# WordPress Migration Plan — ID5 @ Thailand 5.0

**Scope:** replace the custom Internal Portal's *content-management* screens with WordPress, without
rebuilding the public website.

---

## 1. Architecture discovered (Phase 1 audit)

Everything below was read out of the repository and the live `id5_thailand` MySQL database.

| # | Question | Finding |
|---|---|---|
| 1 | Frontend stack | **Server-rendered Blade** (Laravel 12) + **Alpine.js 3** + **Tailwind CSS 4**, bundled by **Vite 7**. No SPA, no React/Vue. Icons: `@tabler/icons-webfont`. Maps: `@googlemaps/markerclusterer`. |
| 2 | Backend stack | **Laravel 12.61** (PHP ^8.2), classic MVC: `app/Http/Controllers/{Public,Internal,Line}`, `app/Services/*`, `app/Models/*`. One `app/Repositories` class (Command Center). |
| 3 | Database | **MySQL** (`id5_thailand`, utf8mb4) via Eloquent. A stale `database/database.sqlite` exists but `.env` points at MySQL. 54 tables, 64 migrations. |
| 4 | Authentication | Laravel session guard (`web`) with **custom internal middleware**: `internal.auth` (`InternalAuthenticate`), `internal.guest`. Custom RBAC: `roles`, `permissions`, `permission_role`, `role_user`; checks via `User::hasPermission('module.view|manage')`. 7 roles, 22 permissions (11 modules x view/manage). Password reset wired to `internal.password.reset`. |
| 5 | Existing APIs | No public REST API for content. JSON endpoints exist only for the AI assistant (`/ai/*`), Command Center polling (`/internal/command-center/data|cameras|alerts|incidents|offices`), and the **LINE webhook** (`routes/line.php`). |
| 6 | Config system | Standard Laravel `.env` + `config/*.php`. Secrets present: `LINE_CHANNEL_*`, `GROQ_API_KEY`, `GOOGLE_MAPS_API_KEY`, DB credentials. `.env.example` / `.env.production.example` are maintained. |
| 7 | Hosting | **Render.com** (`render.yaml`) via **Docker** (`Dockerfile`, `docker/`). Local dev is **XAMPP**. `DEPLOYMENT_GUIDE.md` documents the Render flow. A Cloudflare tunnel is used for sharing (`npm run share`); `trustProxies` is already configured. |
| 8 | Public site structure | 35 Blade views under `resources/views/public/**` plus 14 components under `resources/views/components/public/**`, one layout (`public/layouts/app|header|footer`). ~40 routes in `routes/web.php` under the `public.` name prefix. |
| 9 | Internal portal structure | 71 Blade views under `resources/views/internal/**`, 35 controllers under `app/Http/Controllers/Internal/**`, ~120 routes in `routes/internal.php` under `/internal`. |
| 10 | Portal to data path | Direct Eloquent. Controllers validate with `$request->validate()`, call `Model::create/update`, write uploads with `Storage::disk('public')->put(...)`, and gate on `abort_unless($request->user()->hasPermission(...), 403)`. No API layer in between. |
| 11 | Editable content | See the inventory in section 2. |
| 12 | Admin actions | See the inventory in section 2. |
| 13 | Media/file handling | `storage/app/public/**` (public disk, symlinked at `public/storage`) for images: `articles/`, `home-divisions/`, `org-chart/members/`, `site/hero/`, `stations/`, `media/YYYY/`. Private PDFs live in `storage/app/private/forms/**` and are streamed through `FormDownloadController@download` (throttled 30/min) so the path is never public. A custom Media Library (`media`, `media_folders`, `media_tags`) exists but is **empty (0 rows)**. |
| 14 | Roles/permissions | 7 roles x 22 permissions, see #4. `users.station_id` ties staff to a `stations` row (used by Command Center). |
| 15 | Must NOT move to WordPress | Executive Dashboard (6 `dashboard_*` tables, CSV/print export), Command Center (CCTV cameras, incidents, operational alerts, live polling), internal Users/Roles/Permissions, audit logs, AI assistant conversation data, LINE subscriber/webhook data. Operational and security-sensitive, not editorial. |
| 16 | External services | LINE Messaging API, Groq LLM (AI assistant), Google Maps JS API, a geocoding service (`Services\Stations\GeocodingService`), Render, Cloudflare. |
| 17 | Security-sensitive | Internal login and password reset, RBAC gates, Command Center incident mutation (administrators only), LINE webhook HMAC verification (`VerifyLineSignature`, CSRF-exempt), private form downloads, Groq/LINE API keys. |

### Live data volumes (MySQL, at audit time)

```
articles 10   categories 20   faqs 8    public_forms 7    stations 84
org_chart_members 116 / tiers 22 / sections 6   ita_sections 2 / ita_cards 5
home_division_cards 6   guide_topics 6 / documents 25 / steps 30 / locations 16
smart_guides 48   assessment_recommendations 18   settings 11
media 0   menu_items 0   tags 0   article_attachments 0
```

Non-content: `ai_conversations 1988`, `command_center_cameras 25 / alerts 8 / incidents 5`,
`dashboard_* 37`, `users 8`, `roles 7`, `permissions 22`.

### Findings that shaped the plan

* **`stations` is dual-purpose.** Besides the public office directory it carries live Command Center
  columns (`ops_status`, `active_officers`, `online_cameras`, `active_incidents`) and is the target of
  `users.station_id`. It cannot simply become WordPress-owned.
* **Free-text search runs in SQL.** `Services\Search\SearchService` and
  `Services\Ai\KnowledgeRetrieverService` run `LIKE` queries across `articles`, `faqs` and
  `smart_guides`; the LINE bot uses the same retriever. Content therefore has to stay queryable
  **inside the Laravel database**.
* **Navigation is not currently editable.** The header and footer menus are hardcoded Blade with
  `route()` and `__()` calls. A `menu_items` table exists but has **0 rows and no admin screen**.
* **Two internal screens are inert.** `Settings > Appearance` persists `site.accent_color`, which
  nothing renders; `Settings > Languages` is a read-only report over `SetLocale::$supported`.
* **The custom Media Library is unused** (0 rows) — nothing to migrate, everything to replace.
* A sibling folder `C:\xampp\htdocs\id5-internal-portal` (a Next.js + FastAPI ITA prototype) exists
  **outside this git repository** and is out of scope.

---

## 2. Admin portal inventory (Phase 2)

Legend — **WP**: moves to WordPress · **KEEP**: stays in the Laravel internal portal · **DROP**:
removed, superseded or inert.

### 2.1 Content Management (`/internal/cms`)

| Feature | Code | Data | Public consumers | WordPress equivalent | Migration | Verdict |
|---|---|---|---|---|---|---|
| News CRUD + publish toggle | `Internal\Cms\NewsController` (extends `ArticleCrudController`), `views/internal/cms/news/*` | `articles` `type=news` (+ `categories`, `tags`, `article_attachments`) | `public/home`, `public/news/{index,show}`, `SearchService`, `KnowledgeRetrieverService` | Native **Posts** + Categories/Tags | 2 rows | Slugs are public URLs and must be preserved. **WP** |
| Announcements CRUD + toggle | `Internal\Cms\AnnouncementController` | `articles` `type=announcement` | homepage, `/announcements/*`, search | CPT **ประกาศ / Announcements** | 3 rows | **WP** |
| Alerts CRUD + toggle | `Internal\Cms\AlertController` | `articles` `type=alert` (+ `alert_level`) | homepage, `/alerts`, search | CPT **แจ้งเตือน / Alerts** with a *Severity* field | 2 rows | `alert_level` drives banner colour. **WP** |
| Knowledge base CRUD + toggle | `Internal\Cms\KnowledgeBaseController` | `articles` `type=kb_public` | `/knowledge/*`, search, AI | CPT **คลังความรู้ / Knowledge Base** | 3 rows | **WP** |
| CMS dashboard counters | `Internal\Cms\CmsDashboardController` | counts over `articles` | — | WordPress "At a Glance" | n/a | **DROP** |

All four share `ArticleCrudController`: bilingual title/excerpt/body (th/en/zh), slug, category, tags,
thumbnail upload, status (`draft|review|published|archived`), `is_public`, `is_pinned`, `priority`,
`published_at`, `expired_at`, `seo_title`, `seo_description`, soft deletes, `view_count`.

### 2.2 Website Management (`/internal/website-*`, `/internal/settings`)

| Feature | Code | Data | Public consumers | WordPress equivalent | Migration | Verdict |
|---|---|---|---|---|---|---|
| Homepage hero text (TH/EN title, subtitle, CTA) | `Internal\Website\HomepageController` | `settings` keys `home.hero_*` | `public/home/index.blade.php` | **Customizer → หน้าแรก (Homepage)** | settings rows | **WP** |
| Homepage hero image | `Internal\Settings\SiteSettingsController` | `settings['site.hero_image']` + `storage/app/public/site/hero` | `public/home` | Customizer image control | file + setting | **WP** |
| "Explore ID5 Services" division cards (6) | `Internal\Website\DivisionCardController` | `home_division_cards` (`key`, title/desc th+en, `photo_path`, `sort_order`) | `public/home` grid, `components/public/division-card` | CPT **การ์ดหน้าแรก / Homepage Cards** (edit-only, fixed `key`) | 6 rows + images | `key` maps to real routes and must never change. **WP** |
| Offices CRUD + CSV import + geocoding | `Internal\Website\ContactController`, `Services\Stations\{StationImportService,GeocodingService}` | `stations` | `/contact`, `/offices/*` (Google Maps), `/about`, Command Center, `users.station_id` | CPT **สำนักงาน / Offices**, public fields only | 84 rows | Ops columns and FKs stay Laravel-owned; sync writes a whitelist. CSV import and geocoding become an artisan command. **WP (partial)** |
| Downloadable forms | `Internal\Website\PublicFormController` | `public_forms` (private disk) | `/downloads`, `/about`, Smart Guide documents | CPT **แบบฟอร์มดาวน์โหลด / Forms** with a Media Library file | 7 rows + PDFs | Files stay non-public: Laravel keeps streaming them. **WP** |
| About-page documents | `Internal\Website\AboutDocumentController` | same `public_forms` table, `show_on_about_page` | `/about` | same CPT, "Show on About page" checkbox | — | **WP** |

### 2.3 Other content modules

| Feature | Code | Data | Public consumers | WordPress equivalent | Migration | Verdict |
|---|---|---|---|---|---|---|
| Organization chart | `Internal\OrgChart\OrgChartController` | `org_chart_sections` / `_tiers` / `_members` | `/about`, all 4 division pages | CPT **บุคลากร / People** + taxonomy **หน่วยงาน (Section)** + a *Tier* field | 6 + 22 + 116 rows, 116 photos | Three-level tree; tier ordering must survive. **WP** |
| ITA page | `Internal\Ita\ItaAdminController` | `ita_sections`, `ita_cards` | `/ita` | CPT **หัวข้อ ITA / ITA Items** + taxonomy **ITA Sections** | 2 + 5 rows | **WP** |
| FAQ | `Internal\Faq\FaqController` | `faqs` (+ `categories` `type=faq`) | `/faq`, search, AI, LINE | CPT **คำถามที่พบบ่อย / FAQ** + category taxonomy | 8 rows | **WP** |
| Smart Guide topics | `Internal\SmartGuide\GuideTopicController` | `guide_topics`, `guide_documents`, `guide_steps`, `guide_locations` | `/smart-guide/*`, assessment results | CPT **หัวข้อคู่มือ / Guide Topics** with repeater fields | 6 + 25 + 30 + 16 rows | Repeaters are the most complex mapping; `form_id`/`station_id` links must survive. **WP** |
| Smart Guide SW1H matrix | `Internal\SmartGuide\SmartGuideContentController` | `smart_guides` (persona x topic x dimension) | `/smart-guide/{persona}/{topic}`, AI, LINE | Who/What/Why/Where/When/How boxes on the Guide Topic screen, one group per persona | 48 rows | Persona and topic keys come from `config/`; the set is fixed. **WP** |
| Assessment result content | `Internal\Assessment\AssessmentRecommendationController` | `assessment_recommendations` | `/assessment/result` | CPT **ผลการประเมิน / Assessment Results** (edit-only, fixed `status_key`) | 18 rows | `status_key` must match `config/assessment/statuses.php`. **WP** |
| Media Library | `Internal\Media\MediaLibraryController` | `media`, `media_folders`, `media_tags` | none (portal-only picker) | Native **WordPress Media Library** | **0 rows, nothing to migrate** | **WP** |
| Site settings (name, hotline) | `Internal\Settings\GeneralSettingsController` | `settings['general.*']` | header/footer view composer, `/emergency` | **Customizer → ข้อมูลเว็บไซต์** | 2 keys | **WP** |
| Appearance (accent colour) | `Internal\Settings\AppearanceController` | `settings['site.accent_color']` | **nothing** | — | — | Inert. **DROP** |
| Languages | `Internal\Settings\LanguageController` | reads `lang/` files | — | — | — | Read-only report of a code-level fact. **DROP** |
| Navigation | *does not exist today* | `menu_items` (0 rows, unused) | hardcoded Blade | **Appearance → เมนู (Menus)** | none | **New capability.** The header falls back to today's hardcoded menu when no WordPress menu exists, so current appearance is preserved exactly. **WP** |

### 2.4 Stays in the Laravel internal portal

| Module | Why it must not move |
|---|---|
| Executive Dashboard (`/internal/executive/**`) | Operational statistics (KPIs, nationality/category/provincial splits, monthly trends) with CSV and print export plus an audit trail. Numeric reporting, not website content. |
| Command Center (`/internal/command-center/**`) | Live CCTV, incidents and operational alerts, polled continuously, permission-gated (`command-center.manage` is administrators only). Security-sensitive real-time operations. |
| Users / Roles / Permissions | Still required to authorise the two modules above. WordPress users are a separate population: website editors, not portal operators. |
| Audit logs (`/internal/audit-logs`) | Security record over `dashboard_audit_logs`. |
| Portal home, login, password reset | Entry point for the modules above. |
| AI assistant, LINE bot | Runtime subsystems. They *read* content but are not admin screens. |

---

## 3. WordPress content model (Phase 3)

Design rule: **administrators see Thai, human-readable labels, never a database field name.**

### 3.1 Native WordPress concepts, used first

| Need | WordPress native feature |
|---|---|
| News | **Posts**, with native Categories and Tags |
| Images and PDFs | **Media Library** |
| Navigation | **Appearance → Menus** |
| Site name, hero, footer, hotline | **Customizer** panels |
| Draft / published, scheduling, "pin" | **Post status**, **publish date**, **Make sticky** |
| SEO title and description | Two plain fields in a "การแสดงผลบนเครื่องมือค้นหา" box — no SEO plugin |
| Who may edit what | **Roles and capabilities** |
| Ordering | **Page attributes → Order**, plus drag-ordering inside repeaters |

### 3.2 Custom post types (registered by the `id5-cms` plugin)

| Post type | Menu label (TH / EN) | Replaces | Laravel target |
|---|---|---|---|
| `post` *(native)* | เรื่อง / Posts | News articles | `articles` `type=news` |
| `id5_announcement` | ประกาศ / Announcements | Announcements | `articles` `type=announcement` |
| `id5_alert` | แจ้งเตือน / Alerts | Alerts | `articles` `type=alert` |
| `id5_knowledge` | คลังความรู้ / Knowledge Base | Knowledge base | `articles` `type=kb_public` |
| `id5_faq` | คำถามที่พบบ่อย / FAQ | FAQ | `faqs` |
| `id5_form` | แบบฟอร์มดาวน์โหลด / Forms | Downloads + About documents | `public_forms` |
| `id5_office` | สำนักงานตรวจคนเข้าเมือง / Offices | Office directory | `stations` (public columns only) |
| `id5_person` | บุคลากร / People | Org chart members | `org_chart_members` (+ tiers, sections) |
| `id5_ita_card` | หัวข้อ ITA / ITA Items | ITA cards | `ita_cards` (+ `ita_sections`) |
| `id5_division_card` | การ์ดหน้าแรก / Homepage Cards | "Explore ID5 Services" grid | `home_division_cards` |
| `id5_guide_topic` | หัวข้อคู่มือ / Guide Topics | Smart Guide topics (documents, steps, locations) | `guide_topics`, `guide_*` |
| `id5_guide_content` | เนื้อหาคู่มือ / Guide Content | Smart Guide SW1H matrix, one entry per audience + topic | `smart_guides` |
| `id5_assessment` | ผลการประเมิน / Assessment Results | Assessment recommendations | `assessment_recommendations` |

### 3.3 Taxonomies

| Taxonomy | Applies to | Replaces |
|---|---|---|
| `category`, `post_tag` *(native)* | posts | `categories` `type=news`, `tags` |
| `id5_content_category` | announcements, alerts, knowledge, FAQ | `categories` of the matching type |
| `id5_org_section` | people | `org_chart_sections` |
| `id5_ita_section` | ITA items | `ita_sections` |
| `id5_form_category` | forms | `public_forms.category` |
| `id5_region`, `id5_province` | offices | `stations.region` / `.province` |

### 3.4 Bilingual content without a multilingual plugin

The Laravel schema is column-per-language (`*_th`, `*_en`, plus unused `*_zh`). WPML or Polylang would
model this as *separate posts per language*, doubling the content count, breaking the
one-row-per-article mapping, and adding a paid or heavyweight dependency.

Instead: **Thai is the native WordPress content** (post title, editor body, excerpt) and each screen
gets one extra box, *"ภาษาอังกฤษ / English version"*, holding the English title, summary and body.
That matches the existing `getLocalizedTitle()` fallback (`en ?: th`) exactly, keeps Thai editing
fully native, and needs no plugin. The `*_zh` columns are unused in the live data and are left
untouched by sync.

### 3.4.1 Classic editor, and which fields are rich text

Every synced content type opens in WordPress's **classic editor** (a core filter; no plugin). The
public website's article styles cover plain headings, paragraphs, lists, links and images, which is
exactly what the classic editor produces. The block editor's layout blocks would render correctly in
WordPress and incorrectly on the site.

Each field's type follows how the public Blade view prints it, verified per view:

| Printed as HTML (`{!! !!}`) → rich editor | Printed escaped (`{{ }}`) → plain text box |
|---|---|
| article bodies, FAQ answers, Smart Guide SW1H answers | ITA item content, assessment result text, addresses, descriptions |

Getting this wrong in either direction is a visible defect: HTML sent to an escaped view shows
literal tags, and formatted text sent through a plain box loses its lists and bold.

### 3.5 Plugin dependencies

**None.** Custom fields use core `register_post_meta()` and `add_meta_box()`, driven by one
declarative schema (`wordpress/plugins/id5-cms/includes/schema.php`) so that twelve content types
share a single renderer, a single REST serializer and a single validation path.

ACF was considered and rejected: repeaters require ACF Pro (paid), and the field definitions would
live in the WordPress database rather than in this repository, making the content model undeployable
and unreviewable.

### 3.6 Roles

| WordPress role | Who | Capabilities |
|---|---|---|
| **ผู้ดูแลเว็บไซต์ (Website Editor)** — `id5_editor` | day-to-day content administrators | create/edit/publish/delete all ID5 content types, upload media, manage menus and the Customizer panels. **No** plugin, theme, user, core-update or file-edit capability. |
| **Administrator** *(native)* | one or two technical owners | everything |

`DISALLOW_FILE_EDIT` is set, and the plugin hides Tools, Plugins and Themes from non-administrators.

---

## 4. Integration architecture (Phase 4)

### Decision

> **WordPress is the authoring layer. Content is pushed into the existing Laravel tables by a signed
> webhook plus a reconciling pull. The public Blade site keeps reading its own database.**

This is option **B/E — WordPress REST API + the existing backend, hybrid**.

```
Administrator
     |  edits and publishes
     v
WordPress admin  --(id5-cms plugin, on save/trash/delete/menu/customizer)--+
     |                                                                     | signed webhook
     |  canonical read endpoints  GET /wp-json/id5/v1/...                   | HMAC-SHA256
     v                                                                     v
Laravel  App\Services\Cms\WordPressClient  <---- pulls payload ---- POST /cms/webhook
     |
     |  mappers upsert by wp_post_id (idempotent, Thai-safe, ordering preserved)
     v
MySQL  articles - faqs - public_forms - stations - org_chart_* - ita_* -
       home_division_cards - guide_* - smart_guides - assessment_recommendations -
       settings - menu_items
     |
     v
Existing public Blade site, unchanged
```

Plus `php artisan cms:pull [--type=]` (scheduled reconcile and manual repair) and
`php artisan cms:push` (the one-time export of today's content into WordPress).

### Editing from the website itself

Administrators asked to edit the way they did on their previous WordPress site: open the website,
click what is wrong. The website is not a WordPress theme, so WordPress's Customizer cannot preview
it — but the same experience is provided by two cooperating pieces:

* **Edit mode on the website** (`App\Support\CmsEdit`, `@cmsEdit(...)` in the Blade views). For a
  browser holding a token signed by WordPress, the site draws a WordPress-style top bar and a pencil
  on each editable element. For everyone else it outputs nothing at all.
* **The site editor in WordPress** (`class-id5-site-editor.php`). The live website in a frame on
  the right, a panel on the left. A pencil click slides the ordinary WordPress edit form over the
  preview; saving publishes as usual and the preview reloads.

This adds no second way of storing or validating content — it is navigation. Every edit still goes
through the same WordPress screens, roles and publish-time checks.

### Editing the fixed wording

Content items were only half of what administrators see. Menu labels, headings, button text and
three long service guides were fixed in the code — 734 lines in language files, another ~450
strings typed straight into templates, and the assessment questions in a config file.

All of it is now editable from the same site editor, without turning any of it into "content":

* Text typed into templates was moved into the language files, unchanged. Every public page was
  compared before and after, byte for byte, in each language.
* A change made in WordPress is a single row layered over the language file
  (`App\Support\CmsText`, `CmsTranslator`); removing the row restores the built-in wording.
* In edit mode every printed line knows its own key, so hovering any text offers a pencil.

See `docs/wordpress-setup.md` §9.2 for the mechanics and the rules for new templates.

### Why not the alternatives

| Option | Verdict |
|---|---|
| **A / C — headless WordPress, fetched per request** | Rejected. `SearchService`, `KnowledgeRetrieverService` and the LINE bot run SQL `LIKE` across `articles`/`faqs`/`smart_guides`; `stations` is joined to Command Center data and `users.station_id`. Per-request REST would mean rewriting search, the AI retriever and the bot, and would make the public site fail whenever WordPress is slow or down. |
| **D — rebuild the site as a WordPress theme** | Rejected, and explicitly out of scope: it would discard 35 Blade views, 14 components, the Tailwind design system, the assessment wizard, the Smart Guide engine, the AI widget and the maps integration. |
| **B / E — WordPress authoring, synced into Laravel** | **Chosen.** |

### Scored against the seven required criteria

1. **Preserves the frontend** — public controllers, views, components, CSS and JS are untouched; they
   keep reading the same models.
2. **Minimises rewriting** — the only public-side change is the header's optional menu lookup, which
   falls back to the existing hardcoded markup.
3. **Familiar WordPress editing** — editors work entirely in `wp-admin`.
4. **Secure** — WordPress is never contacted by a visitor's browser; the webhook is HMAC-signed with
   a timestamp replay window; no WordPress credential reaches frontend JavaScript; private PDFs stay
   behind the existing throttled Laravel download route.
5. **Maintainable** — one declarative schema shared by the plugin and the Laravel mappers.
6. **No duplicated source of truth** — after cut-over WordPress is the only place content is edited.
   The Laravel tables become a **derived read model**: the internal CMS screens are deleted, and sync
   writes a per-type whitelist so Laravel-owned operational columns are never overwritten.
7. **Fits the deployment** — Laravel keeps deploying to Render unchanged; WordPress is a separate PHP
   app (any host) reachable over HTTPS, needed only at edit time.

### Ownership rules

| Column group | Owner | Written by sync? |
|---|---|---|
| Editorial fields (titles, bodies, images, ordering, visibility) | WordPress | yes |
| `articles.view_count`, `public_forms.download_count` | Laravel (runtime counters) | **no** |
| `stations.ops_status`, `active_officers`, `online_cameras`, `active_incidents` | Laravel / Command Center | **no** |
| `stations.lat`, `lng` | WordPress, but only when non-empty — geocoding stays an artisan tool | guarded |
| `users.*`, `roles`, `permissions`, `dashboard_*`, `command_center_*`, `ai_*`, `line_*` | Laravel | **no** |

### Failure handling (Phase 10)

The public site never calls WordPress at render time, so a WordPress outage has **zero** visitor
impact: the last synced content keeps serving. Webhook deliveries that fail are retried by the plugin
and reconciled by the scheduled `cms:pull`. Client calls use a configurable timeout (10s default) and
retries, log to a dedicated `cms` log channel, and never surface WordPress internals to visitors.

---

## 5. Migration strategy (Phase 7)

1. `php artisan cms:push --dry-run` — report what would be created in WordPress.
2. `php artisan cms:push` — create or update one WordPress post per existing row, stamping
   `_id5_legacy_id` and `_id5_legacy_type` meta and uploading every referenced image or PDF into the
   WordPress Media Library.
3. Re-running `cms:push` is **idempotent**: it matches on `_id5_legacy_id` and updates rather than
   duplicating.
4. `php artisan cms:pull` — pull everything back and write `wp_post_id` onto the Laravel rows,
   closing the loop so later webhooks map to the right record.
5. Preserved by design: Thai/Unicode (UTF-8 end to end), **slugs** (they are public URLs),
   `sort_order`, publish status, and `published_at` / `created_at`.
6. Steps that cannot be automated are listed in `docs/wordpress-setup.md`.

---

## 6. Removal plan (Phase 8)

Carried out only after the verification checklist in `docs/wordpress-setup.md` passes:

* `app/Http/Controllers/Internal/{Cms,Faq,Ita,Media,OrgChart,SmartGuide,Assessment,Website}/**`
* `app/Http/Controllers/Internal/Settings/{SiteSettingsController,GeneralSettingsController,AppearanceController,LanguageController}.php`
* the matching `resources/views/internal/{cms,faq,ita,media,org-chart,smart-guide,assessment,website}/**` and the four settings views
* the matching route groups in `routes/internal.php`
* internal-portal navigation entries pointing at them
* the `media`, `media_folders`, `media_tags` tables (0 rows, replaced by the WordPress Media Library)

**Deliberately kept** because other code still depends on them: `InternalAuthenticate`,
`InternalRedirectIfAuthenticated`, the auth controllers, `roles`/`permissions`/`User::hasPermission`,
`Setting`, every public model, `Services\Stations\*` (reused by the CSV import command),
`Services\Content\*` (still read by the public site), `DashboardAuditLog`, and all Executive Dashboard
and Command Center code.
