# EMREC TPMS — Tender & Project Management System

A Laravel 13 back-office for EMREC Consulting: track **tenders** through their
lifecycle, log inbound **service requests**, turn won opportunities into
**SDLC projects** with a milestone → feature-set → task → sub-task breakdown,
and keep everything else on a generic **business tracker**. Role-based access,
an immutable audit trail, optimistic concurrency, threaded comments with
`@mentions`, and file attachments throughout.

> This grew out of a tender aggregator. The World Bank ingestion
> (`php artisan tenders:fetch`) still works and feeds the Tenders module.
> Phased roadmap — Phase 2: requirements traceability + stage-gate UI;
> Phase 3: Laravel Reverb real-time + fault-tolerant ingestion workers +
> TED/UNGM sources.

## Setup

```bash
composer install
cp .env.example .env          # set DB_*, ADMIN_*, TEAM_MEMBER_PASSWORD
php artisan key:generate
php artisan migrate --seed
php artisan tenders:fetch     # optional: pull World Bank procurement notices
```

`migrate --seed` runs `ServiceLineSeeder` (EMREC's 10 service lines),
`UserSeeder` (the admin + 4 team accounts), and — outside production —
`TrackerSeeder` (imports `database/seeders/data/emrec-tracker.csv`).

`SESSION_DRIVER` / `CACHE_STORE` / `QUEUE_CONNECTION` default to `database`;
switch to `file` / `sync` in `.env` to avoid backing them with MySQL.

## Roles & permissions

Four roles, seeded on the accounts above:

| Role | Can |
| --- | --- |
| `system_admin` | everything (incl. user & service-line management, audit log) |
| `tender_officer` | ingest / register / edit / transition tenders, log & progress service requests, tracker |
| `project_manager` | initiate & run projects, manage the work breakdown, tracker, comments, audit |
| `dev_member` | comment, update tasks/sub-tasks assigned to them |

Every ability in `config/permissions.php` is a Laravel Gate. An admin can
**grant or revoke individual permissions per user** on the Team → Edit screen
(stored in `permission_overrides`); `system_admin` always passes via
`Gate::before`.

There is **no public registration** — the admin creates accounts (a random
temporary password is shown once).

## The modules

- **Tenders** (`/tenders`) — state machine `Draft → Under Review → Submitted →
  Won → Lost/Cancelled`; only legal transitions are offered and each is
  written to the audit log. Editing a tender's value/deadline needs
  `tenders.edit_baseline` and is audited. A **Won** tender can be promoted to
  a project in one click, inheriting client / scope / budget / deadline.
- **Service Requests** (`/service-requests`) — inbound enquiries with their own
  machine `New → Qualified → Quoted → Won → Engaged`, `Declined`/`Lost` exits.
  A **Won** request promotes to a project and moves to `Engaged`.
- **Projects** (`/projects`) — `type` is `sdlc` (uses the 5 stage-gated
  phases) or `engagement` (milestone-only). Work breaks down
  **Milestone → Feature Set → Task → Sub-task**; ticking a sub-task rolls
  progress up to the project (cached `progress` columns, kept current by
  `ProgressRollupObserver`). `My Work` (`/my-work`) lists your tasks.
- **Tracker** (`/tracker`) — the EMREC Master Business Tracker: category,
  owner, status, priority, progress, next action, due date, remarks.
  Import a spreadsheet at `/import` or `php artisan tracker:import <file.csv>`
  (idempotent — rows matched by `EMREC-###`).
- **Reports** (`/reports`) — tender & service-request funnels, win rates,
  projects by status, workload per person, pipeline & delivery by service line,
  overdue tasks.
- **Audit** (`/audit`, `audit.view` only) — append-only log of state changes,
  baseline edits, project initiations, comments and attachments. Rows cannot
  be updated or deleted.

## Concurrency

Concurrently-edited records carry a `lock_version`. Edit forms submit it in a
hidden field; if someone else saved first, the update is rejected with
"changed by someone else — reload and try again" instead of silently
overwriting (`App\Models\Concerns\HasOptimisticLock`).

## Comments, mentions, attachments

Tenders, service requests, projects, tasks and tracker items all support:

- **Markdown comments** (`Str::markdown`, unsafe HTML stripped). Mention a
  colleague with `@their.name` or `@their@email` — they get a database
  notification (bell in the header, `/notifications`).
- **File attachments** on the `local` disk (`config/attachments.php` for the
  size/extension rules).

## Tender ingestion (World Bank)

Unchanged from the aggregator. `config/tenders.php` holds the source settings;
`php artisan tenders:fetch` upserts, `routes/console.php` schedules it. World
Bank works out of the box; ReliefWeb needs a pre-approved `appname`; TANePS
needs a listing URL (both skip cleanly until configured).

## Tests

```bash
php artisan test
```

Runs against MySQL `tender_aggregator_test` (`phpunit.xml`) — switch those two
`DB_*` lines back to `sqlite` / `:memory:` if your PHP has `pdo_sqlite`.
Coverage: RBAC + overrides, both state machines, optimistic locking, audit
immutability, project initiation & data inheritance, progress roll-up,
mentions/notifications, the CSV importer, and page smoke tests per role.
