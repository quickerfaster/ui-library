# QuickerFaster UI Library — Documentation

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

Welcome to the QuickerFaster UI Library documentation. This library provides a config-driven, convention-based UI framework for Laravel applications — DataTables, navigation, workflows, documents, notifications, dashboards, wizards, and more — all built on Livewire 3 and Bootstrap 5.

---

## Which audience are you?

### I'm building an application ON the library

→ Start at [consuming-app/getting-started.md](consuming-app/getting-started.md)

- Install, configure, and create your first business module
- Module structure, naming conventions, and auto-discovery
- DataTable/Form/Detail config schemas
- Navigation config (`navigation.php`)
- Contract cookbook: Workflowable, Documentable, Notifiable, Reportable
- Multi-tenancy with CompanyScope and HasCompanyScope
- Permissions, notifications, and UI primitives

### I'm maintaining or extending the library itself

→ Start at [library/01-core-concepts.md](library/01-core-concepts.md)

- Library philosophy and design principles
- Complete `src/` directory map
- ModuleServiceProvider registration protocol
- Component catalog (50+ Livewire components)
- Contract signatures and engine internals
- SettingsManager and `config('ui-library')` schema
- Architecture Decision Records (ADR-001–006)
- Integration map and library independence safeguards

---

## Directory Map

| Directory | Audience | Contents |
|-----------|----------|----------|
| [library/](library/) | Library maintainers | Engines, contracts, providers, ADR, safeguards, component catalog, directory map |
| [consuming-app/](consuming-app/) | App developers | Install, configure, module structure, conventions, contracts, engines, UI primitives |
| [project/](project/) | All | Planning, history, gap analyses, implementation plans, architecture blueprints |
| [components/](components/) | All | Component API reference (WorkspaceTabs, Breadcrumbs, Sidebar Filter) |

---

## Quick Links

- [**CHANGELOG**](CHANGELOG.md) — Version history and release notes
- [**Architecture Decision Records**](library/13-adr.md) — ADR-001 through ADR-006
- [**AI Quick-Start**](library/12-ai-quick-start.md) — Decision tree for AI agents working on the library
- [**Component API**](components/) — WorkspaceTabs, Breadcrumbs, Sidebar Filter reference
- [**Project History**](project/) — Planning documents, gap analyses, and implementation plans