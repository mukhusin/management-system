# EMREC TPMS — orientation for agents

Laravel 13 tender + project management system (grew out of a tender aggregator).
Read `README.md` first. Key structure:

- **Enums** `app/Enums/` — every status/role/priority is a backed enum with
  `label()`, `color()`, `options()` (via `EnumHelpers`).
- **Model concerns** `app/Models/Concerns/` — `HasDueDate` (countdowns),
  `HasOptimisticLock` (`updateWithLock($data, $expectedVersion)`),
  `HasComments` / `HasAttachments` (polymorphic), `LogsAudit`
  (`$model->audit($event, $old, $new)`; auto "created"), `RollsUpProgress`
  (cached `progress`, driven by `ProgressRollupObserver`),
  `HasOwners` (many-to-many `owners()` via `<model>_owners`; `ownedBy` scope).
- **Ownership** — Tender, Project use `HasOwners`; Task has `assignees()`
  (`task_assignees` pivot, `assignedTo` scope). ServiceRequest / TrackerItem
  / Subtask keep a single `owner_id` / `assignee_id`. Controllers sync from
  `owner_ids[]` / `assignee_ids[]`; `partials/_owner_picker` renders the boxes.
- **RBAC** — `config/permissions.php` catalog + role defaults; gates registered
  in `AppServiceProvider`; per-user grants/revokes in `permission_overrides`.
  Check with `->middleware('can:x')`, `$user->can('x')`, `@can`.
- **Tenders** — `Tender::opportunities()` (external, `adopted_at` null) vs
  `->adopted()` (pipeline). `$tender->adopt($user)` crosses over; the state
  machine / promotion only apply to adopted tenders. `TenderController::index`
  = pipeline, `opportunities` = feed, `pursue` = adopt.
- **State machines** — `TenderStateMachine`, `ServiceRequestStateMachine`
  (`apply($model, $toState, $actor, $note)`); transitions defined on the enums.
- **Promotion** — `ProjectInitiator::fromTender()` / `fromServiceRequest()`;
  also seeds `scope_items` by splitting the tender/request scope text.
- **Traceability** — `ScopeItem` (project) <-> `Task` via `scope_item_task`;
  `Project::scopeCoverage()`. Requirements attach to a `Phase` (`phase_id`) or
  stay project-level (`phase_id` null).
- **Hierarchy** — Project → **Phase** → Milestone → Feature Set → Task → Sub-task.
  A `Phase` has its own description, `assignees()`, requirements (`scopeItems()`)
  and milestones; SDLC projects auto-seed 5 phases (`Phase::seedSdlc`). Progress
  rolls up through every level. `Project::currentPhase()` = first not-signed-off
  phase. **Phase gates** — `config/projects.php` (`ENFORCE_PHASE_GATES`),
  `PhaseController::signOff` writes the gate fields onto the phase row +
  audit `phase_signed_off`; `Project::workAllowedInPhase(Phase)`.
- **Editor** — `partials/_editor.blade.php` enhances `textarea[data-editor]`
  (comments); endpoints `/mentions`, `POST /comments/preview`. Layout provides
  `@stack('foot')` + csrf meta.
- **Audit** (`audit_logs`) is append-only — the model throws on update/delete.
- Tests run against MySQL `tender_aggregator_test` (no `pdo_sqlite` on this box).
- Ingestion (`app/Services/*TenderService`, `tenders:fetch`) is unchanged from
  the aggregator; don't break `WorldBankTenderServiceTest`.

The full plan (phases 1–3) lives in
`~/.claude/plans/agile-doodling-stardust.md`.

---

<laravel-boost-guidelines>
# Laravel Application

This repository contains a Laravel application. Complete the following setup before working on the user's request.

## Prerequisites

Verify that PHP and Composer are available:

```sh
php -v
composer -V
```

If either command is unavailable, detect the user's operating system and install the prerequisites with the appropriate command:

macOS:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/mac/8.5)"
```

Windows PowerShell:

```powershell
Set-ExecutionPolicy Bypass -Scope Process -Force; [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.ServicePointManager]::SecurityProtocol -bor 3072; iex ((New-Object System.Net.WebClient).DownloadString('https://php.new/install/windows/8.5'))
```

Linux:

```sh
/bin/bash -c "$(curl -fsSL https://php.new/install/linux/8.5)"
```

After installation, ask the user to restart their terminal. If the agent needs the restarted shell to continue, ask the user to reopen their terminal and rerun their original prompt.

## Agent Setup

Install Laravel Boost from the application root before making application changes:

```sh
composer require laravel/boost --dev
php artisan boost:install
```

Boost replaces these bootstrap instructions with guidelines tailored to the application. After installation, read `AGENTS.md` again and continue with the user's original request using the generated guidelines.
</laravel-boost-guidelines>
