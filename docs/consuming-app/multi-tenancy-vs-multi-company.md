# Multi-Tenancy vs Multi-Company

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-30

A concise reference distinguishing database-level multi-tenancy from column-level multi-company scoping. These two concepts are often conflated but operate at entirely different layers.

---

## 1. Multi-Tenancy (Database-Level)

**Separate databases per SaaS client/organization.**

This is a **deployment and infrastructure pattern**: each paying customer gets their own database instance. Connection strings, database provisioning, and tenant-aware routing are all infrastructure concerns.

| Property | Detail |
|---|---|
| **Scope** | Entire database |
| **Mechanism** | Separate DB instances, connection management |
| **Owner** | Deployment / Ops |
| **Library's role** | **None** — the library does not provision databases, manage connection strings, or enforce database-level tenant isolation |

This is NOT the library's concern. It is a deployment/ops responsibility.

---

## 2. Multi-Company (Column-Level)

**Models have a `company_id` column. `CompanyScope` auto-filters all queries by the current company ID from the session.**

This IS the library's mechanism. It is a **data-isolation layer within a single database**, implemented as a global Eloquent scope.

| Property | Detail |
|---|---|
| **Scope** | Individual Eloquent queries |
| **Mechanism** | `company_id` column + `CompanyScope` global scope |
| **Owner** | **Library** — [`CompanyScope`](../../src/Scopes/CompanyScope.php), [`HasCompanyScope`](../../src/Traits/HasCompanyScope.php), [`ResolveCompanyContext`](../../src/Http/Middleware/ResolveCompanyContext.php) |
| **Works for** | Both single-company and multi-company apps |

### How it works

1. `ResolveCompanyContext` middleware calls `CompanyProvider::getCurrentCompanyId($user)`
2. Result is stored in `session('current_company_id')`
3. `CompanyScope` reads `session('current_company_id')` and adds `WHERE company_id = ?` to every scoped query
4. `TopNav::loadCompanies()` calls `CompanyProvider::getCompanies($user)` for the switcher UI

---

## 3. Company Switcher

The `CompanyProvider` contract enables the top-navigation bar to show a company drop-down. Each consuming app implements this contract to resolve the user's available companies.

### Example Implementation

A consuming application implements a `CompanyProvider` (e.g., `App\YourModule\Providers\YourModelCompanyProvider`) which resolves:

```
User → YourModel (by user_id) → Company (by company_id)
```

No `company_id` column exists on the `users` table — the `CompanyProvider` contract lets each app resolve the company however it needs. The chain varies per application; some apps may have a direct `User → Company` relationship, while others use an intermediary model as the bridge.

### Library Default

The library ships with `NullCompanyProvider` — returns empty/no-op. The switcher is hidden until a consuming app binds a real implementation.

---

## 4. Three-Layer Model

From [../library/27-architecture-boundary.md](../library/27-architecture-boundary.md) §3:

| Level | What | Isolation | Owner |
|---|---|---|---|
| **1. SaaS client** | Paying customers (A, B, C) | Separate databases | Deployment config |
| **2. Company** | Multiple companies within one database | `company_id` + `CompanyScope` | **Library** |
| **3. Org hierarchy** | Deparment, Team, Location, Branch | FK relationships | Organization module |

---

## 5. Enabling Multi-Company — Checklist

Follow these steps to enable multi-company scoping in a consuming app:

### 1. Add `company_id` column to models

```php
// In a migration
$table->foreignId('company_id')->constrained('companies');
```

### 2. Add `HasCompanyScope` trait to models

```php
use QuickerFaster\UILibrary\Traits\HasCompanyScope;

class Invoice extends Model
{
    use HasCompanyScope;
}
```

### 3. Implement `CompanyProvider`

Create a provider class in your module's `Providers` directory (e.g., `App\YourModule\Providers\YourModelCompanyProvider`):

```php
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

class YourModelCompanyProvider implements CompanyProvider
{
    public function getCompanies(?User $user): Collection
    {
        // Resolve companies the user can access through
        // your domain model relationships
    }

    public function getCurrentCompanyId(?User $user): int|string|null
    {
        return session('current_company_id');
    }
}
```

### 4. Bind in service provider

Bind in your module's own `ServiceProvider::register()` — not in `AppServiceProvider`:

```php
// In YourModuleServiceProvider::register()
$this->app->bind(
    \QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider::class,
    \App\Modules\YourModule\Providers\YourModelCompanyProvider::class
);
```

This follows the modular binding pattern — each module owns its contract bindings.

### 5. Run cache clear

```bash
php artisan cache:clear
```

---

## Cross-References

- [multi-tenancy.md](multi-tenancy.md) — In-depth company scoping documentation: `CompanyScope`, `ResolveCompanyContext`, testing
- [contracts.md](contracts.md) §6 — `CompanyProvider` implementation cookbook
- [../library/27-architecture-boundary.md](../library/27-architecture-boundary.md) — Canonical three-layer tenancy model and architecture boundary rules
- [../library/10-settings-and-config.md](../library/10-settings-and-config.md) — Full `ui-library.tenancy` config schema