# Library-Internal Documentation

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

Documentation for developers **maintaining or extending** the QuickerFaster UI Library itself. For building applications on the library, see [../consuming-app/README.md](../consuming-app/README.md).

---

## Reading Order

### New Library Contributors

1. [**01-core-concepts.md**](01-core-concepts.md) — Philosophy & design principles: separation of concerns, convention over configuration, config-driven rendering, catch-all routing, scaffolded auth, reusable composition, three-tier settings, and the `qf` namespace convention.

2. [**02-directory-map.md**](02-directory-map.md) — Complete `src/` directory tree with every file and its purpose. Includes Core module map (`src/Core/Organization/`).

3. [**17-view-config-routing-interplay.md**](17-view-config-routing-interplay.md) — Request lifecycle "Rosetta Stone": how views, configs, and routes interact. Documents the Core-module config resolution gap.

4. [**13-adr.md**](13-adr.md) — Architecture Decision Records (ADR-001 through ADR-006): Vanilla-JS policy, component ownership, navigation architecture, three-tier settings, and more.

### Component Developers

1. [**07-component-catalog.md**](07-component-catalog.md) — Complete catalog of all Livewire components (50+), Blade components, services, models, and traits. Each entry includes class location, alias, and purpose.

2. [**08-contracts-and-interfaces.md**](08-contracts-and-interfaces.md) — All contract signatures with full method definitions: `Workflowable`, `Documentable`, `Notifiable`, `NotificationChannel`, `Reportable`, `FieldType`, `Widget`, `ModuleContract`, `NavigationProvider`, `SettingsProvider`, `CompanyProvider`, `WorkspaceResolver`, `ApprovalModelResolver`, `OnboardingCondition`.

3. [**11-extension-guide.md**](11-extension-guide.md) — Step-by-step recipes for extending the library: adding FieldTypes, overriding library components, and adding widget types.

### Engine Developers

1. [**09-engines-and-services.md**](09-engines-and-services.md) — Five engine services in depth: `WorkflowEngine`, `DocumentEngine`, `NotificationService`, `ReportEngine`, `ReferenceDataService`. Architecture, schemas, and integration.

2. [**10-settings-and-config.md**](10-settings-and-config.md) — Full `config('ui-library')` schema (module paths, discovery, navigation, approvals, activity logs, user model, workflows, tenancy, documents, reports, catch-all security). Plus `SettingsManager` 3-tier cascading resolver and `HasSettings` trait.

3. [**26-module-auto-discovery.md**](26-module-auto-discovery.md) — `DiscoveryRegistrar` internals and caching strategy (content-hash + finite TTL).

### Architects & Reviewers

1. [**13-adr.md**](13-adr.md) — Architecture decisions with rationale.

2. [**15-gaps-and-recommendations.md**](15-gaps-and-recommendations.md) — 10 known gaps and weaknesses with recommendations.

3. [**14-integration-map.md**](14-integration-map.md) — Composer dependencies and service-provider wiring.

4. [**25-library-independence-safeguards.md**](25-library-independence-safeguards.md) — Domain-agnostic enforcement and verification.

---

## Full File Index

| File | Description |
|------|-------------|
| [01-core-concepts.md](01-core-concepts.md) | Library philosophy, design principles, `qf` namespace convention |
| [02-directory-map.md](02-directory-map.md) | Complete `src/` directory tree with file purposes |
| [03-module-pattern.md](03-module-pattern.md) | ModuleServiceProvider registration protocol, view namespaces, route loading, event listener auto-discovery |
| [04-routing-and-views.md](04-routing-and-views.md) | Routing architecture, catch-all route, view namespace resolution, route loading order |
| [05-data-configs.md](05-data-configs.md) | ModelConfigRepository, ConfigResolver internals, config-driven validation and import/export |
| [06-navigation-system.md](06-navigation-system.md) | NavigationLayout, TopNav, Sidebar, BottomBar, MenuRenderer, NavigationManager, WorkspaceFilter |
| [07-component-catalog.md](07-component-catalog.md) | Complete catalog: Livewire, Blade, services, models, traits |
| [08-contracts-and-interfaces.md](08-contracts-and-interfaces.md) | All contract signatures with full method definitions |
| [09-engines-and-services.md](09-engines-and-services.md) | Workflow, Document, Notification, Report, Reference Data engines |
| [10-settings-and-config.md](10-settings-and-config.md) | `config('ui-library')` schema + SettingsManager 3-tier resolver |
| [11-extension-guide.md](11-extension-guide.md) | FieldType, widget, and component extension recipes |
| [12-ai-quick-start.md](12-ai-quick-start.md) | Agent-facing decision tree for common library tasks |
| [13-adr.md](13-adr.md) | Architecture Decision Records (ADR-001–006) |
| [14-integration-map.md](14-integration-map.md) | Composer dependencies + service-provider wiring |
| [15-gaps-and-recommendations.md](15-gaps-and-recommendations.md) | 10 known library gaps with recommendations |
| [17-view-config-routing-interplay.md](17-view-config-routing-interplay.md) | View-config-routing "Rosetta Stone" document |
| [21-approval-infrastructure-analysis.md](21-approval-infrastructure-analysis.md) | Legacy vs WorkflowEngine analysis |
| [22-workflow-definition-wizard-ux.md](22-workflow-definition-wizard-ux.md) | Workflow definition wizard data model + UX |
| [24-workflow-wizard-ux-polish.md](24-workflow-wizard-ux-polish.md) | Wizard UX polish details |
| [25-library-independence-safeguards.md](25-library-independence-safeguards.md) | Domain-agnostic enforcement |
| [26-module-auto-discovery.md](26-module-auto-discovery.md) | DiscoveryRegistrar internals + caching strategy |
| [phase-5-navigation-ux.md](phase-5-navigation-ux.md) | Vanilla-JS navigation architecture (WorkspaceTabs, Breadcrumbs, Sidebar Filter) |

---

## Project & Planning

Historical planning documents live in [../project/](../project/). Component API reference lives in [../components/](../components/).