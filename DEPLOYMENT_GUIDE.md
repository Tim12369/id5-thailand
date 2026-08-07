# Deploying ID5 @ Thailand 5.0 for Free, 24/7 Access

This gets the site reachable at a public URL any time, using two free
services in combination:

- **Render** — runs the Laravel app itself (free web service, no credit card)
- **Neon** — hosts the Postgres database (free tier that does not expire,
  unlike Render's own free Postgres, which is deleted after 90 days)

**The one tradeoff**: both free tiers sleep after a period of no traffic
(Render's web service after 15 min, Neon's database after 5 min) and wake up
automatically on the next visit, which can take 30-60 seconds. After that
first visit, it's fast again until it goes idle once more. Aside from that,
this stays up permanently at no cost.

**What's already prepared for you** (in this project):
- `Dockerfile`, `docker/apache-vhost.conf`, `docker/entrypoint.sh` — builds
  and runs the app the way Render expects
- `render.yaml` — Render service definition
- `.env.production.example` — reference for which environment variables to set

---

## Step 1 — Put the project on GitHub

Render deploys from a GitHub repo. From the project folder:

```powershell
git init
git add .
git commit -m "Initial commit"
```

Then create a new **private** repository on GitHub (github.com/new), and
push:

```powershell
git remote add origin https://github.com/YOUR_USERNAME/id5-thailand.git
git branch -M main
git push -u origin main
```

## Step 2 — Create the free Neon database

1. Go to neon.tech and sign up (no credit card needed).
2. Create a new project — any name, any region close to Thailand (e.g.
   Singapore) is fine.
3. On the project dashboard, open **Connection Details** and copy the
   **pooled connection** values — host, database name, username, password.
   Keep this tab open, you'll need it in Step 4.

## Step 3 — Create the free Render web service

1. Go to render.com and sign up (no credit card needed).
2. **New → Web Service**, connect your GitHub account, select the
   `id5-thailand` repo.
3. Render should detect `render.yaml` automatically and propose a Docker
   web service — confirm the plan is **Free**.

## Step 4 — Set the environment variables

In the Render dashboard, on your new service, open the **Environment** tab
and add these (values in parentheses are where to find them):

- `APP_KEY` — generate one locally by running `php artisan key:generate --show`
  in this project, and paste the `base64:...` output
- `APP_URL` — your Render service's URL, shown at the top of the dashboard
  (e.g. `https://id5-thailand.onrender.com`)
- `DB_HOST`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD` — from Neon's
  Connection Details (Step 2)
- `LINE_CHANNEL_ID`, `LINE_CHANNEL_SECRET`, `LINE_CHANNEL_ACCESS_TOKEN`,
  `GOOGLE_MAPS_API_KEY`, `GROQ_API_KEY` — copy these from your local `.env`

(`render.yaml` already sets the non-secret values like `DB_CONNECTION=pgsql`,
`APP_ENV=production`, etc. — you only need to fill in the ones above.)

## Step 5 — Deploy

Render deploys automatically once the environment variables are saved. The
first build takes a few minutes (installs PHP/Node dependencies, builds
frontend assets, runs migrations). Watch the **Logs** tab for progress.

## Step 6 — Visit it

Open the Render URL from Step 4. That's the link to give your supervisor —
it works 24/7. If it's been idle a while, the very first load may take
30-60 seconds while Render and Neon wake back up; after that it's normal
speed.

---

## Keeping it updated later

Whenever you make changes locally:

```powershell
git add .
git commit -m "Describe the change"
git push
```

Render automatically rebuilds and redeploys on every push to `main`.
Database migrations run automatically on each deploy (see
`docker/entrypoint.sh`).

## If something doesn't load

- **Build fails on Render**: check the **Logs** tab — usually a missing
  environment variable or a PHP/Node dependency issue.
- **500 error after a successful deploy**: check **Logs** for the Laravel
  error; most often a missing/incorrect `DB_*` or `APP_KEY` value.
- **CSS/JS missing**: the asset build step in the Dockerfile failed — check
  build logs for `npm run build` errors.
- **"This page isn't working" right after a long idle period**: this is the
  expected wake-up delay — wait ~30-60 seconds and reload.
