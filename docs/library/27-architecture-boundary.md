# Architecture Boundary — The Single Source of Truth

> **Status**: Canonical reference
> **Date**: 2026-08-18
> **Package**: `quicker-faster/ui-library`
> **Role**: The definitive answer to "does this go in the library or a module?" Read this first.

---

## 1. The User's Workflow

The QuickerFaster UI Library is a **domain-independent foundation** for building any business application. It ships with zero business-domain nouns. Every business concept (HR, accounting, inventory, CRM) is a **self-contained, copyable module** under `app/Modules/{ModuleName}/`.

The intended workflow is:

```bash
laravel new my-project
composer require quicker-faster/ui-library
cp -r ~/modules/organization app/Modules/Organization   # foundational module
cp -r ~/modules/hr app/Modules/Hr                       # business module
php artisan ui-library:discover
php artisan migrate
```

Five commands from an empty directory to a working multi-tenant business app. The library provides the infrastructure (data tables, navigation, workflows, documents, notifications, reports, tenancy scoping). Each module provides one business domain. Drop in the modules you need; omit the ones you don't.

---

## 2. The Decision Rule

Every feature, class, and file must answer one question: **library or module?** Apply the two tests below. The first "yes" wins.

### The Two Tests

**T1 — The two-domain test:** Would this work identically for at least two unrelated business domains (e.g., HR and inventory)?

**T2 — The capability-vs-noun test:** Is this a *capability/mechanism* (contract, engine, scope, middleware, trait) or a *business noun* (Invoice, Employee, Payroll, Department)?

### Decision Table

| Goes in the library (`src/`) | Goes in a module (`app/Modules/`) |
|---|---|
| Contracts, engines, scopes, middleware, traits with **no business nouns** in their API | Business **entities/nouns**, domain-specific tables, views, routes |
| Passes the **two-domain test**: works for HR AND inventory AND accounting | Only makes sense for **one domain** |
| Ships with a **null/no-op default** the consuming app can swap | Self-contained with its own migrations, routes, configs, seeders |
| Examples: scoping mechanism, notification engine, document attachment, workflow engine, data tables, navigation, permission system, report engine | Examples: Organization (entity graph), HR, Accounting, Inventory, CRM, Billing |

### The "Company" Specific Rule

> **"company" the scoping term** (`company_id`, scope, switcher, `CompanyProvider` contract) → **library mechanism**.
> **"Company" the org-chart entity** (branches, departments, teams, billing, subdomain) → **Organization module**.

This single distinction resolves the most common boundary question. Read on for the full explanation.

---

## 3. The Three-Layer Tenancy Model

The library supports a two-level SaaS tenancy model. Three layers exist, but only two are the library's concern.

| Level | What | Isolation | Who owns it |
|---|---|---|---|
| **1. SaaS client** | Different paying customers (A, B, C) | Separate databases | **Deployment config** — NOT the library |
| **2. Company** | Multiple companies within one client's database | `company_id` column + global scope | **Library** (scoping mechanism) |
| **3. Org hierarchy** | Department / Team / Location / Branch inside a company | FK relationships | **Organization module** (business domain) |

**Layer 1** is an infrastructure concern: each paying customer gets their own database instance. The library does not provision databases, manage connection strings, or enforce tenant isolation at the database level. That is a deployment/ops responsibility.

**Layer 2** is the library's core tenancy mechanism: a global Eloquent scope that filters every query by `company_id`, driven by a pluggable `CompanyProvider` contract. This is the multi-company data-isolation foundation. It works for both single-company and multi-company apps — in a single-company app, the scope is a no-op or filters to the one company set at login.

**Layer 3** is the Organization module's domain: the rich hierarchy of departments, teams, locations, branches, divisions, and business units *inside* a company. This is a business domain, not infrastructure. A warehouse app needs `Warehouse`/`StorageLocation`, not `Department`/`Team`. A school needs `Campus`/`Faculty`/`Programme`, not `Division`/`BusinessUnit`. The library provides the scoping anchor; the module provides the hierarchy that makes sense for its domain.

---

## 4. The Organization Split

"Organization" is the most frequently misunderstood boundary. It is **two separate things** that happen to share a name.

### Library Side: The Scoping Anchor

The library owns a minimal `companies` table and the scoping mechanism:

| Piece | What it is | Location |
|---|---|---|
| `companies` table | `id`, `name`, `code`, `timestamps` — the minimal tenant record | Library migration |
| [`CompanyScope`](../../src/Scopes/CompanyScope.php) | Global Eloquent scope filtering by `company_id` | Library |
| [`HasCompanyScope`](../../src/Traits/HasCompanyScope.php) | Trait that registers the scope on any model | Library |
| [`ResolveCompanyContext`](../../src/Http/Middleware/ResolveCompanyContext.php) | Middleware that seeds the session tenant ID | Library |
| [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) | Contract: `getCompanies()` / `getCurrentCompanyId()` | Library |
| [`NullCompanyProvider`](../../src/Services/Navigation/NullCompanyProvider.php) | No-op default (returns empty / null) | Library |
| Company switcher component | Generic `TenantSwitcher` Livewire component driven by the provider | Library |

This is the **multi-company data-isolation foundation**. It is universal — every app that scopes data by company needs it, whether single-company or multi-company.

### Module Side: The Organization Entity Graph

The Organization module (`app/Modules/Organization`) owns the rich domain:

- **Concrete `Company` model** with full schema: `tax_id`, `registration_number`, `billing_address`, `subdomain`, `currency_code`, `parent_company_id`, `logo`, etc.
- **Full hierarchy**: `Department`, `Team`, `Location`, `Branch`, `BusinessUnit`, `Division`
- **7+ migrations** for all hierarchy tables
- **Data configs**, **3 dashboards**, **seeders**
- **`OrganizationCompanyProvider`** — the module's implementation of `CompanyProvider`, bound by the consuming app
- **Registration/provisioning flow** — SaaS org-setup controllers and events
- **`/organization/*` routes** — CRUD UI for the org chart

The Organization module is a **foundational module** — other business modules depend on it, so it is versioned and treated as a shared dependency, not throwaway scaffold.

### Why the Split Exists

A single-company invoicing app for a freelancer needs the scoping mechanism (layer 2) but *not* the department/team hierarchy. A warehouse app needs tenant isolation but maps its own `Warehouse`/`StorageLocation` hierarchy, not `Department`/`Team`. Forcing the full org chart on every app violates the two-domain test. Keeping the scoping anchor in the library and the entity graph in a module lets each app pick the hierarchy that fits its domain.

---

## 5. Media / Document Management

**Verdict: Document management stays in the library.**

Document management is a **generic polymorphic file-attachment mechanism**, not a business noun. It passes the two-domain test convincingly:

| Domain | How it uses the library's Document system |
|---|---|
| **Inventory** | Attach product photos, spec sheets, supplier certificates to `Product` / `Supplier` |
| **Accounting** | Attach invoices, receipts, statements to `Transaction` / `Vendor`; generate PDFs |
| **HR** | Attach resumes, contracts, offer letters to `Employee`; generate PDFs for offer letters |

All three use the identical mechanism for the identical reason: "attach a file (or generate one) and be able to store, list, download, and preview it."

### What the Library Owns

| Piece | Role |
|---|---|
| [`Documentable`](../../src/Contracts/Documents/Documentable.php) contract | Any model answers: what's your ID, type key, storage path, template data? |
| [`DocumentEngine`](../../src/Services/Documents/DocumentEngine.php) | Generic `upload()`, `generatePdf()`, `generateExcel()`, `getDocuments()`, `delete()` |
| Polymorphic [`Document`](../../src/Models/Document.php) model | `documentable_type`/`documentable_id` morph, `file_path`, `mime_type`, `size`, `disk`, `metadata` |
| Preview UI | [`DocumentPreview`](../../src/Http/Livewire/DocumentPreview.php) + [`DocumentPreviewModal`](../../src/Http/Livewire/Modals/DocumentPreviewModal.php) — detects image/pdf/text/office/unsupported and renders the right partial |

### What Modules Own

Modules own their **business metadata**. The established pattern is:

> **Library owns the base table; modules ALTER for domain columns.**

The library's `documents` table is the single source of truth — a polymorphic file-attachment table with `documentable_type`, `documentable_id`, `file_path`, `mime_type`, `size`, `disk`, and `metadata` columns. HR uses an ALTER TABLE migration to add domain-specific columns (`employee_id`, `type`, `expiry_date`) without creating a duplicate `documents` table. This pattern applies to any module that needs to extend a library-owned base table with domain-specific metadata.

The rule: **the library owns the file mechanism; each module owns its business metadata. Modules extend library tables via ALTER, never by creating competing tables with the same name.**

---

## 6. Multi-Tenancy Foundation

### The Scoping Vocabulary

The library uses **"company"** as the scoping unit. This is configurable — the column name, session key, and provider label can all be overridden. The code may eventually be renamed to a neutral term (e.g., "tenant") to avoid collision with the Organization module's concrete `Company` entity, but the concept is stable: **"scope all rows to the current data-isolation unit."**

### Pluggable Tenant Model

The library depends on a [`CompanyProvider`](../../src/Contracts/Navigation/CompanyProvider.php) contract returning `{id, name}` pairs. It never imports a concrete Company model.

```php
interface CompanyProvider
{
    /** @return Collection<int, object{id:int|string, name:string}> */
    public function getCompanies(?User $user): Collection;

    public function getCurrentCompanyId(?User $user): int|string|null;
}
```

- **Default binding:** [`NullCompanyProvider`](../../src/Services/Navigation/NullCompanyProvider.php) — returns empty / null. The library works with zero domain assumptions out of the box.
- **Consuming app binding:** the app (or the Organization module) binds its own implementation. Example: `OrganizationCompanyProvider` reads from the concrete `Company` model.
- **Single-tenant apps:** bind a provider that returns exactly one tenant. The switcher auto-hides when only one company exists.

### Company Switcher

A generic `TenantSwitcher` Livewire component driven entirely by the provider. It has no knowledge of "Company," "Workspace," or "Account" — it renders whatever `{id, name}` pairs the provider returns.

| Scenario | Behavior |
|---|---|
| `count($tenants) > 1` | Switcher visible; user can switch companies |
| `count($tenants) === 1` | Switcher hidden automatically |
| `NullCompanyProvider` (default) | Switcher hidden; scope applies no filter |

The label is configurable (`"Switch workspace"`, `"Switch company"`, etc.).

### Column Convention

`company_id` is the default column on library tables. The [`HasCompanyScope`](../../src/Traits/HasCompanyScope.php) trait supports a configurable column name via `config('ui-library.tenancy.column')`. Modules follow the same convention — all business tables use `company_id` as the scoping foreign key. The scope is bypassed with `withoutCompanyScope()` when cross-company queries are needed.

---

## 7. Module Dependency Chain

```
Library (scoping + infrastructure)
  └── Organization module (app/Modules/Organization) — foundational
        ├── HR module (app/Modules/Hr) — core people
        │     ├── Attendance module (app/Modules/Attendance)
        │     ├── Leave module (app/Modules/Leave)
        │     └── Payroll module (app/Modules/Payroll)
        └── Holiday module (app/Modules/Holiday)
```

**The library** provides the scoping mechanism, data tables, navigation, workflows, documents, notifications, reports, permissions, and the module auto-discovery system. It has zero dependencies on any module.

**The Organization module** is foundational — it provides the concrete Company entity and org hierarchy that other business modules reference. Modules declare their dependency on Organization via the `depends_on` key in their module registry entry.

**The HR module** is a core people module slimbed to 15 models (Employee, EmployeePosition, JobTitle, etc.). It depends on Organization for company scoping and org hierarchy. Three sub-modules (Attendance, Leave, Payroll) depend on HR for the employee entity.

**The Attendance module** (12 models) handles time tracking, shifts, clock events, and work patterns. It depends on Organization and HR.

**The Leave module** (5 models) handles leave types, requests, balances, and approvers. It depends on Organization, HR, and Attendance (event-driven sync via `LeaveApproved`/`LeaveDenied` events).

**The Payroll module** (11 models) handles payroll runs, payslips, policies, and bank files. It depends on Organization, HR, and Attendance (resolves `AttendanceHoursProvider` from the container).

**The Holiday module** (2 models) handles holiday calendars and holidays. It depends on Organization.

**Additional business modules** (Accounting, Inventory, CRM, etc.) are leaf nodes. Each is self-contained, owns its own migrations/routes/views/configs, and depends on the library (always) and Organization (when it needs the company entity graph).

---

## 8. Practical Workflow

### Multi-Company HR Project (current reference implementation)

```bash
laravel new hr-platform
composer require quicker-faster/ui-library
php artisan ui-library:install

# Copy foundational + business modules (6 modules)
cp -r ~/modules/organization app/Modules/Organization   # foundational
cp -r ~/modules/hr app/Modules/Hr                       # core people (15 models)
cp -r ~/modules/attendance app/Modules/Attendance       # time tracking (12 models)
cp -r ~/modules/leave app/Modules/Leave                 # leave management (5 models)
cp -r ~/modules/payroll app/Modules/Payroll             # payroll (11 models)
cp -r ~/modules/holiday app/Modules/Holiday             # holidays (2 models)

# Bind the Organization module's CompanyProvider
# In AppServiceProvider:
#   $this->app->singleton(
#       CompanyProvider::class,
#       App\Modules\Organization\Providers\OrganizationCompanyProvider::class
#   );

php artisan ui-library:discover
php artisan migrate
```

### Single-Company Project (e.g., Freelance Invoicing)

```bash
laravel new invoice-app
composer require quicker-faster/ui-library
php artisan ui-library:install

# No Organization module needed — the library's minimal companies table
# and NullCompanyProvider are enough for single-company scoping

# Bind a single-tenant provider, or leave NullCompanyProvider as default
php artisan ui-library:discover
php artisan migrate
```

### New Domain (No Existing Module)

```bash
laravel new new-project
composer require quicker-faster/ui-library
php artisan ui-library:install

cp -r ~/modules/organization app/Modules/Organization   # if multi-company

# Create your module following library conventions:
mkdir -p app/Modules/NewDomain/{Config,Data,Models,Resources/views,Routes,Database/Migrations}

# Define Data/*.php configs, Models, and views
# The library auto-discovers everything at boot
php artisan ui-library:discover
```

---

## 9. Quick Reference / FAQ

### Does X go in the library or a module?

| Question | Answer | Reason |
|---|---|---|
| A new Eloquent model for "Invoice"? | **Module** | Business noun |
| A trait that adds `company_id` scoping to any model? | **Library** | Mechanism, no nouns |
| A dashboard showing "Revenue by Department"? | **Module** | Domain-specific widget |
| A dashboard widget engine that renders any widget config? | **Library** | Mechanism |
| A migration creating an `employees` table? | **Module** | Domain table |
| A migration creating a `documents` polymorphic table? | **Library** | Generic infrastructure |
| A "Send Invoice" notification template? | **Module** | Domain-specific template |
| The notification dispatch engine? | **Library** | Mechanism |
| A route for `/hr/employees`? | **Module** | Domain route |
| A catch-all route resolving `/{module}/{view}`? | **Library** | Generic infrastructure |
| A config file defining invoice fields? | **Module** | Domain config |
| The DataTable engine that reads any config? | **Library** | Mechanism |

### What if I'm not sure?

Apply the two-domain test: "Would this work identically for an HR app and an inventory app?" If yes, it belongs in the library. If it only makes sense for one domain, it belongs in a module.

### What's the difference between "company" the scoping term and "Company" the entity?

The library's "company" is a **tenant term** — a `company_id` integer and a `{id, name}` pair used to partition rows. The Organization module's "Company" is a **business entity** — a rich model with tax IDs, billing addresses, subdomains, parent-child hierarchies, and CRUD UI. The library provides the scoping mechanism; the module provides the entity.

### Can I use the library without the Organization module?

Yes. The library ships with `NullCompanyProvider` as the default. In a single-company app, the `company_id` scope is a no-op or filters to a single hardcoded value. The library's minimal `companies` table is enough — no department/team hierarchy is forced on you.

### What if two modules need to share a table?

**One owner per table.** Modules only `ALTER` what they extend. If both HR and Accounting need a `companies` table, the Organization foundational module owns it, and both modules declare `depends_on` pointing to Organization. Never have two modules create the same table.

### What if a module needs a cross-cutting concern like addresses?

If it passes the two-domain test (HR employees have addresses, inventory warehouses have addresses, accounting vendors have addresses), it is a candidate for either a foundational module (if it has domain flavor) or the library (if it is pure mechanism). Start with a foundational module; promote to the library only after it survives real use across two unrelated domains.

### How do I add a new business domain?

Follow the module conventions in [`module-structure.md`](../consuming-app/module-structure.md). Create `app/Modules/{Domain}/` with `Data/`, `Models/`, `Resources/views/`, `Routes/`, and `Database/Migrations/`. The library's auto-discovery picks up everything at boot. Run `php artisan ui-library:discover` to verify.

### What is NOT the library's job?

- **Database provisioning** (separate DB per SaaS client) — deployment/ops concern
- **Business-specific reports** (e.g., "Monthly Payroll Summary") — module concern
- **Domain-specific validation rules** (e.g., "tax ID format for Nigeria") — module concern
- **Third-party API integrations** (e.g., payment gateways, SMS providers) — module concern
- **User membership in companies** (the `company_user` pivot) — app/Organization module concern, exposed through `CompanyProvider`

---

## Appendix — Source Documents

This document synthesizes the following analyses. Refer to them for deeper evidence and code-level detail:

| Document | Topic |
|---|---|
| [`library-vs-module-boundary-analysis.md`](../project/library-vs-module-boundary-analysis.md) | Initial boundary analysis; Organization split decision; concrete conflicts found |
| [`media-management-boundary-analysis.md`](../project/media-management-boundary-analysis.md) | Document management verdict; enterprise/freelance scenario analysis |
| [`multitenancy-foundation-analysis.md`](../project/multitenancy-foundation-analysis.md) | "Company" vs "tenant" vs "workspace"; pluggable tenant model; rename map |
| [`uncommitted-noise-triage.md`](../project/uncommitted-noise-triage.md) | What's noise vs what's library code in the current working tree |
| [`25-library-independence-safeguards.md`](25-library-independence-safeguards.md) | Hard rules: contract boundary, config-driven design, two-domain test, grep gates |
| [`module-structure.md`](../consuming-app/module-structure.md) | Module anatomy, auto-discovery conventions, navigation/workflow/permission configs |
| [`getting-started.md`](../consuming-app/getting-started.md) | Installation workflow, configuration, creating your first module |