# Uncommitted Working-Tree Triage — QuickerFaster UI Library

> **Branch**: `decoupling`
> **Scope**: Read-only triage of the uncommitted changes remaining after the three Organization-refactor commits.
> **Rule applied**: [`library-vs-module-boundary-analysis.md`](library-vs-module-boundary-analysis.md) §7 — library = domain-agnostic mechanism (contracts, engines, scopes, middleware, traits, config seams with no business nouns); module = business entity/noun.

---

## 1. Full Picture

| Measure | Value |
|---|---|
| Total porcelain entries (`git status --short --porcelain | wc -l`) | **2,455** |
| `vendor/` entries | 2,347 (1,062 modified + 1,285 deleted) |
| `docs/` entries | 71 (41 deleted + 30 untracked) |
| `src/` entries | 21 (11 modified + 10 untracked) |
| `dependencies/` entries | 8 (7 modified/deleted + 1 untracked) |
| `tests/` entries | 4 (1 modified + 3 untracked) |
| Other | `composer.json`, `composer.lock`, `public/assets/css/*`, `.gitignore`, `phpunit.xml` |

The single dominant fact: **96% of the noise is `vendor/` drift** (2,347 of 2,455 entries).

---

## 2. Categorized Report

| Path / Group | Category | Rationale | Action |
|---|---|---|---|
| `vendor/` (2,347 entries) | **DISCARD** | `git diff --stat vendor/` = `2347 files changed, +37,238 / −217,695`. Pure composer update/lock drift, not library code. `.gitignore:23` already lists `/vendor`. | `git checkout -- vendor/` then `git rm -r --cached vendor/` (see §3.1) |
| `composer.lock` | **DISCARD** | Lock-file drift tied to the same composer update that touched `vendor/`; `composer.json`'s autoload-dev addition does **not** require a lock change. | `git checkout -- composer.lock` |
| `composer.json` | **KEEP** | Adds only `autoload-dev` → `QuickerFaster\UILibrary\Tests\` ⇒ `tests/`. Required for the new test suite. | commit |
| `docs/` renames (41 D + 30 ??) | **KEEP** | Legitimate restructure `docs/architecture/*` → `docs/{library,consuming-app,project}/*` (+ new `docs/README.md`, `docs/project/media-management-boundary-analysis.md`). Complete and consistent (see §3.3). | commit (stage deletions + new files) |
| `.gitignore` | **KEEP** | Standard Laravel ignores incl. `/vendor`, `.env`, `/node_modules`. Supports untracking vendor. | commit |
| `phpunit.xml` | **KEEP** | PHPUnit 10 config bootstrapping `tests/` against Testbench + in-memory DB. | commit |
| `src/Config/ui-library.php` | **KEEP** | Adds `discovery` toggles, `tenancy` seam (`company_id`/`current_company_id`), `workspace_resolver` key, doc-path fix. Domain-agnostic. (See note §3.4.) | commit |
| `src/Providers/*` (2 files, +67/−73) | **KEEP** | `ModuleServiceProvider` refactored to delegate listener/report/workflow discovery to `DiscoveryRegistrar`; `UILibraryServiceProvider` binds `WorkspaceResolver`/`CompanyProvider` from config. Decoupling work. | commit |
| `src/Http/Livewire/DataTables/*` (2 files) | **KEEP** | Dispatches the new `DataTableRecordSaved` event on create/update/delete/restore; fixes `replacePlaceholders()` to recurse arrays. Domain-agnostic extension point. | commit |
| `src/Jobs/*` (3 files) | **KEEP** | `ExportChunk`/`GenerateExport`/`ProcessImportChunk` now read `ui-library.tenancy.column`/`session_key` instead of hardcoded `company_id`/`current_company_id`. Decoupling. | commit |
| `src/Services/AccessControl/AccessControlPermissionService.php` | **KEEP** | Idempotent `firstOrCreate` seeding; per-module `permissions.php` config (`only`/`except`/`extra`/`actions`); discovery of permission names. Domain-agnostic. (Flag pre-existing `use App\Models\User;` — §3.5.) | commit |
| `src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php` | **KEEP** | Merges auto-discovered business-module notification templates via `NotificationDiscoveryService`. | commit |
| `src/Attributes/` (`ReportType.php`) | **KEEP** | `#[ReportType]` attribute — domain-agnostic declaration seam. | commit |
| `src/Console/Commands/DiscoverCommand.php` | **KEEP** | `ui-library:discover` — debuggable summary of auto-discovery. | commit |
| `src/Events/DataTableRecordSaved.php` | **KEEP** | Generic lifecycle event (domain-agnostic payload: old/new record, model FQCN, action). | commit |
| `src/Http/Middleware/ResolveCompanyContext.php` | **KEEP** | Tenancy middleware implementing the `CompanyProvider` contract seam. | commit |
| `src/Listeners/DataTableRecordListener.php` | **KEEP** | Abstract base listener with no business nouns. | commit |
| `src/Scopes/` (`CompanyScope.php`) | **KEEP** | Config-driven tenant global scope (two-domain test passes). | commit |
| `src/Services/Discovery/` (`DiscoveryRegistrar.php`) | **KEEP** | Convention-based auto-discovery of `app/Modules/*` assets; fully domain-agnostic (see §3.6). | commit |
| `src/Services/Notifications/NotificationDiscoveryService.php` | **KEEP** | Domain-agnostic notification discovery. | commit |
| `src/Traits/HasCompanyScope.php` | **KEEP** | Trait registering `CompanyScope`; no business nouns. | commit |
| `src/Traits/Workflows/` (`HasWorkflow.php`) | **KEEP** | Relationship plumbing trait delegating domain-specific keys to the model. | commit |
| `tests/*` (4 entries) | **KEEP** | `WorkflowEngineTest` (modified), `ApprovalGuardTest` (tracked), plus new `HasWorkflowTest`, `WorkflowableEntity` fixture, and workflowable test migration. Legitimate additions. | commit |
| `dependencies/*` (8 entries) | **KEEP** | Scaffolding/template cleanup: payroll migration deleted, export_chunks typo fix, HR nouns neutralized, `App\Modules\Admin` → library `Core\Admin`. (See §3.2.) | commit |
| `public/assets/css/quicker-faster.css` (+222) | **NEEDS DECISION** | Pure-additions CSS change; likely a compiled/built asset rather than hand-authored source. Verify the source of truth before committing vs reverting. | ask user |

---

## 3. Specific Investigation Notes

### 3.1 `vendor/` — composer drift, not code
`git diff --stat vendor/` totals **2,347 files, +37,238 / −217,695**. The changes span brick/math, guzzle, symfony/*, etc. — a wholesale `composer update` that rewrote third-party sources. This is never authored library code. `.gitignore` already ignores `/vendor`, meaning it was force-tracked at some point; the correct fix is not just reverting but **untracking**:

```bash
git checkout -- vendor/          # discard current drift
git rm -r --cached vendor/       # stop tracking; .gitignore already covers it
```

### 3.2 `dependencies/` — scaffolding/templates for consuming apps
This directory is the library's **scaffold/publish layer** (deployment configs, migration templates, `User` template, seeders, `routes/web.php`). It is referenced by the boundary doc as shipped scaffolding ([§5.5](library-vs-module-boundary-analysis.md)). The uncommitted changes are all **domain-neutralization and decoupling**, not new business logic:

- `create_payroll_run_progress.php` **deleted** → removes the `payroll` business noun (HR leakage).
- `2026_2_create_ export_chunks_table.php` (space-in-filename typo) deleted → correct `2026_2_create_export_chunks_table.php` added.
- `create_saved_filters_table.php` / `create_saved_reports_table.php` → example comments changed from `hr.attendance` / `hr.employee` to `admin.user`.
- `DatabaseSeeder.php` → drops `App\Modules\Admin\Database\Seeders\QFDatabaseSeeder` for library `Core\Admin\Database\Seeders\RoleSeeder`/`UserSeeder`.
- `UserSeeder.php` → `employee`/`clocking` → `member`; `QuickHR@12345` password → `ChangeMe@12345`.
- `models snipets.txt` → `Employee Profile` → `Profile`.

These belong in the library (they are the *copyable scaffolding* the two-domain rule endorses) and the edits remove the last business nouns. **KEEP**.

### 3.3 `docs/` renames — complete and consistent
Old `docs/architecture/00…25 + phase-5` plus root-level project docs were split into three top-level groups. The new tree is internally consistent and complete:

- `docs/library/` — 01–17, 21, 22, 24, 25, 26, `phase-5-navigation-ux.md`, `README.md`.
- `docs/consuming-app/` — 18, 19, 20, `contracts`, `data-configs`, `data-table-record-events`, `getting-started`, `module-structure`, `multi-tenancy`, `permissions-and-notifications`, `ui-primitives`, `README.md`.
- `docs/project/` — 16, 23, and the pre-existing root project docs (`ai-optimized-architecture-blueprint`, `gap-analysis`, `implementation-plan`, phase specs, etc.) plus new `media-management-boundary-analysis.md`.
- New root `docs/README.md`.

A config cross-reference was already updated in the same move: `src/Config/ui-library.php` §catch-all now points at `docs/library/15-...` instead of `docs/architecture/15-...`. **KEEP.**

### 3.4 `src/Config/ui-library.php` — decoupling infrastructure
The diff adds three domain-agnostic seams:
1. `discovery` (listeners/reports/workflows toggles + cache TTL).
2. `navigation.workspace_resolver` → `NullWorkspaceResolver`.
3. `tenancy` (`column` = `company_id`, `session_key` = `current_company_id`) — consumed by the Jobs changes above.

⚠️ **Pre-existing concern (not introduced by this diff)**: `navigation.company_provider` still defaults to `DefaultCompanyProvider::class` (concrete `Core\Organization\Models\Company`), which the boundary doc flags as a regression against the "null default" contract ([§5.2](library-vs-module-boundary-analysis.md)). This line is *unchanged context* in the diff, so it is a separate remediation, not part of this triage decision.

### 3.5 `AccessControlPermissionService.php` — clean refactor with one legacy leak
The uncommitted changes are domain-agnostic and idempotent (`firstOrCreate`, per-module config, discovery). However the file retains a **pre-existing** `use App\Models\User;` import (unchanged context). This is already on the boundary checklist ("no `App\` references in `src/`"). Flag for follow-up, but the current changes should still be **KEPT**.

### 3.6 `src/Services/Discovery/DiscoveryRegistrar.php` — domain-agnostic ✅
It scans consuming-app modules under `app/Modules/*` *generically*: it resolves PSR-4 classes from relative paths, reads `Listeners/`, `Reports/`, and `Config/workflows.php` by convention, and never hardcodes a business noun. Every domain-specific bit (module key, namespace) is parameterized. This is textbook library infrastructure and passes the two-domain test.

### 3.7 `src/Events/`, `src/Listeners/`, `src/Scopes/`, `src/Traits/Workflows/`
Only the **new/untracked** members are in scope here (`DataTableRecordSaved`, `DataTableRecordListener`, `CompanyScope`, `HasWorkflow`, `HasCompanyScope`, `ResolveCompanyContext`). All are domain-agnostic:
- `DataTableRecordSaved` carries only `oldRecord`/`newRecord`/`model` FQCN/`action`.
- `DataTableRecordListener` is an abstract base with no nouns.
- `CompanyScope` / `HasCompanyScope` / `ResolveCompanyContext` are the tenancy contract layer the boundary doc says should stay in the library.
- `HasWorkflow` delegates domain-specific keys (`getWorkflowDefinitionKey`, `getWorkflowContext`) to the model.

### 3.8 `tests/` — legitimate additions
`TestCase` boots only `UILibraryServiceProvider` + Livewire, points the library at a test `Fixtures\User`, and disables the providers that reference `app/Modules`. New tests (`HasWorkflowTest`, `ApprovalGuardTest`, `WorkflowEngineTest`) cover the workflow/approval infrastructure. All domain-agnostic. **KEEP.**

### 3.9 `composer.json` vs `composer.lock`
`composer.json` adds only the `autoload-dev` PSR-4 map for `Tests\`. `composer.lock` is drift from the same `composer update` that touched `vendor/`. Keep the former, discard the latter.

---

## 4. Summary Counts

| Category | Entries | Notes |
|---|---|---|
| **KEEP** | ~118 | docs renames (71) + src changes (21) + dependencies (8) + tests (4) + composer.json + .gitignore + phpunit.xml + untracked src files |
| **DISCARD** | 2,348 | vendor/ (2,347) + composer.lock (1) |
| **NEEDS DECISION** | 1 | `public/assets/css/quicker-faster.css` |

(Counts are git-status entry counts; several untracked `src/` entries are directories, so the real file count for KEEP is slightly higher.)

---

## 5. Recommended Order of Operations

```bash
# 1. Neutralize the 96% noise first — vendor drift
git checkout -- vendor/
git rm -r --cached vendor/          # untrack it; .gitignore already lists /vendor
git checkout -- composer.lock

# 2. Resolve the one open question
#    public/assets/css/quicker-faster.css → decide commit vs revert

# 3. Stage the kept groups
git add composer.json .gitignore phpunit.xml
git add src/Config/ui-library.php src/Providers/ src/Http/Livewire/DataTables/
git add src/Jobs/ src/Services/AccessControl/ src/Core/Common/Database/Seeders/NotificationTemplateSeeder.php
git add src/Attributes/ src/Console/Commands/DiscoverCommand.php src/Events/DataTableRecordSaved.php
git add src/Http/Middleware/ResolveCompanyContext.php src/Listeners/DataTableRecordListener.php
git add src/Scopes/ src/Services/Discovery/ src/Services/Notifications/NotificationDiscoveryService.php
git add src/Traits/HasCompanyScope.php src/Traits/Workflows/
git add tests/
git add dependencies/               # domain-neutralization cleanup
git add -A docs/                    # captures deletions + renames + new files

# 4. Sanity gate (from boundary checklist §8)
scripts/check-domain-independence.sh

# 5. Commit in logical chunks
#    a. docs restructure
#    b. discovery + tenancy infrastructure (src)
#    c. scaffolding domain-neutralization (dependencies/)
#    d. tests + composer/phpunit
```

---

## 6. Follow-ups (not part of this triage, already tracked/committed)

1. Revert `navigation.company_provider` default to `NullCompanyProvider` ([§5.2](library-vs-module-boundary-analysis.md)).
2. Remove `use App\Models\User;` from `AccessControlPermissionService.php` ([§5.4 / checklist](library-vs-module-boundary-analysis.md)).
3. Move the concrete `Core\Organization` entity graph to a foundational module ([§6.2](library-vs-module-boundary-analysis.md)).
