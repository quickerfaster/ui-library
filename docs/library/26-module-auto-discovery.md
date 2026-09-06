# QuickerFaster UI Library — Module Auto-Discovery

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-17
> **Status**: ✅ Authoritative — canonical reference for the DiscoveryRegistrar internals and caching

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`05-data-configs.md`](./05-data-configs.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`25-library-independence-safeguards.md`](./25-library-independence-safeguards.md)

> **Consuming-app developers**: For the conventions (listeners, reports, workflows, permissions, notifications), opt-out mechanisms, and the `ui-library:discover` command, see [../consuming-app/module-structure.md](../consuming-app/module-structure.md) §"Auto-Discovery Conventions & Opt-Outs".

---

## 1. Purpose

The library discovers business-module assets under `app/Modules/*` **by convention** and registers them automatically, eliminating manual service-provider wiring. The [`DiscoveryRegistrar`](../../src/Services/Discovery/DiscoveryRegistrar.php) is the single coordinator; [`ModuleServiceProvider`](../../src/Providers/ModuleServiceProvider.php) drives registration during boot, and `php artisan ui-library:discover` renders a debuggable summary of the same discoveries.

The five discoverable asset types are:

| Asset type | Convention | Registered into |
|------------|-----------|-----------------|
| Event listeners | `app/Modules/{Module}/Listeners/*.php` | Laravel event dispatcher |
| Reports | `app/Modules/{Module}/Reports/*.php` (classes implementing `Reportable`) | `ui-library.reports.report_types` |
| Workflows | `app/Modules/{Module}/Config/workflows.php` | `ui-library.workflows.definitions` (deep-merged) |
| Permissions | auto-generated CRUD names + `Config/permissions.php` overrides | permission registry |
| Notifications | `app/Modules/{Module}/Data/notifications.php` (templates + channels) | notification template/channel registry |

Discovery is **domain-agnostic**: the library reads convention shapes and consuming-app-supplied keys, never a hardcoded business module, model, or class name. See [`25-library-independence-safeguards.md`](./25-library-independence-safeguards.md) §3.3.

> **Convention details**: The full conventions for each asset type, opt-out mechanisms, and the `ui-library:discover` command are now in the consuming-app documentation at [../consuming-app/module-structure.md](../consuming-app/module-structure.md) §"Auto-Discovery Conventions & Opt-Outs". This file focuses on the DiscoveryRegistrar internals and caching strategy.

---

## 3. Caching Strategy

Production caches use a **content-hash + finite TTL** policy:

- **Content-hashed keys** are derived from candidate file paths + mtimes, so they **self-invalidate** whenever a file is added, removed, or changed on deploy.
- **Finite TTL** via `ui-library.discovery.cache_ttl` (default `86400` seconds). The TTL is a safety net only — the library **never** uses `Cache::forever()` for discovery.
- **Dev/test always re-scan** so tests observe fresh state without a manual cache flush.

This resolves the previously-documented "Caching Strategy for Module Discovery" gap ([`15-gaps-and-recommendations.md`](./15-gaps-and-recommendations.md) §10.8).

---

**Related files**: [`03-module-pattern.md`](./03-module-pattern.md) · [`05-data-configs.md`](./05-data-configs.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`11-extension-guide.md`](./11-extension-guide.md) · [`25-library-independence-safeguards.md`](./25-library-independence-safeguards.md)
