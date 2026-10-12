<?php

namespace App\Services\Cms;

use App\Models\Article;
use App\Models\AssessmentRecommendation;
use App\Models\Faq;
use App\Models\GuideTopic;
use App\Models\HomeDivisionCard;
use App\Models\ItaCard;
use App\Models\OrgChartMember;
use App\Models\PublicForm;
use App\Models\Setting;
use App\Models\SmartGuide;
use App\Models\Station;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Throwable;

/**
 * Moves the content that already exists in this database into WordPress.
 *
 * This runs once at cut-over (and again afterwards if anything needs repairing).
 * It is deliberately non-destructive in both directions: nothing is deleted
 * here, and WordPress matches each item on the row it came from, so running the
 * export twice updates the same posts rather than creating a second copy of
 * every article.
 *
 * Images and PDFs are uploaded into the WordPress Media Library, with a local
 * record of which file became which attachment so re-running does not fill the
 * library with duplicates.
 */
class CmsPushService
{
    /** Content types in dependency order: forms and offices first, because
     *  Smart Guide topics reference them by code. */
    public const ORDER = [
        'id5_form',
        'id5_office',
        'post',
        'id5_announcement',
        'id5_alert',
        'id5_knowledge',
        'id5_faq',
        'id5_person',
        'id5_ita_card',
        'id5_division_card',
        'id5_guide_topic',
        'id5_guide_content',
        'id5_assessment',
    ];

    private bool $dryRun = false;

    /** @var array<string,int> */
    private array $counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

    public function __construct(
        private readonly WordPressClient $client,
    ) {}

    public static function make(): self
    {
        return new self(WordPressClient::fromConfig());
    }

    public function dryRun(bool $dryRun = true): self
    {
        $this->dryRun = $dryRun;

        return $this;
    }

    /**
     * Export one content type.
     *
     * @return array{created:int,updated:int,skipped:int,failed:int}
     */
    public function push(string $postType): array
    {
        $this->counts = ['created' => 0, 'updated' => 0, 'skipped' => 0, 'failed' => 0];

        match ($postType) {
            'post'              => $this->pushArticles('news', 'post'),
            'id5_announcement'  => $this->pushArticles('announcement', 'id5_announcement'),
            'id5_alert'         => $this->pushArticles('alert', 'id5_alert'),
            'id5_knowledge'     => $this->pushArticles('kb_public', 'id5_knowledge'),
            'id5_faq'           => $this->pushFaqs(),
            'id5_form'          => $this->pushForms(),
            'id5_office'        => $this->pushOffices(),
            'id5_person'        => $this->pushPeople(),
            'id5_ita_card'      => $this->pushItaCards(),
            'id5_division_card' => $this->pushDivisionCards(),
            'id5_guide_topic'   => $this->pushGuideTopics(),
            'id5_guide_content' => $this->pushGuideContent(),
            'id5_assessment'    => $this->pushAssessments(),
            default             => null,
        };

        return $this->counts;
    }

    /**
     * Create in WordPress the categories that exist only here.
     *
     * Exporting content creates the categories that content uses. One with no
     * content yet would be left behind — still listed on the website, but
     * with nothing in WordPress to rename. This sends those, and remembers the
     * WordPress ID each was given.
     */
    public function pushTerms(): int
    {
        $pending = [];

        foreach (CmsSyncService::CATEGORY_TYPES as $taxonomy => $types) {
            foreach (\App\Models\Category::whereIn('type', $types)->whereNull('wp_term_id')->orderBy('sort_order')->get() as $category) {
                $pending[$taxonomy][] = [
                    'name'   => $category->name_th,
                    'slug'   => $category->slug,
                    'fields' => ['name_en' => (string) $category->name_en, 'sort_order' => (int) $category->sort_order],
                ];
            }
        }

        $count = array_sum(array_map('count', $pending));

        if (! $count || $this->dryRun) {
            return $count;
        }

        foreach ($this->client->importTerms($pending)['terms'] ?? [] as $term) {
            \App\Models\Category::whereIn('type', CmsSyncService::CATEGORY_TYPES[$term['taxonomy']] ?? [])
                ->whereNull('wp_term_id')
                ->where('slug', $term['slug'])
                ->update(['wp_term_id' => $term['term_id']]);
        }

        return $count;
    }

    /**
     * Send the changed wording held here back to WordPress.
     *
     * Only needed after WordPress has been rebuilt from a backup older than
     * the editors' latest changes: the website still has them, WordPress no
     * longer does, and the next pull would otherwise undo them.
     */
    public function pushTexts(): int
    {
        $texts = DB::table('cms_texts')
            ->orderBy('key')
            ->get(['key', 'locale', 'value'])
            ->map(fn ($row) => ['key' => $row->key, 'locale' => $row->locale, 'value' => $row->value])
            ->all();

        if (! $texts || $this->dryRun) {
            return count($texts);
        }

        return (int) ($this->client->importTexts($texts)['imported'] ?? 0);
    }

    /**
     * Export the site-wide settings the Customizer now owns.
     */
    public function pushSettings(): int
    {
        $keys = [
            'general.site_name', 'general.emergency_phone',
            'home.hero_title_th', 'home.hero_title_en',
            'home.hero_subtitle_th', 'home.hero_subtitle_en',
            'home.hero_cta_th', 'home.hero_cta_en',
            'site.footer_copyright', 'site.footer_address',
        ];

        $settings = [];

        foreach ($keys as $key) {
            $value = Setting::get($key);

            if (filled($value)) {
                $settings[$key] = $value;
            }
        }

        // The hero image is a stored file, so it has to become an attachment
        // before WordPress can reference it.
        $heroPath = Setting::get('site.hero_image');

        if (filled($heroPath)) {
            $attachmentId = $this->uploadFromDisk('public', $heroPath);

            if ($attachmentId) {
                $settings['site.hero_image'] = $attachmentId;
            }
        }

        if ($this->dryRun || ! $settings) {
            return count($settings);
        }

        $this->client->importSettings($settings);

        return count($settings);
    }

    // ─── Per-type exporters ───────────────────────────────────────────────

    private function pushArticles(string $type, string $postType): void
    {
        // Soft-deleted articles are left out on purpose: someone deleted them,
        // and exporting them would bring deleted content back as drafts.
        Article::query()
            ->where('type', $type)
            ->orderBy('id')
            ->each(function (Article $article) use ($postType) {
                $terms = [];

                if ($article->category) {
                    $taxonomy = $postType === 'post' ? 'category' : 'id5_content_category';

                    $terms[$taxonomy] = [[
                        'name'   => $article->category->name_th,
                        'slug'   => $article->category->slug,
                        'fields' => [
                            'name_en'    => $article->category->name_en,
                            'sort_order' => $article->category->sort_order,
                        ],
                    ]];
                }

                $tags = $article->tags->map(fn ($tag) => ['name' => $tag->name, 'slug' => $tag->slug])->all();

                if ($tags) {
                    $terms['post_tag'] = $tags;
                }

                $this->send($article, $postType, 'articles', [
                    'title'          => $article->title_th,
                    'slug'           => $article->slug,
                    'content'        => $article->body_th,
                    'excerpt'        => $article->excerpt_th,
                    'status'         => $this->wpStatus($article->status),
                    'date_gmt'       => optional($article->published_at ?? $article->created_at)->toIso8601String(),
                    'menu_order'     => 0,
                    'featured_media' => $this->uploadFromDisk('public', $article->thumbnail),
                    'fields'         => [
                        'title_en'        => $article->title_en,
                        'excerpt_en'      => $article->excerpt_en,
                        'body_en'         => $article->body_en,
                        'is_pinned'       => $article->is_pinned,
                        'expired_at'      => optional($article->expired_at)->toDateString(),
                        'seo_title'       => $article->seo_title,
                        'seo_description' => $article->seo_description,
                        'alert_level'     => $article->alert_level,
                    ],
                    'terms' => $terms,
                ]);
            });
    }

    private function pushFaqs(): void
    {
        Faq::with('category')->orderBy('sort_order')->each(function (Faq $faq) {
            $terms = [];

            if ($faq->category) {
                $terms['id5_content_category'] = [[
                    'name'   => $faq->category->name_th,
                    'slug'   => $faq->category->slug,
                    'fields' => [
                        'name_en'    => $faq->category->name_en,
                        'sort_order' => $faq->category->sort_order,
                    ],
                ]];
            }

            $this->send($faq, 'id5_faq', 'faqs', [
                'title'      => $faq->question_th,
                'content'    => $faq->answer_th,
                'status'     => $faq->is_active ? 'publish' : 'draft',
                'date_gmt'   => optional($faq->created_at)->toIso8601String(),
                'menu_order' => $faq->sort_order,
                'fields'     => [
                    'question_en' => $faq->question_en,
                    'answer_en'   => $faq->answer_en,
                ],
                'terms' => $terms,
            ]);
        });
    }

    private function pushForms(): void
    {
        PublicForm::orderBy('sort_order')->each(function (PublicForm $form) {
            $this->send($form, 'id5_form', 'public_forms', [
                'title'      => $form->name_th,
                'status'     => $form->is_active ? 'publish' : 'draft',
                'date_gmt'   => optional($form->created_at)->toIso8601String(),
                'menu_order' => $form->sort_order,
                'fields'     => [
                    'code'               => $form->code,
                    'name_en'            => $form->name_en,
                    'description_th'     => $form->description_th,
                    'description_en'     => $form->description_en,
                    'version'            => $form->version,
                    'file'               => $this->uploadFromDisk($form->disk, $form->path),
                    'show_on_about_page' => $form->show_on_about_page,
                    'is_active'          => $form->is_active,
                ],
                'terms' => [
                    'id5_form_category' => [['name' => $form->category, 'slug' => $form->category]],
                ],
            ]);
        });
    }

    private function pushOffices(): void
    {
        $days = ['mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun'];

        Station::orderBy('sort_order')->each(function (Station $station) use ($days) {
            $hours = [];

            foreach ($days as $day) {
                $hours['hours_'.$day] = $station->opening_hours[$day] ?? null;
            }

            $this->send($station, 'id5_office', 'stations', [
                'title'          => $station->name_th,
                'slug'           => $station->slug,
                'status'         => $station->is_active ? 'publish' : 'draft',
                'date_gmt'       => optional($station->created_at)->toIso8601String(),
                'menu_order'     => $station->sort_order,
                'featured_media' => $this->uploadFromDisk('public', $station->image_path),
                'fields'         => array_merge($hours, [
                    'code'                      => $station->code,
                    'name_en'                   => $station->name_en,
                    'address_th'                => $station->address_th,
                    'address_en'                => $station->address_en,
                    'phone'                     => $station->phone,
                    'email'                     => $station->email,
                    'website'                   => $station->website,
                    'facebook_url'              => $station->facebook_url,
                    'line_url'                  => $station->line_url,
                    // The raw column, not the attribute: the model's accessor
                    // invents a directions link when none is stored, and exporting
                    // that would freeze a generated value in as if someone typed it.
                    'google_maps_url'           => $station->getRawOriginal('google_maps_url'),
                    'parent_organization'       => $station->parent_organization,
                    'online_services_available' => $station->online_services_available,
                    'last_verified_at'          => optional($station->last_verified_at)->toDateString(),
                    'lat'                       => $station->lat,
                    'lng'                       => $station->lng,
                    'is_active'                 => $station->is_active,
                ]),
                'terms' => array_filter([
                    'id5_region'   => $station->region ? [['name' => $station->region]] : null,
                    'id5_province' => $station->province ? [['name' => $station->province]] : null,
                ]),
            ]);
        });
    }

    private function pushPeople(): void
    {
        OrgChartMember::with('tier.section')->orderBy('id')->each(function (OrgChartMember $member) {
            $tier    = $member->tier;
            $section = $tier?->section;

            if (! $section) {
                $this->counts['skipped']++;

                return;
            }

            $this->send($member, 'id5_person', 'org_chart_members', [
                'title'          => $member->name_th,
                'status'         => 'publish',
                'date_gmt'       => optional($member->created_at)->toIso8601String(),
                'menu_order'     => $member->sort_order,
                'featured_media' => $this->uploadFromDisk('public', $member->photo_path),
                'fields'         => [
                    'rank_th'       => $member->rank_th,
                    'position_th'   => $member->position_th,
                    'name_en'       => $member->name_en,
                    'tier_label_th' => $tier->label_th,
                    'tier_label_en' => $tier->label_en,
                    'tier_order'    => $tier->sort_order,
                ],
                'terms' => [
                    'id5_org_section' => [[
                        'name'   => $section->name_th,
                        'slug'   => $section->slug,
                        'fields' => [
                            'name_en'            => $section->name_en,
                            'theme'              => $section->theme,
                            'sort_order'         => $section->sort_order,
                            'show_on_about_page' => $section->show_on_about_page,
                        ],
                    ]],
                ],
            ]);
        });
    }

    private function pushItaCards(): void
    {
        ItaCard::with('section')->orderBy('sort_order')->each(function (ItaCard $card) {
            $section = $card->section;

            if (! $section) {
                $this->counts['skipped']++;

                return;
            }

            $this->send($card, 'id5_ita_card', 'ita_cards', [
                'title'      => $card->title_th,
                'content'    => '',
                'status'     => $card->is_visible ? 'publish' : 'draft',
                'date_gmt'   => optional($card->created_at)->toIso8601String(),
                'menu_order' => $card->sort_order,
                'fields'     => [
                    'card_number' => $card->card_number,
                    'title_en'    => $card->title_en,
                    'content_th'  => $card->content_th,
                    'content_en'  => $card->content_en,
                    'icon'        => $card->icon,
                    'icon_image'  => $this->uploadFromDisk('public', $card->icon_image),
                    'color'       => $card->color,
                    'is_visible'  => $card->is_visible,
                ],
                'terms' => [
                    'id5_ita_section' => [[
                        'name'   => $section->title_th,
                        'fields' => [
                            'section_number' => $section->section_number,
                            'title_en'       => $section->title_en,
                            'description_th' => $section->description_th,
                            'description_en' => $section->description_en,
                            'sort_order'     => $section->sort_order,
                            'is_visible'     => $section->is_visible,
                        ],
                    ]],
                ],
            ]);
        });
    }

    private function pushDivisionCards(): void
    {
        HomeDivisionCard::orderBy('sort_order')->each(function (HomeDivisionCard $card) {
            $this->send($card, 'id5_division_card', 'home_division_cards', [
                'title'          => $card->title_th,
                'status'         => 'publish',
                'date_gmt'       => optional($card->created_at)->toIso8601String(),
                'menu_order'     => $card->sort_order,
                'featured_media' => $this->uploadFromDisk('public', $card->photo_path),
                'fields'         => [
                    'card_key' => $card->key,
                    'title_en' => $card->title_en,
                    'desc_th'  => $card->desc_th,
                    'desc_en'  => $card->desc_en,
                ],
            ]);
        });
    }

    private function pushGuideTopics(): void
    {
        GuideTopic::with(['documents.form', 'steps', 'locations.station'])
            ->orderBy('sort_order')
            ->each(function (GuideTopic $topic) {
                $this->send($topic, 'id5_guide_topic', 'guide_topics', [
                    'title'      => $this->label('topics', $topic->topic),
                    'status'     => $topic->is_active ? 'publish' : 'draft',
                    'date_gmt'   => optional($topic->created_at)->toIso8601String(),
                    'menu_order' => $topic->sort_order,
                    'fields'     => [
                        'topic_key'       => $topic->topic,
                        'icon'            => $topic->icon,
                        'color'           => $topic->color,
                        'deadline_days'   => $topic->deadline_days,
                        'is_urgent'       => $topic->is_urgent,
                        'search_keywords' => $topic->search_keywords,
                        'is_active'       => $topic->is_active,
                        'documents'       => $topic->documents->map(fn ($document) => [
                            'name_th'     => $document->name_th,
                            'name_en'     => $document->name_en,
                            'form_code'   => $document->form?->code,
                            'is_required' => $document->is_required,
                        ])->all(),
                        'steps'           => $topic->steps->map(fn ($step) => [
                            'title_th'       => $step->title_th,
                            'title_en'       => $step->title_en,
                            'description_th' => $step->description_th,
                            'description_en' => $step->description_en,
                        ])->all(),
                        'locations'       => $topic->locations->map(fn ($location) => [
                            'type'        => $location->type,
                            'label_th'    => $location->label_th,
                            'label_en'    => $location->label_en,
                            'url'         => $location->url,
                            'office_code' => $location->station?->code,
                        ])->all(),
                    ],
                ]);
            });
    }

    /**
     * Smart Guide audience guidance: one WordPress entry per audience + topic,
     * carrying its six answers as HTML exactly as stored.
     */
    private function pushGuideContent(): void
    {
        $groups = SmartGuide::orderBy('id')->get()->groupBy(fn (SmartGuide $g) => $g->persona.'|'.$g->topic);

        foreach ($groups as $rows) {
            $first  = $rows->first();
            $fields = ['persona' => $first->persona, 'topic_key' => $first->topic];

            foreach ($rows as $row) {
                $fields[$row->dimension.'_th'] = $row->content_th;
                $fields[$row->dimension.'_en'] = $row->content_en;
            }

            if ($this->dryRun) {
                $this->counts[$first->wp_post_id ? 'updated' : 'created']++;

                continue;
            }

            try {
                $response = $this->client->importItem([
                    'post_type'   => 'id5_guide_content',
                    'legacy_type' => 'smart_guides',
                    // The lowest row id in the group is stable for the life of
                    // the pair, which is all the idempotency key needs.
                    'legacy_id'   => $rows->min('id'),
                    'wp_post_id'  => $first->wp_post_id,
                    'title'       => $this->label('personas', $first->persona).' — '.$this->label('topics', $first->topic),
                    'status'      => $rows->contains('is_active', true) ? 'publish' : 'draft',
                    'date_gmt'    => optional($first->created_at)->toIso8601String(),
                    'fields'      => $fields,
                ]);

                SmartGuide::whereIn('id', $rows->pluck('id'))->update([
                    'wp_post_id'    => $response['wp_post_id'],
                    'cms_synced_at' => now(),
                ]);

                $this->counts[($response['created'] ?? false) ? 'created' : 'updated']++;
            } catch (Throwable $e) {
                $this->counts['failed']++;

                Log::channel('cms')->error('Could not export Smart Guide guidance to WordPress.', [
                    'persona' => $first->persona,
                    'topic'   => $first->topic,
                    'error'   => $e->getMessage(),
                ]);
            }
        }
    }

    /**
     * The Thai display name for a persona or topic key, falling back to the key
     * itself when the language file has no entry for it.
     */
    private function label(string $group, string $key): string
    {
        $full  = $group.'.'.$key;
        $label = __($full, [], 'th');

        return (is_string($label) && $label !== $full) ? $label : $key;
    }

    private function pushAssessments(): void
    {
        $labels = config('assessment.statuses', []);

        AssessmentRecommendation::orderBy('sort_order')->each(function (AssessmentRecommendation $item) use ($labels) {
            $fields = [
                'status_key'        => $item->status_key,
                'risk_level'        => $item->risk_level,
                'suggested_persona' => $item->suggested_persona,
                'suggested_topics'  => $item->suggested_topics ? implode(', ', $item->suggested_topics) : null,
                'is_active'         => $item->is_active,
            ];

            foreach (AssessmentRecommendation::CONTENT_FIELDS as $field) {
                $fields[$field.'_th'] = $item->{$field.'_th'};
                $fields[$field.'_en'] = $item->{$field.'_en'};
            }

            $label = $labels[$item->status_key]['label']['th']
                ?? $labels[$item->status_key]['label']
                ?? $item->status_key;

            $this->send($item, 'id5_assessment', 'assessment_recommendations', [
                'title'      => is_string($label) ? $label : $item->status_key,
                'status'     => $item->is_active ? 'publish' : 'draft',
                'date_gmt'   => optional($item->created_at)->toIso8601String(),
                'menu_order' => $item->sort_order,
                'fields'     => $fields,
            ]);
        });
    }

    // ─── Transport ────────────────────────────────────────────────────────

    /**
     * Send one item to WordPress and record the resulting post ID locally, so
     * later webhooks about that post find the row it came from.
     */
    private function send(Model $model, string $postType, string $legacyTable, array $payload): void
    {
        if ($this->dryRun) {
            $this->counts[$model->wp_post_id ? 'updated' : 'created']++;

            return;
        }

        try {
            $response = $this->client->importItem(array_merge($payload, [
                'post_type'   => $postType,
                'legacy_type' => $legacyTable,
                'legacy_id'   => $model->getKey(),
                // A row already linked to a post must update that post. Without
                // this, content that originated in WordPress and was synced down
                // here would be exported back as a second copy of itself.
                'wp_post_id'  => $model->wp_post_id,
            ]));

            $model->forceFill([
                'wp_post_id'    => $response['wp_post_id'],
                'cms_synced_at' => now(),
            ])->saveQuietly();

            $this->counts[($response['created'] ?? false) ? 'created' : 'updated']++;
        } catch (Throwable $e) {
            $this->counts['failed']++;

            Log::channel('cms')->error('Could not export one item to WordPress.', [
                'post_type' => $postType,
                'table'     => $legacyTable,
                'id'        => $model->getKey(),
                'error'     => $e->getMessage(),
            ]);
        }
    }

    /**
     * Upload a stored file into the WordPress Media Library, reusing the
     * attachment from a previous run when the same file has already been sent.
     *
     * @return int|null The WordPress attachment ID.
     */
    private function uploadFromDisk(?string $disk, ?string $path): ?int
    {
        if (blank($disk) || blank($path)) {
            return null;
        }

        $scope = $disk.':'.$path;

        $known = DB::table('cms_sync_state')
            ->where('kind', 'media')
            ->where('scope', $scope)
            ->value('reference');

        if ($known) {
            return (int) $known;
        }

        if (! Storage::disk($disk)->exists($path)) {
            Log::channel('cms')->warning('Referenced file is missing and was not uploaded.', ['file' => $scope]);

            return null;
        }

        if ($this->dryRun) {
            return null;
        }

        try {
            $attachment = $this->client->uploadMedia(
                Storage::disk($disk)->path($path),
                basename($path),
                Storage::disk($disk)->mimeType($path) ?: 'application/octet-stream',
            );

            DB::table('cms_sync_state')->insert([
                'kind'        => 'media',
                'scope'       => $scope,
                'reference'   => (string) $attachment['id'],
                'status'      => 'ok',
                'last_run_at' => now(),
                'created_at'  => now(),
                'updated_at'  => now(),
            ]);

            return (int) $attachment['id'];
        } catch (Throwable $e) {
            Log::channel('cms')->warning('Could not upload a file to WordPress.', [
                'file'  => $scope,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    /**
     * Map this application's article status onto a WordPress post status.
     */
    private function wpStatus(string $status): string
    {
        return match ($status) {
            'published' => 'publish',
            'review'    => 'pending',
            'archived'  => 'private',
            default     => 'draft',
        };
    }
}
