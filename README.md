# Tender & Development-Aid Aggregator

A personal dashboard that pulls tender/procurement notices from multiple
sources (World Bank, ReliefWeb, TANePS) into one searchable MySQL-backed
Laravel Blade app.

This is a **complete Laravel 13 app**. The framework files
(`public/index.php`, `bootstrap/`, `config/`, `artisan`, etc.) are
scaffolded in alongside the app-specific code (models, controllers,
views, services, migrations, console command).

## Setup

1. **Install dependencies:**
   ```bash
   composer install
   ```

2. **Configure your environment** — copy `.env.example` to `.env`,
   adjust the `DB_*` values for your MySQL server (create the
   `tender_aggregator` database first), and set `ADMIN_EMAIL` /
   `ADMIN_PASSWORD` to whatever you want the first account to be. Then:
   ```bash
   php artisan key:generate
   php artisan migrate
   php artisan db:seed
   ```
   `migrate` creates the `users`, `cache`, `jobs`, `sessions`, and
   `tenders` tables; `db:seed` runs the `UserSeeder` to create the admin
   account (see "Authentication" below). `SESSION_DRIVER` / `CACHE_STORE`
   / `QUEUE_CONNECTION` default to `database`; switch them to `file` /
   `sync` in `.env` if you'd rather not back them with MySQL.

## Authentication (multi-user, login required)

Every page except `/login` requires a logged-in session — the `auth`
middleware group in `routes/web.php` handles this, so both API-fetched
tenders and ones added manually by any user are only visible to signed-in
users.

This uses Laravel's **built-in** `Auth` facade directly (no
Breeze/Jetstream/Fortify package needed) — `AuthController.php` plus
`resources/views/auth/login.blade.php` are the whole login flow.

**There is no public registration.** Every account is created by the
seeder:

- The admin account comes from `ADMIN_NAME` / `ADMIN_EMAIL` /
  `ADMIN_PASSWORD` in `.env`. Set those, then:
  ```bash
  php artisan db:seed --class=UserSeeder
  ```
- To add the rest of your team, fill in the `$team` array in
  `database/seeders/UserSeeder.php` and re-run the command above.
  Accounts are matched by email, so re-running never duplicates or
  overwrites an existing user.
- One-offs can still be created by hand:
  ```bash
  php artisan tinker
  >>> \App\Models\User::create(['name' => 'Mukhusin', 'email' => 'you@example.com', 'password' => 'a-strong-password']);
  ```
  (`password` is hashed automatically by the `User` model's cast.)

## Who added what

The `tenders` table has a nullable `user_id`:
- **API-fetched rows** (World Bank, ReliefWeb, TANePS) have `user_id = null`
  — they belong to the system, not a person.
- **Manually-added rows** (via "+ Add opportunity", e.g. from LinkedIn)
  are stamped with whoever added them, and the dashboard shows "Added by
  {name}" so your team knows the source.

4. **Pull in tenders**:
   ```bash
   php artisan tenders:fetch
   # or just one source while you're testing:
   php artisan tenders:fetch --source=world_bank
   ```

5. **Run it**:
   ```bash
   php artisan serve
   ```
   Visit `http://localhost:8000` — you'll see the dashboard with search,
   source filter, country filter, and open/closed toggle.

## Sources

All source settings live in `config/tenders.php` (env-overridable). A
source that isn't enabled or is missing its credentials/URL is **skipped**
by `tenders:fetch`, not treated as an error.

- **World Bank** (`app/Services/WorldBankTenderService.php`) — real open
  API, works out of the box. `WORLD_BANK_QUERY` is a full-text `qterm`
  filter (defaults to `Tanzania`; blank = every country). Covered by
  `tests/Feature/WorldBankTenderServiceTest.php`.
- **ReliefWeb** (`app/Services/ReliefWebTenderService.php`) — real API
  (v2; v1 was decommissioned). Since **2025-11-01 it rejects unknown
  `appname`s with a 403**, so you must
  [request a pre-approved appname](https://apidoc.reliefweb.int/parameters#appname)
  and set `RELIEFWEB_APPNAME`. Until then the source is skipped. Filtered
  to Tanzania via `RELIEFWEB_COUNTRY`.
- **TANePS** (`app/Services/TanepsTenderService.php`) — **no public API**,
  and the dashboard at `taneps.go.tz/#/website/tender-notice` is a
  JavaScript SPA that never reaches the server (that's why the old
  version timed out). The classic PPS platform underneath still renders
  plain HTML under `/epps/...`; find that listing URL in a browser, set
  `TANEPS_LISTING_URL`, and adjust the table selectors in the service.
  Skipped until `TANEPS_LISTING_URL` is set.

## Manual "quick add" (for LinkedIn, WhatsApp, email, etc.)

LinkedIn (and most closed platforms — WhatsApp groups, private newsletters)
can't be auto-fetched: no public API, and scraping LinkedIn specifically
violates their Terms of Service. Instead, there's a **"+ Add opportunity"**
button in the header that opens a small form — paste the title, the
LinkedIn post URL, deadline, etc., and it's stored with `source = manual`
so it shows up in search/filters right alongside the auto-fetched ones.

Good complements for catching these without scraping:
- A **Google Alert** for terms like `"funding opportunity" Africa
  site:linkedin.com`, or for specific org names you follow.
- Following the specific pages/people (e.g. Global Grants and
  Opportunities for Africa) directly on LinkedIn so you get notified,
  then using "+ Add opportunity" to save the ones worth tracking.

## Adding a new source

1. Create `app/Services/YourSourceTenderService.php` implementing
   `TenderSourceInterface` (`fetch()`, `sourceKey()`, `isEnabled()`).
2. Add its settings block to `config/tenders.php`.
3. Register it in the `$sources` array in
   `app/Console/Commands/FetchTenders.php`.
4. Run `php artisan tenders:fetch --source=your_source_key` to test it
   in isolation.

Good next candidates, in rough order of ease:
- **TED (EU Tenders Electronic Daily)** — has a real API
  (`https://api.ted.europa.eu`), similar shape to the World Bank service.
- **PPRA Tanzania** — publishes tender bulletins; likely needs scraping
  like TANePS.
- **UNGM** — partial public data; check their current terms before
  scraping.

## Scheduling automatic refresh

`routes/console.php` schedules `tenders:fetch` to run **every 6 hours**
(configurable via `TENDERS_FETCH_CRON` in `.env`), in the timezone set by
`APP_TIMEZONE`. The run is non-overlapping, backgrounded, and its output
is appended to `storage/logs/tenders-fetch.log`; a failure is also noted
in `storage/logs/laravel.log`.

Check what's registered and when it next runs:
```bash
php artisan schedule:list
```

Laravel's scheduler still needs the OS to invoke it once a minute. Pick one:

- **Server (cron)** — add a single crontab entry (`crontab -e`):
  ```
  * * * * * cd /home/mukhusin-siraji/Dev/outside/tender-aggregator && php artisan schedule:run >> /dev/null 2>&1
  ```
- **Local dev** — run a long-lived worker in a spare terminal (no cron needed):
  ```bash
  php artisan schedule:work
  ```
- **Run it right now**, ignoring the schedule:
  ```bash
  php artisan tenders:fetch
  ```

## Alerts (not built yet, but the model supports it easily)

Since every tender row is deduped by `(source, external_id)`, a simple
next step is: after `tenders:fetch` upserts, check for rows created in
the last run matching a saved keyword/country, and email or Telegram
yourself. Happy to build that next once the core fetchers are working
against real data.



git init
git commit -m "first commit"
git branch -M main
git remote add origin git@github.com:mukhusin/management-system.git
git push -u origin main