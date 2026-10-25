<?php

namespace App\Support;

use App\Models\Article;
use App\Models\AssessmentRecommendation;
use App\Models\Category;
use App\Models\Faq;
use App\Models\GuideTopic;
use App\Models\HomeDivisionCard;
use App\Models\ItaCard;
use App\Models\ItaSection;
use App\Models\OrgChartMember;
use App\Models\OrgChartSection;
use App\Models\PageSection;
use App\Models\PublicForm;
use App\Models\SmartGuide;
use App\Models\Station;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

/**
 * "Edit mode" on the public website.
 *
 * A website editor who arrives from WordPress sees the site exactly as visitors
 * do, plus a WordPress-style bar across the top and a pencil on everything that
 * can be changed. Clicking one opens that item in the WordPress site editor.
 * Ordinary visitors get none of this: not the bar, not the pencils, not a single
 * extra byte of markup.
 *
 * How an editor is recognised: WordPress hands them a short-lived token, signed
 * with the secret the two systems already share for webhooks. Nothing about the
 * visitor's WordPress login is visible to this application, and nothing here
 * grants any ability to change content — the token only decides whether the
 * pencils are drawn. Every actual edit still happens in WordPress, behind
 * WordPress's own login.
 */
class CmsEdit
{
    public const PARAM = 'id5_edit';

    private const SESSION = 'cms_edit';

    /** Resolved once per request by the CmsEditMode middleware. */
    private static ?array $state = null;

    // ─── Per-request state ────────────────────────────────────────────────

    /**
     * Work out whether this request is in edit mode.
     *
     * A token in the URL is checked first and, when valid, remembered in the
     * session so the editor can keep browsing. The token is also honoured on
     * its own, without a session: inside the WordPress editor the site is shown
     * in a frame, where browsers may refuse to send this site's cookies.
     */
    public static function boot(Request $request): void
    {
        self::$state = null;

        if (! config('cms.enabled') || blank(config('cms.webhook.secret')) || blank(config('cms.base_url'))) {
            return;
        }

        $token = $request->query(self::PARAM);

        if (is_string($token) && ($claims = self::verify($token))) {
            self::$state = ['name' => $claims['n'] ?? '', 'exp' => (int) $claims['e'], 'token' => $token];

            if ($request->hasSession()) {
                $request->session()->put(self::SESSION, self::$state);
            }

            return;
        }

        $saved = $request->hasSession() ? $request->session()->get(self::SESSION) : null;

        if (is_array($saved) && ($saved['exp'] ?? 0) > time()) {
            self::$state = $saved;
        } elseif ($saved) {
            $request->session()->forget(self::SESSION);
        }
    }

    public static function leave(Request $request): void
    {
        self::$state = null;
        $request->session()->forget(self::SESSION);
    }

    public static function active(): bool
    {
        return self::$state !== null;
    }

    public static function name(): string
    {
        return (string) (self::$state['name'] ?? '');
    }

    public static function token(): ?string
    {
        return self::$state['token'] ?? null;
    }

    // ─── Tokens ───────────────────────────────────────────────────────────

    /**
     * Check a token issued by the WordPress plugin and return its claims.
     *
     * Format: base64url(JSON {n: display name, e: expiry}) . "." . HMAC-SHA256.
     * The signed text is prefixed so a signature made for this purpose can
     * never be replayed as a webhook signature, which uses the same secret.
     */
    public static function verify(string $token): ?array
    {
        $parts = explode('.', $token);

        if (count($parts) !== 2) {
            return null;
        }

        [$payload, $signature] = $parts;

        $expected = hash_hmac('sha256', 'id5-edit-mode|'.$payload, (string) config('cms.webhook.secret'));

        if (! hash_equals($expected, $signature)) {
            return null;
        }

        $claims = json_decode((string) base64_decode(strtr($payload, '-_', '+/')), true);

        if (! is_array($claims) || (int) ($claims['e'] ?? 0) <= time()) {
            return null;
        }

        return $claims;
    }

    // ─── Links into WordPress ─────────────────────────────────────────────

    public static function wordpressUrl(): string
    {
        return (string) config('cms.base_url');
    }

    /**
     * The WordPress site editor, showing a given page of this website and
     * optionally opening one item for editing straight away.
     */
    public static function editorUrl(?string $path = null, ?string $open = null): string
    {
        $query = array_filter(['page' => 'id5-site-editor', 'path' => $path, 'open' => $open]);

        return self::wordpressUrl().'/wp-admin/admin.php?'.http_build_query($query);
    }

    // ─── Markers ──────────────────────────────────────────────────────────

    /**
     * An invisible marker telling the edit-mode script that the element right
     * after it can be edited, and what opens when its pencil is clicked.
     *
     * Emitted through the `@cmsEdit(...)` Blade directive. Returns an empty
     * string outside edit mode, so the page a visitor receives is unchanged.
     *
     * @param  Model|string|null $target A synced model, or a string such as
     *                                   "setting:hero", "list:id5_office" or
     *                                   "terms:id5_province".
     */
    public static function marker(mixed $target, ?string $kind = null, ?string $title = null): string
    {
        if (! self::active() || $target === null) {
            return '';
        }

        if ($target instanceof Model) {
            // A category, an ITA section or an org-chart unit is a WordPress
            // term rather than a post; its pencil opens the term's own form.
            $id = match (true) {
                filled($target->wp_post_id ?? null) => 'post:'.(int) $target->wp_post_id,
                filled($target->wp_term_id ?? null) => 'term:'.(int) $target->wp_term_id,
                default                             => null,
            };

            if ($id === null) {
                return ''; // not managed in WordPress, so nothing to open
            }

            [$defaultKind, $defaultTitle] = self::describe($target);

            $kind  ??= $defaultKind;
            $title ??= $defaultTitle;
        } else {
            $id = (string) $target;
        }

        return sprintf(
            '<span hidden data-cms-edit="%s" data-cms-kind="%s" data-cms-title="%s"></span>',
            e($id),
            e((string) $kind),
            e(mb_strimwidth(CmsText::untag((string) $title), 0, 90, '…'))
        );
    }

    /**
     * A "+" tile, drawn only in edit mode, that opens a new item's form in
     * WordPress — optionally with some of it already filled in.
     *
     * Emitted through the `@cmsAdd(...)` Blade directive. Returns an empty
     * string outside edit mode, so the page a visitor receives is unchanged.
     *
     * @param  string $target Such as "new:id5_person?id5_section=94&…".
     */
    public static function addTile(?string $target, string $label, ?string $where = null, bool $wide = false): string
    {
        if (! self::active() || blank($target)) {
            return '';
        }

        return sprintf(
            '<button type="button" class="id5-add%s" data-cms-add="%s" data-cms-kind="%s" data-cms-title="%s"><i class="ti ti-plus" aria-hidden="true"></i><span>%s</span></button>',
            $wide ? ' id5-add--wide' : '',   // a full-width bar under a list, rather than a card-sized tile
            e($target),
            e($label),
            e(CmsText::untag((string) $where)),
            e($label)
        );
    }

    /**
     * The target that opens "add a person" already placed in one row of the
     * organisation chart: its unit, the row's name and the row's position.
     */
    public static function newPersonIn(?OrgChartSection $section, $tier): ?string
    {
        if (! $section || blank($section->wp_term_id) || ! $tier) {
            return null; // a unit WordPress does not know cannot be pre-selected
        }

        return 'new:id5_person?'.http_build_query([
            'id5_section'       => (int) $section->wp_term_id,
            'id5_tier_label_th' => (string) $tier->label_th,
            'id5_tier_label_en' => (string) $tier->label_en,
            'id5_tier_order'    => (int) $tier->sort_order,
        ], '', '&', PHP_QUERY_RFC3986);
    }

    /**
     * What to call a model in the editor's list: [kind, title].
     */
    private static function describe(Model $model): array
    {
        return match (true) {
            $model instanceof Article => [
                ['news' => 'ข่าว', 'announcement' => 'ประกาศ', 'alert' => 'แจ้งเตือน', 'kb_public' => 'คลังความรู้'][$model->type] ?? 'บทความ',
                $model->title_th,
            ],
            $model instanceof Faq                      => ['คำถามที่พบบ่อย', $model->question_th],
            $model instanceof PublicForm               => ['แบบฟอร์ม', $model->code.' — '.$model->name_th],
            $model instanceof Station                  => ['สำนักงาน', $model->name_th],
            $model instanceof OrgChartMember           => ['บุคลากร', trim(($model->rank_th ? $model->rank_th.' ' : '').$model->name_th)],
            $model instanceof ItaCard                  => ['หัวข้อ ITA', $model->title_th],
            $model instanceof HomeDivisionCard         => ['การ์ดหน้าแรก', $model->title_th],
            $model instanceof GuideTopic               => ['หัวข้อคู่มือ', __('topics.'.$model->topic, [], 'th')],
            $model instanceof SmartGuide               => ['เนื้อหาคู่มือ', __('personas.'.$model->persona, [], 'th').' — '.__('topics.'.$model->topic, [], 'th')],
            $model instanceof AssessmentRecommendation => ['ผลการประเมิน', $model->status_key],
            $model instanceof Category                 => ['หมวดหมู่', $model->name_th],
            $model instanceof ItaSection               => ['หมวด ITA', $model->title_th],
            $model instanceof OrgChartSection          => ['หน่วยงานในผัง', $model->name_th],
            $model instanceof PageSection              => $model->kind === PageSection::PAGE ? ['หน้าเว็บ', (string) $model->title_th] : ['ส่วนเนื้อหา', (string) ($model->title_th ?: 'ส่วนเนื้อหา')],
            default                                    => ['เนื้อหา', (string) $model->getKey()],
        };
    }
}
