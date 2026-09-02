# EMREC TPMS — orientation for agents

Laravel 13 tender + project management system (grew out of a tender aggregator).
Read `README.md` first. Key structure:

- **Enums** `app/Enums/` — every status/role/priority is a backed enum with
  `label()`, `color()`, `options()` (via `EnumHelpers`).
- **Model concerns** `app/Models/Concerns/` — `HasDueDate` (countdowns),
  `HasOptimisticLock` (`updateWithLock($data, $expectedVersion)`),
  `HasComments` / `HasAttachments` (polymorphic), `LogsAudit`
  (`$model->audit($event, $old, $new)`; auto "created"), `RollsUpProgress`
  (cached `progress`, driven by `ProgressRollupObserver`).
- **RBAC** — `config/permissions.php` catalog + role defaults; gates registered
  in `AppServiceProvider`; per-user grants/revokes in `permission_overrides`.
  Check with `->middleware('can:x')`, `$user->can('x')`, `@can`.
- **State machines** — `TenderStateMachine`, `ServiceRequestStateMachine`
  (`apply($model, $toState, $actor, $note)`); transitions defined on the enums.
- **Promotion** — `ProjectInitiator::fromTender()` / `fromServiceRequest()`.
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
