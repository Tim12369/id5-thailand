<?php

namespace App\Support;

use App\Support\Assessment\StatusDefinition;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * The fixed wording of the public website — menu labels, headings, buttons,
 * the service guides, the assessment questions — and the changes an editor has
 * made to it in WordPress.
 *
 * The built-in wording lives in resources/lang (and, for the assessment wizard,
 * in config/assessment). An editor's change is one row in `cms_texts`, layered
 * over the built-in line by App\Support\CmsTranslator. Removing the row brings
 * the built-in wording back, so a line can never be left blank by accident and
 * "restore the original" is always possible.
 *
 * In edit mode each line printed on the page also carries an invisible tag
 * naming its key, which is how the page knows what a click on a heading or a
 * button should open. Visitors never receive those tags.
 */
class CmsText
{
    /** The languages the public website is served in. */
    public const LOCALES = ['th', 'en'];

    /**
     * Language files that are not part of the editable website: `messages` is
     * a leftover that no view reads any more.
     */
    private const SKIP_GROUPS = ['messages'];

    /**
     * Lines an editor already changes somewhere better — the homepage banner
     * has its own form with the picture next to it — and which are therefore
     * not offered a second time as loose text.
     */
    private const SKIP_KEYS = ['home.hero_title', 'home.hero_subtitle', 'home.hero_cta'];

    /**
     * What each group of lines is called in the WordPress editor. A group
     * without a name here is shown under its file name, so a new language
     * file is still editable before anyone remembers to add it.
     */
    private const GROUPS = [
        'nav'                     => 'เมนูนำทาง',
        'layout'                  => 'ส่วนหัวและโครงหน้าเว็บ',
        'footer'                  => 'ส่วนท้ายเว็บไซต์',
        'home'                    => 'หน้าแรก',
        'about'                   => 'เกี่ยวกับเรา',
        'ecosystem'               => 'ระบบนิเวศตรวจคนเข้าเมือง',
        'administration_division' => 'หน้าฝ่ายอำนวยการ',
        'foreigner_division'      => 'หน้าฝ่ายบริการคนต่างด้าว',
        'investigation_division'  => 'หน้าฝ่ายสืบสวนสอบสวน',
        'technology_division'     => 'หน้าฝ่ายเทคโนโลยี',
        'services'                => 'หน้าบริการ',
        'visa'                    => 'คู่มือวีซ่า',
        'tm30'                    => 'คู่มือ TM30',
        '90day'                   => 'คู่มือรายงานตัว 90 วัน',
        'extension'               => 'ต่ออายุการพำนัก',
        'overstay'                => 'อยู่เกินกำหนด',
        'smart_guide'             => 'Smart Guide',
        'sw1h'                    => 'Smart Guide: หัวข้อ 5W1H',
        'personas'                => 'ประเภทผู้ใช้บริการ',
        'topics'                  => 'หัวข้อคู่มือ',
        'assessment'              => 'แบบประเมินสถานะ',
        'assessment_q'            => 'แบบประเมินสถานะ: คำถามและตัวเลือก',
        'assessment_status'       => 'แบบประเมินสถานะ: ชื่อสถานะ',
        'offices'                 => 'สำนักงาน',
        'contact'                 => 'ติดต่อเรา',
        'emergency'               => 'เหตุฉุกเฉิน',
        'faq'                     => 'คำถามที่พบบ่อย',
        'downloads'               => 'ดาวน์โหลดแบบฟอร์ม',
        'knowledge'               => 'คลังความรู้',
        'knowledge_center'        => 'คลังความรู้',
        'article'                 => 'ข่าวและบทความ',
        'announcements'           => 'ประกาศ',
        'alerts'                  => 'การแจ้งเตือน',
        'ita'                     => 'ITA',
        'search'                  => 'ค้นหา',
        'open_data'               => 'ข้อมูลเปิดภาครัฐ',
        'cookie'                  => 'แถบแจ้งคุกกี้',
        'ai'                      => 'ผู้ช่วย AI',
    ];

    /** The only markup an editor may use, and only in lines printed as HTML. */
    private const TAGS = ['strong', 'b', 'em', 'i', 'u', 'br', 'span', 'a', 'sup', 'sub'];

    private const ATTRIBUTES = ['class', 'href', 'target', 'rel'];

    /** Characters that carry a line's tag in edit mode: invisible, zero width. */
    private const FENCE  = "\u{2063}";
    private const DIGITS = ["\u{200B}", "\u{200C}", "\u{200D}", "\u{2060}"];

    /** @var array<string,array<string,string>>|null [locale][key] => wording */
    private static ?array $overrides = null;

    /** @var array<string,array>|null */
    private static ?array $catalogue = null;

    private static bool $tagging = false;

    /** @var array<string,int> key => position, for the lines used on this page */
    private static array $used = [];

    // ─── Changes made in WordPress ────────────────────────────────────────

    /**
     * Every change currently in force: [locale][key] => wording.
     *
     * Read once per request. A missing table (a fresh install before its
     * migrations, a broken database) means "no changes", never an error page:
     * the website must still render with its built-in wording.
     */
    public static function overrides(): array
    {
        if (self::$overrides !== null) {
            return self::$overrides;
        }

        $overrides = [];

        try {
            foreach (DB::table('cms_texts')->get(['key', 'locale', 'value']) as $row) {
                $overrides[$row->locale][$row->key] = (string) $row->value;
            }
        } catch (Throwable) {
            $overrides = [];
        }

        return self::$overrides = $overrides;
    }

    public static function override(string $locale, string $key): ?string
    {
        return self::overrides()[$locale][$key] ?? null;
    }

    /**
     * The changes that belong to one language file, keyed the way the file is:
     * "actions.visa.title" for the key "home.actions.visa.title".
     *
     * @return array<string,string>
     */
    public static function forGroup(string $locale, string $group): array
    {
        $lines  = [];
        $prefix = $group.'.';

        foreach (self::overrides()[$locale] ?? [] as $key => $value) {
            if (str_starts_with($key, $prefix)) {
                $lines[substr($key, strlen($prefix))] = $value;
            }
        }

        return $lines;
    }

    /**
     * Forget everything remembered so far. Every new application instance
     * starts with this, so nothing leaks from one request or test into the
     * next.
     */
    public static function reset(): void
    {
        self::$overrides = null;
        self::$catalogue = null;
        self::$tagging   = false;
        self::$used      = [];
    }

    /**
     * The stored changes were just rewritten: re-read them, and make the
     * translator load its language files again with the new wording on top.
     */
    public static function flush(): void
    {
        self::$overrides = null;

        if (app()->resolved('translator')) {
            app('translator')->setLoaded([]);
        }
    }

    // ─── Wording held outside the language files ──────────────────────────

    /**
     * Resolve a ['th' => .., 'en' => ..] label held in config (the assessment
     * questions and statuses) to the visitor's language, honouring an editor's
     * change to it.
     */
    public static function localized(string $key, array $bag): string
    {
        $locale = App::getLocale();

        $text = self::override($locale, $key)
            ?? $bag[$locale]
            ?? $bag['en']
            ?? $bag['th']
            ?? (string) reset($bag);

        return self::$tagging ? self::tag($key, $text) : $text;
    }

    // ─── Everything that can be edited ────────────────────────────────────

    /**
     * Every editable line with its built-in wording:
     * key => ['group' => .., 'th' => ..|null, 'en' => ..|null, 'html' => bool].
     */
    public static function catalogue(): array
    {
        if (self::$catalogue !== null) {
            return self::$catalogue;
        }

        $lines = [];

        foreach (self::LOCALES as $locale) {
            foreach (glob(lang_path($locale.'/*.php')) ?: [] as $file) {
                $group = basename($file, '.php');

                if (in_array($group, self::SKIP_GROUPS, true)) {
                    continue;
                }

                foreach (Arr::dot((array) require $file) as $item => $text) {
                    if (is_string($text)) {
                        $lines[$group.'.'.$item][$locale] = $text;
                    }
                }
            }
        }

        foreach (config('assessment.questions', []) as $node) {
            $base = 'assessment_q.'.$node['id'];

            $lines[$base.'.title'] = $node['title'];

            if (! empty($node['help'])) {
                $lines[$base.'.help'] = $node['help'];
            }

            foreach ($node['options'] ?? [] as $option) {
                $lines[$base.'.option.'.$option['value']] = $option['label'];
            }
        }

        foreach (config('assessment.statuses', []) as $status => $definition) {
            $lines['assessment_status.'.$status] = $definition['label'];
        }

        $lines['assessment_status.'.StatusDefinition::UNDETERMINED_KEY] = StatusDefinition::undetermined()->label;

        $catalogue = [];

        // Leftover lines that nothing prints any more (config/cms_texts.php).
        $skip = array_flip(array_merge(self::SKIP_KEYS, (array) config('cms_texts.unused', [])));

        foreach ($lines as $key => $bag) {
            if (isset($skip[$key])) {
                continue;
            }

            $catalogue[$key] = [
                'group' => explode('.', $key)[0],
                'th'    => $bag['th'] ?? null,
                'en'    => $bag['en'] ?? null,
                'html'  => self::isHtml($key),
            ];
        }

        return self::$catalogue = $catalogue;
    }

    /**
     * Names for the groups that actually have lines: group => label.
     */
    public static function groups(): array
    {
        $groups = [];

        foreach (self::catalogue() as $line) {
            $groups[$line['group']] ??= self::GROUPS[$line['group']] ?? $line['group'];
        }

        return $groups;
    }

    public static function editable(string $key): bool
    {
        return isset(self::catalogue()[$key]);
    }

    /**
     * Lines printed as HTML rather than as plain text. The name says so, which
     * keeps the view (`{!! !!}`) and this rule from drifting apart.
     */
    public static function isHtml(string $key): bool
    {
        return str_ends_with($key, '_html');
    }

    // ─── Printing wording inside JavaScript ───────────────────────────────

    /**
     * A line of wording as a JavaScript string literal, for the handful of
     * places a template hands one to Alpine (`@jsText(__('…'))`).
     *
     * Printing it with `'{{ … }}'` instead is only correct while the wording
     * happens to contain no quote: the browser turns `&#039;` back into `'`
     * inside an attribute, which ends the string early and breaks the widget.
     * Since editors can now change the wording, that would be an apostrophe
     * away. Everything that could end the string or the attribute is written
     * as a \u escape; ordinary text, Thai included, is left as it is.
     */
    public static function js(?string $text): string
    {
        return "'".strtr((string) $text, [
            '\\'      => '\\\\',
            "'"       => '\\u0027',
            '"'       => '\\u0022',
            '<'       => '\\u003C',
            '>'       => '\\u003E',
            '&'       => '\\u0026',
            "\n"      => '\\n',
            "\r"      => '',
            "\u{2028}" => '\\u2028',
            "\u{2029}" => '\\u2029',
        ])."'";
    }

    // ─── Accepting a change ───────────────────────────────────────────────

    /**
     * Make an editor's wording safe to store for a given line.
     *
     * A plain line is printed escaped, so nothing in it can become markup and
     * it only needs tidying. A line printed as HTML is reduced to a short list
     * of inline tags; anything else — scripts, event handlers, frames, links
     * to a `javascript:` address — is removed here, whatever WordPress let
     * through.
     */
    public static function clean(string $key, string $value): string
    {
        $value = trim(str_replace(["\r\n", "\r"], "\n", $value));
        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x{2063}]/u', '', $value) ?? '';

        return self::isHtml($key) ? self::cleanHtml($value) : $value;
    }

    private static function cleanHtml(string $html): string
    {
        $html = strip_tags($html, self::TAGS);

        if (! str_contains($html, '<')) {
            return $html;
        }

        $dom = new \DOMDocument();

        // The XML declaration makes the parser read the fragment as UTF-8.
        if (! @$dom->loadHTML('<?xml encoding="UTF-8"><div>'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING)) {
            return strip_tags($html);
        }

        $root = $dom->getElementsByTagName('div')->item(0);

        if (! $root) {
            return strip_tags($html);
        }

        foreach (iterator_to_array($root->getElementsByTagName('*')) as $element) {
            foreach (iterator_to_array($element->attributes) as $attribute) {
                $name = strtolower($attribute->nodeName);

                $keep = in_array($name, self::ATTRIBUTES, true)
                    && ($name !== 'href' || preg_match('#^(https?://|mailto:|tel:|/(?!/)|\#)#i', trim($attribute->nodeValue)));

                if (! $keep) {
                    $element->removeAttribute($attribute->nodeName);
                }
            }

            if (strtolower($element->nodeName) === 'a' && $element->getAttribute('target') === '_blank') {
                $element->setAttribute('rel', 'noopener noreferrer');
            }
        }

        $clean = '';

        foreach ($root->childNodes as $child) {
            $clean .= $dom->saveHTML($child);
        }

        return trim($clean);
    }

    // ─── Edit mode: knowing which line is which on the page ───────────────

    /**
     * Tag every editable line printed from here on — or stop doing so.
     *
     * Set for every request, on or off, and the page's list of lines starts
     * empty each time: on a server that handles many requests in one process
     * nothing may carry over from an editor's page into a visitor's.
     */
    public static function setTagging(bool $on): void
    {
        self::$tagging = $on;
        self::$used    = [];
    }

    public static function tagging(): bool
    {
        return self::$tagging;
    }

    /**
     * Tag a translated line — or, for a key that names a whole list, each line
     * in it.
     */
    public static function tagLine(string $key, mixed $line): mixed
    {
        if (is_array($line)) {
            foreach ($line as $item => $value) {
                $line[$item] = self::tagLine($key.'.'.$item, $value);
            }

            return $line;
        }

        return is_string($line) && self::editable($key) ? self::tag($key, $line) : $line;
    }

    /**
     * Append the invisible tag for a key. The tag is a position in this page's
     * list of lines, written in zero-width characters between two invisible
     * separators; the edit-mode script reads it, removes it, and remembers
     * which element showed the line.
     */
    public static function tag(string $key, string $text): string
    {
        $position = self::$used[$key] ??= count(self::$used);
        $code     = '';

        do {
            $code     = self::DIGITS[$position % 4].$code;
            $position = intdiv($position, 4);
        } while ($position > 0);

        return $text.self::FENCE.$code.self::FENCE;
    }

    /** Remove any tags from a string, for the rare value that must stay exact. */
    public static function untag(string $text): string
    {
        return preg_replace('/\x{2063}[\x{200B}\x{200C}\x{200D}\x{2060}]+\x{2063}/u', '', $text) ?? $text;
    }

    /**
     * The lines used on this page, in tag order, with what the editor needs to
     * show a form for each.
     */
    public static function used(): array
    {
        $catalogue = self::catalogue();
        $lines     = [];

        foreach (array_keys(self::$used) as $key) {
            $lines[] = ['key' => $key, 'group' => $catalogue[$key]['group'] ?? explode('.', $key)[0]];
        }

        return $lines;
    }
}
