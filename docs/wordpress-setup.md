# WordPress setup and operations

**Written for:** the developer or system administrator who installs and maintains this system.
Content editors want `docs/admin-wordpress-guide.md` instead.

This document covers installing the WordPress editing layer, connecting it to the public website,
migrating the existing content, deploying, and rolling back.

Background on *why* the system is built this way is in `docs/wordpress-migration-plan.md`.

---

## 1. How the two systems fit together

```
Administrator  ->  WordPress (wp-admin)  --signed webhook-->  Laravel  ->  MySQL  ->  public site
                           ^                                     |
                           +----------- pulls content -----------+
```

* **WordPress** is where content is written. Visitors never touch it.
* **Laravel** keeps serving the public website from its own database, exactly as before.
* When something is published in WordPress, the plugin sends a small signed notification; Laravel
  answers at once, then pulls the content and writes it into its own tables. No queue worker is
  needed for this.
* An hourly `cms:pull` reconciles anything a notification missed.
* Editors can work from the website itself: the **site editor** in WordPress shows the live site
  with a pencil on everything editable (see section 9.1).

**If WordPress is down, the public website is unaffected.** It never calls WordPress while rendering
a page.

---

## 2. Requirements

| | Minimum |
|---|---|
| WordPress | 6.4 or newer |
| PHP (for WordPress) | 8.1 or newer |
| Database (for WordPress) | MySQL 5.7+ / MariaDB 10.3+ — **its own database**, separate from `id5_thailand` |
| HTTPS | required in production, both ways |
| Laravel app | unchanged: PHP 8.2+, MySQL, a running queue worker |

WordPress may live on the same server as Laravel or a different one. The only requirements are that
Laravel can reach WordPress over HTTPS, and WordPress can reach Laravel's `/cms/webhook`.

---

## 3. Install WordPress

1. Install WordPress normally (`wp-admin` setup wizard, or WP-CLI).
2. Give it its **own database**. Do not point it at `id5_thailand`.
3. In `wp-config.php`, before the "That's all" line, add:

   ```php
   // This install is an editor, not a website — nobody edits code through it.
   define( 'DISALLOW_FILE_EDIT', true );
   define( 'DISALLOW_FILE_MODS', false ); // leave false so core security updates still apply

   // Application Passwords require HTTPS unless this is a local dev install.
   // define( 'WP_ENVIRONMENT_TYPE', 'local' );
   ```

4. **Settings → General**: set the site title, the timezone (`Asia/Bangkok`) and **Site Language →
   ไทย**. The administrator guide refers to every menu by its Thai name, so the admin must be in
   Thai. (WordPress downloads the language pack itself when you pick it.)
5. **Settings → Reading**: tick *Discourage search engines from indexing this site*. The editing
   install must never compete with the real website in search results.
6. **Settings → Permalinks**: choose *Post name*. The REST API needs pretty permalinks.
7. **Delete WordPress's sample content.** A fresh install ships with a "Hello world!" post, a
   "Sample Page" and a sample comment. The post is a real post, so the sync would publish it to the
   website as a news article. Move all three to the trash and empty it before going further.

### Required plugins

**None.** `id5-cms` has no third-party dependencies. Do not install an SEO, multilingual or custom
fields plugin: the content model already covers all three, and adding one would create a second place
the same information lives.

---

## 4. Install the ID5 CMS plugin

The plugin source lives in this repository at `wordpress/plugins/id5-cms/`.

```bash
# From the repository root, copy it into the WordPress install:
cp -r wordpress/plugins/id5-cms /path/to/wordpress/wp-content/plugins/
```

Then in **Plugins → Installed Plugins**, activate **ID5 CMS**.

Activation registers the content types and creates the **ผู้ดูแลเว็บไซต์ (Website Editor)** role. It
does not create, modify or delete any content.

You should now see these in the WordPress sidebar: ข่าวสาร, ประกาศ, แจ้งเตือน, คลังความรู้,
คำถามที่พบบ่อย, แบบฟอร์มดาวน์โหลด, สำนักงานตรวจคนเข้าเมือง, บุคลากร, หัวข้อ ITA, การ์ดหน้าแรก,
หัวข้อคู่มือ, เนื้อหาคู่มือ, ผลการประเมิน.

All of them open in WordPress's **classic editor** (the plugin switches the block editor off for
these content types — no Classic Editor plugin is needed). The public website styles plain headings,
paragraphs, lists, links and images; block layouts would look right in WordPress and wrong on the
site.

**Pictures.** An item's cover picture (featured image) and every picture or PDF placed in its text
with **เพิ่มสื่อ** are copied into the website's own storage when the item syncs
(`MediaMirror::mirror()` and `MediaMirror::mirrorHtml()`), and the text is pointed at the copy. A
visitor's browser therefore never contacts WordPress. Only files under the Media Library address
WordPress reports (`uploads_url` in the payload) are copied; pictures hot-linked from other websites
are left as written. The rich-text styles, including WordPress's left/centre/right alignment and
captions, are the `.prose` rules in `resources/css/app.css` — run `npm run build` after changing
them.

---

## 5. Create the sync account

Laravel reads content as a WordPress user. Give it its own account so its access can be revoked
without touching a person's login.

1. **Users → Add New**
   * Username: `id5-sync`
   * Role: **Editor** (it needs `edit_posts` and `upload_files`, nothing more)
2. Open that user's profile, scroll to **Application Passwords**.
3. Name it `Laravel sync` and press **Add New Application Password**.
4. Copy the generated password **now** — WordPress shows it once.

This is an Application Password, not the account password: it can be revoked on its own, it carries
only that user's capabilities, and it cannot be used to log in to `wp-admin`.

---

## 6. Configure the Laravel side

Add to the Laravel `.env` (see `.env.example` for the full list):

```dotenv
CMS_ENABLED=true
CMS_WORDPRESS_URL=https://cms.example.go.th
CMS_WORDPRESS_USER=id5-sync
CMS_WORDPRESS_APP_PASSWORD="xxxx xxxx xxxx xxxx xxxx xxxx"
CMS_WEBHOOK_SECRET=<a long random string>
```

Generate the shared secret with:

```bash
php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"
```

Then:

```bash
php artisan migrate        # adds the wp_post_id mapping columns
php artisan config:clear
php artisan cms:status     # should report: enabled, credentials present, reachable: yes
```

**Never** put any of these values in frontend JavaScript, a Blade template, or `VITE_*`. Anything
prefixed `VITE_` is compiled into the public bundle.

---

## 7. Configure the WordPress side

In WordPress, **Settings → การเชื่อมต่อเว็บไซต์ ID5**:

| Field | Value |
|---|---|
| URL ของเว็บไซต์สาธารณะ | `https://www.example.go.th` — the real public website |
| ที่อยู่สำหรับแจ้งการเปลี่ยนแปลง | `https://www.example.go.th/cms/webhook` |
| รหัสลับสำหรับลงลายเซ็น | the same `CMS_WEBHOOK_SECRET` as above |

The same screen shows when the last notification succeeded and the last error, which is the fastest
way to diagnose a connection problem.

---

## 8. REST API and CORS

The public website calls WordPress **server to server**, never from a browser, so **no CORS
configuration is needed** and none should be added.

The plugin's `id5/v1` endpoints all require an authenticated user with `edit_posts`, except
`/wp-json/id5/v1/health`, which returns only the plugin version.

If a security scan flags WordPress's own `/wp-json/wp/v2/users` endpoint, you may block it in the
reverse proxy. The integration does not use it.

Recommended reverse-proxy rules for the WordPress host:

```nginx
# Only the sync server and office network need wp-admin / wp-login / the REST API.
location ~ ^/(wp-admin|wp-login\.php|wp-json) {
    allow 203.0.113.0/24;   # office
    allow 198.51.100.10;    # Laravel server
    deny all;
    # ... existing PHP handling ...
}
```

---

## 9. Roles

| Role | Who | What they can do |
|---|---|---|
| **ผู้ดูแลเว็บไซต์ (Website Editor)** | content administrators | everything in `docs/admin-wordpress-guide.md`: write, edit, publish and delete content, upload media, manage menus and the Customizer panels |
| **Editor** | the `id5-sync` account only | read content through the REST API |
| **Administrator** | one or two technical owners | everything, including plugins, themes and users |

Website Editors deliberately **cannot** install plugins, switch themes, edit files, manage users or
update WordPress core. They also cannot create or delete the fixed-set content types (homepage cards,
guide topics, assessment results), because the public website renders a specific set of those and a
missing one would leave a hole on a live page.

Give day-to-day staff the **Website Editor** role, not Administrator.

### 9.1 The site editor and "edit mode"

WordPress → **แก้ไขเว็บไซต์** opens a Customizer-style screen: the real website on the right, an
editing panel on the left. `https://<website>/edit` is a shortcut to it.

How it works, and why it is safe:

* WordPress gives the logged-in editor a token valid for 12 hours, signed with the webhook secret.
  The website, on seeing a valid token, switches into *edit mode* for that browser: it draws a
  WordPress-style bar and a pencil on each editable element, and tells the editor screen what is on
  the page.
* **The token changes nothing by itself.** It only decides whether pencils are drawn. Every edit
  still happens in WordPress, behind WordPress's login and role checks.
* **Visitors are unaffected.** Without a valid token the website outputs none of the edit-mode
  markup, styles or script — verified byte-for-byte in the browser tests.
* Edit-mode responses are sent with `Cache-Control: no-store` and
  `Content-Security-Policy: frame-ancestors 'self' <WordPress origin>`, so a page with pencils is
  never cached for a visitor and can only be framed by your WordPress install.

Requirements: the WordPress and website hostnames should share a parent domain (for example
`cms.example.go.th` and `www.example.go.th`). If they do not, the editor still works — the token is
carried in the frame's URLs — but the black bar will not persist when an editor browses the public
site in a separate tab.

`/wp-admin` and `/wp-login.php` on the *website* redirect to WordPress, because administrators type
them out of habit.

### 9.2 Editable wording (menu labels, headings, buttons)

Everything a visitor reads that is not a content item — navigation labels, section headings,
button text, the visa / TM30 / 90-day guides, the assessment questions, the AI assistant's built-in
answers — can be changed from the site editor, in Thai and in English.

**Where the wording lives.** The built-in text stays in `resources/lang/{th,en}` (and, for the
assessment wizard, `config/assessment`). An editor's change is one row: in WordPress in the
`wp_id5_texts` table, mirrored on the website in `cms_texts`. `App\Support\CmsTranslator` layers
those rows over the language files when a file is loaded, so placeholders, lists and fallbacks
behave exactly as before. Deleting a row — which is what "use the original wording" does — brings
the built-in text back. With no rows the website renders byte for byte as it did without this
feature.

**How WordPress knows what exists.** It asks the website: `GET /cms/texts`, signed with the webhook
secret, returns every editable line with its built-in wording. Nothing else can read that route.
The answer is cached in WordPress for five minutes.

**How a change travels.** Saving in the panel stores the row and sends a `texts.updated`
notification; the website pulls `GET /wp-json/id5/v1/texts` and replaces `cms_texts` with it. The
hourly `cms:pull` does the same.

**What keeps it safe.**

* Every line is printed escaped (`{{ }}`), so nothing typed can become markup. The exception is the
  handful of lines whose key ends in `_html`, printed with `{!! !!}`; those are reduced to inline
  formatting (`strong`, `em`, `br`, `a`, `span` …) by WordPress on save **and again** by the website
  on receipt (`CmsText::clean()`), whatever WordPress let through.
* A line handed to JavaScript is printed with `@jsText(__('key'))`, which writes quotes and angle
  brackets as `\u` escapes. Never write `'{{ __('key') }}'` inside an Alpine attribute or a
  `<script>`: an apostrophe in the wording would end the string and break the widget.
* A line that contains a placeholder (`:count`) cannot be saved without it.
* Lines the website does not have are ignored on receipt, as is wording identical to the built-in
  text.
* A WordPress that has never had a line saved and returns none (a fresh or rebuilt install) does
  not wipe the changes held by the website.

**Deleting.** A post deleted or trashed in WordPress announces itself and is removed from the
website. A *term* — an ITA section, an org-chart unit, a category — has nothing left to announce
it, so `CmsSyncService::syncTerms()` compares the website's linked rows with WordPress's complete
term list and removes the ones that are gone (with the topics or people inside them; articles in a
deleted category become uncategorised). It runs on every `terms.updated` notification and in the
hourly pull. Nothing is removed when the list cannot be trusted: a taxonomy missing from the
answer, or an empty list while linked rows exist here. In WordPress, a section that items are
required to belong to cannot be deleted while it still holds published items
(`ID5_Site_Editor::protect_sections_in_use`). The site editor shows a **ลบรายการนี้** button above
any open item the user may delete.

**Commands.**

```bash
php artisan cms:pull --texts   # pull the changed wording now
php artisan cms:push --texts   # send the website's copy back to a rebuilt WordPress
php artisan cms:push --terms   # create in WordPress the categories it does not have yet
```

**Edit mode.** For an editor each line printed on the page carries an invisible tag (zero-width
characters) naming its key; the edit-mode script reads the tag, removes it, and puts a pencil on
the element. Tags are added only to pages, only in edit mode, and the state is reset on every
request. Visitors never receive them — there is a test for exactly that.

**When you add or change a template.**

* Put every visible string in a language file and print it with `__()`. Hard-coded text has no
  pencil.
* Name a key `…_html` only if the template prints it with `{!! !!}`, and only for inline markup.
* Add a `@cmsEdit($model)` marker **on the same line as** the element it belongs to
  (`@cmsEdit($x)<div …>`), not on a line of its own, so visitors' HTML does not change.
* A line that nothing prints any more should be added to `config/cms_texts.php` (or deleted), so
  editors are not offered wording that appears nowhere.

---

## 10. Migrate the existing content

This moves everything already in `id5_thailand` into WordPress. Run it once, at cut-over.

**Back up the database first.** The pull in step 3 writes to the content tables, and a backup is the
only thing that lets you compare before and after, or go back.

```bash
# 0. Back up. (Any method — mysqldump, your host's snapshot, phpMyAdmin export.)
mysqldump -u <user> -p id5_thailand > id5_thailand-before-wordpress.sql

# 1. See what would happen. Writes nothing.
php artisan cms:push --dry-run

# 2. Do it.
php artisan cms:push

# 3. Pull it back, which records each WordPress post ID against its row and
#    proves the round trip works.
php artisan cms:pull
```

`cms:push` is **idempotent**. WordPress matches each item on the database row it came from, so
running it again updates the same posts instead of creating duplicates. It never deletes anything
from either system.

What is preserved: Thai and other Unicode text, **slugs** (they are public URLs), ordering,
publish/draft status, publish dates, images and PDFs, category and tag assignment, the organisation
chart's section/row structure, the Smart Guide's documents, steps and locations, and the formatting
(bold, lists) of its per-audience guidance.

What is deliberately **not** migrated: articles that were already deleted in the old admin stay
deleted.

What changes, harmlessly: WordPress adds a line break between HTML blocks (`</p><p>` becomes
`</p>` + newline + `<p>`). Browsers render the two identically.

### What needs checking by hand afterwards

1. **Smart Guide per-audience guidance.** It arrives under **เนื้อหาคู่มือ**, one entry per
   audience + topic with six formatted answers each. Open a few and confirm the bold text and bullet
   lists are intact.
2. **Office opening hours.** These were stored as free-form JSON and are mapped onto seven day
   fields. Spot-check a few offices.
3. **Homepage hero image.** Confirm it appears in **Customize → ข้อมูลเว็บไซต์ → หน้าแรก**.
4. **Navigation menus.** These never existed before, so nothing is imported. Until you build one in
   **Appearance → Menus**, the website keeps using its existing built-in navigation — which is the
   safe default. Build a menu only when you actually want to change the navigation.
5. **Any content with no `code`.** Forms and offices are keyed by code; a row without one is skipped
   and reported in `storage/logs/cms.log`.

---

## 11. Verify before removing anything

Work through this list before trusting the migration.

**Connection**
- [ ] `php artisan cms:status` reports enabled, credentials present, reachable: yes
- [ ] Settings → การเชื่อมต่อเว็บไซต์ ID5 shows a recent successful send

**Round trip**
- [ ] Edit a news headline in WordPress, press Update, reload the public news page — the new headline
      is there within a few seconds (with the queue worker running)
- [ ] Set a post to Draft — it disappears from the public listing
- [ ] Replace a homepage card's image — the new image appears on the homepage
- [ ] Add an FAQ — it appears on `/faq`
- [ ] Upload a new PDF to a form — `/downloads` serves the new file and the URL is still
      `/downloads/{id}/download`, not a direct file link

**Content**
- [ ] Row counts match: compare `php artisan cms:status` against the WordPress "At a Glance" panel
- [ ] Thai text renders correctly everywhere — no `???` or mojibake
- [ ] Public URLs are unchanged (check a few `/news/<slug>` links)
- [ ] The organisation chart on `/about` and the four division pages looks the same as before

**Public site**
- [ ] Desktop, tablet and mobile layouts unchanged
- [ ] Search still returns articles, FAQs and guides
- [ ] The AI assistant and the LINE bot still answer with current content
- [ ] `/offices` map still plots every office

**Engineering**
- [ ] `php artisan route:list` has no errors
- [ ] `php artisan test` passes
- [ ] `npm run build` succeeds
- [ ] `storage/logs/cms.log` has no errors from the last pull

---

## 12. Deployment

Nothing about the Laravel deployment changes: same `Dockerfile`, same `render.yaml`, same build.

One operational requirement, and one option:

1. **The scheduler must be running**, for the hourly `cms:pull` safety net:
   ```bash
   * * * * * cd /path/to/app && php artisan schedule:run >> /dev/null 2>&1
   ```
2. **A queue worker is optional.** By default (`CMS_SYNC_MODE=after_response`) the web process that
   receives a notification applies the change itself, right after replying to WordPress — so there
   is no worker whose silent death would stop edits appearing. If an immediate sync fails it is
   put on the queue as a retry; a worker, if you run one, picks that up, and otherwise the hourly
   reconcile does. Set `CMS_SYNC_MODE=queue` only if you run a supervised
   `php artisan queue:work` and prefer all syncing to happen there.

Add the new environment variables to the hosting panel (on Render: *Environment* →
*Environment Variables*). `CMS_WORDPRESS_APP_PASSWORD` and `CMS_WEBHOOK_SECRET` are secrets — never
commit them.

Deploy WordPress like any other PHP application: keep core, the `id5-cms` plugin and the database
backed up, and apply WordPress security updates promptly.

---

## 13. Rolling back

The design makes rollback cheap, because the public website reads its own database either way.

**To pause syncing** (content freezes at its current state; the website keeps working normally):

```dotenv
CMS_ENABLED=false
```
```bash
php artisan config:clear
```

**To disconnect WordPress entirely**: clear `ที่อยู่สำหรับแจ้งการเปลี่ยนแปลง` in
Settings → การเชื่อมต่อเว็บไซต์ ID5. WordPress stops notifying; the website keeps serving the last
synced content indefinitely.

**To restore the old internal CMS screens**: they were removed in a single commit. Revert it, run
`php artisan route:clear`, and the old screens return. Nothing in the data model was dropped — the
migration only *added* nullable columns, so old and new code read the same rows.

**To undo the database changes**:

```bash
php artisan migrate:rollback --step=1
```

This drops only `wp_post_id`, `cms_synced_at`, `wp_term_id`, `cms_key` and the `cms_sync_state`
table. No content is touched.

---

## 14. Running it locally

For development on one machine, WordPress can run on PHP's built-in server. Use the router shipped
in this repository — without it the built-in server does not redirect `/wp-admin` to `/wp-admin/`,
and every link in the WordPress sidebar then returns **404 Not Found**:

```bash
php -S 127.0.0.1:8081 -t /path/to/wordpress /path/to/id5-thailand/wordpress/dev-router.php
```

Always open the admin as `/wp-admin/` (with the trailing slash). A real web server (Apache, Nginx)
adds it for you; this only matters for the built-in server.

Two processes must be running for an edit to reach the website: the Laravel app and WordPress
(plus their databases). No queue worker is needed.

**Shut databases down cleanly.** A MariaDB/MySQL server that is killed mid-write — by closing its
window, ending the task, or powering off — can corrupt its data files. This happened to the local
WordPress database during development and it had to be rebuilt. Stop it with
`mysqladmin shutdown` (on the development machine, `C:\xampp\stop-id5.bat` does this and takes a
backup first). In production use a managed database or a proper service, and back it up.

**If the WordPress database is ever lost**, the website keeps running — it serves its own copy.
Restore WordPress from the most recent dump, then run `php artisan cms:push` followed by
`php artisan cms:pull`: the push re-creates anything newer than the dump from the website's copy.

---

## 15. Troubleshooting

| Symptom | Cause | Fix |
|---|---|---|
| Every WordPress sidebar link gives **404 Not Found** | the admin was opened as `/wp-admin` (no slash) on PHP's built-in server | use `wordpress/dev-router.php` (section 14), or open `/wp-admin/` |
| An editor sees a red *"ยังไม่ได้เผยแพร่"* notice | a required field is empty or a code is already in use | this is the plugin's validation doing its job — the notice lists what to fix |
| WordPress shows *Error establishing a database connection* | its database server is not running, or its files were damaged by an unclean stop | start it; if it will not start, restore as described in section 14 — the public website is unaffected meanwhile |
| An editor changed some wording but the website still shows the old text | the notification did not arrive | `php artisan cms:pull --texts`; check `cms:status` |
| The wording panel says it cannot reach the website | WordPress cannot call `<website>/cms/texts` (firewall, wrong *public site URL*, or the two secrets differ) | fix the connection settings; the panel keeps working from the last list it received |
| The black bar / pencils do not appear on the website | not logged in to WordPress, or the 12-hour edit token expired | open WordPress → **แก้ไขเว็บไซต์** again |
| `cms:push` warns that settings were not exported | the sync account is an Editor and may not change site-wide settings | expected; set them in the site editor, or run once with an administrator's application password |
| `cms:status` says *reachable: no* | wrong URL, or the REST API is blocked | Open `https://<wp>/wp-json/id5/v1/health` in a browser — it should return JSON |
| Edits do not reach the website | no queue worker running | Start `php artisan queue:work`; `cms:pull` will catch up meanwhile |
| WordPress shows a webhook error | secret mismatch, or the URL is wrong | Confirm `CMS_WEBHOOK_SECRET` matches exactly on both sides |
| `401 Unauthorized` in `cms.log` | Application Password revoked or mistyped | Generate a new one for `id5-sync` |
| Webhook rejected, *timestamp outside the replay window* | server clocks differ by more than 5 minutes | Fix NTP on both servers |
| Images do not appear on the public site | the file could not be downloaded from WordPress | Check `storage/logs/cms.log`; confirm Laravel can reach the WordPress uploads URL |
| An item is skipped every sync | it has no business key (form/office `code`, card `key`) | Fill that field in WordPress |

Logs: `storage/logs/cms.log` (Laravel side), Settings → การเชื่อมต่อเว็บไซต์ ID5 (WordPress side).
