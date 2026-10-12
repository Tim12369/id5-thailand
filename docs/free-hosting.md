# Hosting the editors' WordPress for free

The public website already runs on Render's free plan. This puts WordPress
online next to it, also for free, so editors can log in from anywhere.

| Piece | Where | Cost |
|---|---|---|
| Public website (Laravel) | Render free web service — already running | free |
| Website database | Neon PostgreSQL — already running | free |
| **WordPress** | a second Render free web service, built from `wordpress/Dockerfile` | free |
| **WordPress database** | Aiven for MySQL, free plan (1 GB) | free, no card |

## How it copes with a free plan

Render's free services have **no lasting disk**: every file written is lost when
the service restarts, redeploys, or sleeps after 15 idle minutes. Databases are
the only thing that lasts, so everything that must survive lives in one:

* **WordPress uploads** are also stored in the MySQL database (table
  `wp_id5_files`, by the plugin's `ID5_Uploads_Store`) and written back to disk
  each time the service starts.
* **Pictures the website copies from WordPress** are also stored in PostgreSQL
  (table `cms_media_files`, `CMS_MEDIA_PERSIST=true`) and restored at start-up
  by `php artisan cms:media:restore`.
* **Plugins and themes** come from the Docker image only. Installing or
  updating them from the dashboard is switched off (`DISALLOW_FILE_MODS`),
  because it would silently vanish. To update WordPress or the plugin, change
  the repository and push.
* **Logins survive restarts**: WordPress's keys are derived from one secret
  (`ID5_WP_SECRET`) instead of being regenerated at each start.

## Setting it up

### 1. Create the MySQL database (Aiven)

1. Sign up at <https://aiven.io> (no credit card).
2. **Create service → MySQL → Free plan**, any region. Wait until it says *Running*.
3. On the service's **Overview** page, note the **Host**, **Port**, **User**
   (`avnadmin`), **Password** and **Database name** (`defaultdb`).

### 2. Create the WordPress service (Render)

1. Render dashboard → **New → Web Service** → the `id5-thailand` repository.
2. **Name** `id5-cms` · **Language** Docker · **Branch** `main` ·
   **Root Directory** `wordpress` · **Instance type** Free.
3. Add these environment variables, then **Create Web Service**:

| Variable | Value |
|---|---|
| `WORDPRESS_DB_HOST` | Aiven host and port joined by a colon, e.g. `mysql-abc.aivencloud.com:12345` |
| `WORDPRESS_DB_USER` | `avnadmin` |
| `WORDPRESS_DB_PASSWORD` | the Aiven password |
| `WORDPRESS_DB_NAME` | `defaultdb` |
| `ID5_WP_SECRET` | a long random value (64 characters); never change it afterwards |
| `ID5_PUBLIC_SITE_URL` | `https://id5-thailand.onrender.com` |
| `ID5_WEBHOOK_SECRET` | a second long random value, shared with the website (step 4) |

The service's address (for example `https://id5-cms.onrender.com`) is its
WordPress address; Render supplies it to the container automatically. Until
step 3 is done it only says that the site is being set up.

### 3. Copy the working WordPress into it

On the computer that has the local WordPress, with its database running:

```
php wordpress/hosting/tools/send-site.php ^
    --from=C:/xampp/htdocs/id5-wordpress ^
    --to="mysql://avnadmin:PASSWORD@HOST:PORT/defaultdb" ^
    --wp-url=https://id5-cms.onrender.com ^
    --site-url=https://id5-thailand.onrender.com ^
    --credentials=C:/xampp/id5-hosting-passwords.txt
```

It copies every table and every uploaded file, rewrites the local addresses to
the online ones, and gives **every account a new random password**, written to
the file named by `--credentials`. It only reads from the local install, and it
refuses to overwrite a database that already holds a WordPress unless
`--replace` is added.

Then restart the `id5-cms` service once in Render (**Manual Deploy → Restart**)
so it puts the uploaded files on its disk.

### 4. Connect the public website

In Render, on the **`id5-thailand`** service → **Environment**, add:

| Variable | Value |
|---|---|
| `CMS_ENABLED` | `true` |
| `CMS_WORDPRESS_URL` | `https://id5-cms.onrender.com` |
| `CMS_WORDPRESS_USER` | the sync account's user name (`id5-sync`) |
| `CMS_WORDPRESS_APP_PASSWORD` | that account's application password — the same value as in the local `.env` |
| `CMS_WEBHOOK_SECRET` | the same value as `ID5_WEBHOOK_SECRET` above |
| `CMS_MEDIA_PERSIST` | `true` |
| `CMS_RETIRE_UNMANAGED` | `true` |

`CMS_RETIRE_UNMANAGED` matters on this site: its database was filled with
sample data before WordPress existed, so without it every news story, FAQ,
person and ITA topic would appear twice — the old sample row and the WordPress
one. With it, a full sync retires the rows no WordPress item owns (news goes to
the trash and FAQs are hidden, both recoverable; people and ITA topics are
removed). Offices, forms and homepage cards are matched by their own codes and
simply taken over.

### 5. Send everything across

Log in to WordPress as the administrator → **ตั้งค่า → การเชื่อมต่อเว็บไซต์ ID5** →
**ส่งเนื้อหาทั้งหมดอีกครั้ง**. The website fetches all content, pictures, menus
and wording; allow one to three minutes, then open the public website.

From then on the website updates by itself whenever an editor presses
**เผยแพร่**. The same button is the manual "make the website match WordPress
again" for any later doubt — this host has no scheduler to do it hourly.

## What to expect on a free plan

* **Waking up.** After 15 idle minutes a service sleeps; the next visit takes
  about a minute. For editors that is the first page after a break. If the
  public website happens to be asleep when an editor publishes, the change
  arrives a minute or two later (WordPress retries by itself).
* **Shared hours.** A Render workspace gets 750 free instance hours per month
  for all its free services together, and suspends them all for the rest of
  the month when they run out. One service awake around the clock uses about
  730. Sleeping costs nothing, so two lightly used services fit comfortably —
  but do not point an uptime monitor at either address, and watch
  **Billing → Usage** if traffic grows. Creating the WordPress service in a
  separate Render workspace gives it its own 750 hours.
* **Space.** The MySQL database holds 1 GB, including every uploaded picture in
  the sizes WordPress keeps (a typical photo takes 0.3–1.5 MB). PostgreSQL on
  Neon's free plan holds 0.5 GB, including the website's copies of the pictures
  it shows. Photos larger than 1920 px are scaled down on upload. Deleting a
  picture in the Media Library frees its space.
* **Uploads up to 16 MB** per file.
* **Idle database.** Aiven may power off a free service that goes unused for a
  long time, after an e-mail warning; it can be powered on again from its
  console and nothing is lost.

## Changing it later

* **A new WordPress or plugin version:** change `wordpress/Dockerfile` (the
  `FROM wordpress:…` line) or the plugin, and push. Both services redeploy.
* **Moving to a paid host with a real disk:** nothing has to change. The
  database copies simply become redundant; they can be switched off by removing
  `CMS_MEDIA_PERSIST` and the `ID5_PERSIST_UPLOADS` line in
  `wordpress/hosting/wp-config.php`.
