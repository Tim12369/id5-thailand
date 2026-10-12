# ID5 CMS (WordPress plugin)

Turns a WordPress install into the editing layer for the ID5 @ Thailand 5.0 public website.

This plugin is **source**, kept in the Laravel repository so the content model is reviewable and
deployable. Copy the directory into `wp-content/plugins/` to install it. See
`docs/wordpress-setup.md` in the repository root for the full procedure.

## What it does

| | |
|---|---|
| Registers 12 custom post types and 6 taxonomies with Thai labels, on the classic editor | `class-id5-post-types.php` |
| Renders the editor fields for all of them from one declarative schema | `class-id5-schema.php`, `class-id5-fields.php` |
| Refuses to publish an item the website could not use (missing key, duplicate code), with a Thai notice | `class-id5-fields.php` |
| Adds Customizer panels for site-wide text, the homepage hero and the footer | `class-id5-customizer.php` |
| Serves read endpoints under `id5/v1` for the website to pull from | `class-id5-rest.php`, `class-id5-serializer.php` |
| Accepts the one-time content import from the existing database | `class-id5-import.php` |
| Notifies the website, with an HMAC signature, whenever content changes | `class-id5-webhook.php` |
| Creates a locked-down "Website Editor" role | `class-id5-roles.php` |
| Registers the two menu locations and tidies the admin for editors | `class-id5-admin.php` |
| The site editor: live website on the right, editing panel on the left, pencils on the page | `class-id5-site-editor.php`, `assets/site-editor.*` |
| Editable wording: menu labels, headings, buttons — stored as changes over the website's built-in text | `class-id5-texts.php`, `assets/site-editor-texts.js` |

## Dependencies

**None.** No Composer packages, no bundled JavaScript libraries, no other plugins. Fields use core
`register_post_meta()` and `add_meta_box()`; the repeater UI uses jQuery and jQuery UI Sortable,
both already shipped by WordPress.

ACF was considered and rejected: repeaters require ACF Pro, and field definitions would live in the
WordPress database rather than in version control.

## Adding or changing a field

Edit `includes/class-id5-schema.php` only. The editor screen, the REST payload and the import all
read from it, so a one-line change there is enough on the WordPress side.

Then add the matching column write in the corresponding Laravel mapper under
`app/Services/Cms/Mappers/`.

## Endpoints

All of `id5/v1` requires an authenticated user with `edit_posts`, except `/health`.

| Method | Route | Purpose |
|---|---|---|
| GET | `/wp-json/id5/v1/health` | Unauthenticated liveness probe |
| GET | `/wp-json/id5/v1/site` | Everything: content, terms, menus, settings |
| GET | `/wp-json/id5/v1/collection/<type>` | One content type |
| GET | `/wp-json/id5/v1/post/<id>` | One item |
| GET | `/wp-json/id5/v1/settings` | Customizer values |
| GET | `/wp-json/id5/v1/menus` | Header and footer menus |
| GET | `/wp-json/id5/v1/terms` | All taxonomy terms |
| POST | `/wp-json/id5/v1/import` | One item, from the migration command |
| POST | `/wp-json/id5/v1/import/settings` | Customizer values, from the migration command |

## Outbound notifications

On save, trash, delete, menu change, term change or Customizer save, the plugin POSTs a small JSON
body to the configured website URL:

```
X-ID5-Timestamp: 1759800000
X-ID5-Signature: <hex HMAC-SHA256 of "<timestamp>\n<body>">
```

The body names what changed but carries no content — the website then pulls the canonical payload
over an authenticated connection. Failed deliveries retry three times with increasing delays; after
that the website's own hourly reconcile picks the change up.

## Guardrails

* **Fixed sets.** Homepage cards, guide topics and assessment results declare `fixed_set` in the
  schema. Creating or deleting one requires `manage_options`, enforced through the post type's own
  capabilities, so the buttons disappear and direct URLs are refused for editors.
* **Validation.** Fields marked `required` or `unique` (and types with `unique_together` or
  `required_taxonomies`) are checked on publish. A failing item is kept as a draft and the editor is
  told what to fix.
* **Text is not rewritten.** The serializer applies `wpautop` and shortcodes only — never
  `wptexturize`, which would turn `don't` into a curly-quote entity and `4x6` into `4×6`.

## Uninstalling

Deactivating keeps all content and the role; the custom post types simply stop being registered, and
reactivating restores them. The plugin registers no uninstall hook, so it never deletes content.
