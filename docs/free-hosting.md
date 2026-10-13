# How the editors' WordPress is hosted for free

Editors log in at **`https://<the website>/wp/wp-admin/`** — for the live site,
<https://id5-thailand.onrender.com/wp/wp-admin/>. WordPress runs inside the same
free Render service as the public website and keeps its data in the same free
Neon database. There is no second service, no second account, and nothing to
set in a hosting panel: pushing to `main` is the whole deployment.

## The pieces

| Piece | Where |
|---|---|
| Public website (Laravel) | the Render web service, at `/` |
| WordPress for editors | the same container, at `/wp` (`Alias` in `docker/apache-vhost.conf`) |
| Website data | Neon PostgreSQL |
| WordPress data | one SQLite file in the container, mirrored into the same PostgreSQL |
| Uploaded files | the container's disk, mirrored into the same PostgreSQL |

WordPress runs on SQLite through the WordPress project's own *SQLite Database
Integration* driver, so it needs no MySQL server.

## Why everything is mirrored into PostgreSQL

Render's free services have **no lasting disk**: every file is lost when the
service restarts, redeploys, or goes to sleep after 15 idle minutes. The
database is the only thing that lasts, so everything that must survive is kept
there and put back when the service starts (`docker/entrypoint.sh`):

* **WordPress's database** — `docker/wordpress/sqlite-sync.php` saves the SQLite
  file into the tables `wp_sqlite_blocks` / `wp_sqlite_meta` whenever it
  changes, a few seconds after each edit and once more when the service is
  stopped. Only the 64 KB blocks that changed are sent, in one transaction, so
  what is saved is always a complete database.
* **WordPress's uploads** — the plugin's `ID5_Uploads_Store` writes every
  Media Library file into the table `wp_id5_files`.
* **The pictures the website copies from WordPress** — `MediaVault`
  (`CMS_MEDIA_PERSIST`) writes them into `cms_media_files`.

Safeguards worth knowing about:

* If the saved database cannot be read at start-up, WordPress stays **offline**
  (a "try again in a minute" page) instead of starting empty — an empty site
  would overwrite the saved one. The public website is not affected.
* While a new version is starting, the old one is still running. The newer
  instance takes ownership of the saved database; the older one notices and
  stops saving, so it cannot overwrite newer data.

## Nothing to configure

`docker/entrypoint.sh` works out everything that connects the two halves:

* addresses follow from `APP_URL` (WordPress is `APP_URL` + `/wp`);
* the secret they share, the application password the website signs in with,
  and WordPress's own keys are all **derived from `APP_KEY`**, which the
  service already has. Logins therefore survive restarts, and no secret is
  stored anywhere new;
* the website and WordPress talk to each other over the local web server
  (`CMS_INTERNAL_URL`, `App\Services\Cms\InternalRoute`), not out to the
  public address and back.

Any of `CMS_ENABLED`, `CMS_WORDPRESS_URL`, `CMS_WORDPRESS_USER`,
`CMS_WORDPRESS_APP_PASSWORD`, `CMS_WEBHOOK_SECRET`, `CMS_MEDIA_PERSIST`,
`CMS_RETIRE_UNMANAGED` can still be set explicitly in the environment to
override what is worked out.

## How the site came to exist

The first time the service started with nothing saved, `docker/wordpress/boot.php`:

1. created an empty WordPress;
2. filled it from **`docker/wordpress/seed/`** — the content of the WordPress
   this project was developed with, written by
   `docker/wordpress/tools/make-seed.php`;
3. gave every account a new random password;
4. had the website fetch everything from it once (`cms:pull`), retiring the
   sample rows the website's database was originally filled with
   (`CMS_RETIRE_UNMANAGED`), so nothing appears twice.

After that the seed is never read again: the site lives in the database and
editors change it online.

The repository is public, so the seed holds **content only** — no passwords or
password hashes, no e-mail addresses, no login sessions, and settings only from
an explicit list (see the top of `make-seed.php`).

### The first logins

Nobody chose the first passwords, and they are never printed in a log. They are
stored encrypted with a public key (`docker/wordpress/maintainer-public.pem`);
only the matching private key, which exists on the maintainer's computer and
nowhere else, can open them:

```
php docker/wordpress/tools/read-first-logins.php ^
    --url=https://id5-thailand.onrender.com/wp ^
    --key=C:/xampp/id5-maintainer-private.pem ^
    --out=C:/xampp/id5-wordpress-logins.txt
```

Each person should change their password after the first login (**Profile →
New password**). Further editors are added in WordPress (**Users → Add new**,
role *Website Editor*) by the administrator.

## Day to day

* Editors work at `/wp/wp-admin/`, or open **แก้ไขเว็บไซต์** to click and edit
  the website itself. Changes reach the public website within seconds.
* **ตั้งค่า → การเชื่อมต่อเว็บไซต์ ID5 → ส่งเนื้อหาทั้งหมดอีกครั้ง** makes the
  website fetch everything again, for any doubt that the two are out of step.
* Plugins and themes cannot be installed from the dashboard
  (`DISALLOW_FILE_MODS`): the files would vanish at the next restart. To
  change the WordPress version or the plugin, edit the repository and push.

## What a free plan means in practice

* **Waking up.** After 15 idle minutes the service sleeps; the next visit —
  a visitor's or an editor's — takes about a minute while it starts and puts
  its files back.
* **750 hours a month** of running time for the whole Render workspace. One
  service can run around the clock inside that; do not add an uptime monitor
  that would keep a second free service awake as well.
* **0.5 GB** in the Neon database for everything: the website's data,
  WordPress's database, and both copies of every picture. Roughly a hundred or
  two photographs; deleting pictures from the Media Library frees their space.
  Photos larger than 1920 px are scaled down on upload, and uploads are limited
  to 16 MB each.
* **A deployment replaces the running copy.** An edit made in the minute a new
  version is starting can be lost; avoid pushing code while editors are working.

## Moving to a host with a real disk later

Nothing has to change: the database copies simply become a backup. On a host
with MySQL, the plugin alone (`wordpress/plugins/id5-cms`) installs into any
ordinary WordPress — see `docs/wordpress-setup.md`.
