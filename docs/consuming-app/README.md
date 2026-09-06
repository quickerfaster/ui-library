# Consuming-App Documentation

> **Package**: `quicker-faster/ui-library`
> **Last Updated**: 2026-08-17

Documentation for developers building applications **on top of** the QuickerFaster UI Library. For library-internal architecture, see [../library/README.md](../library/README.md).

---

## Getting Started

1. [**getting-started.md**](getting-started.md) — Install → configure → first module. Covers `composer require`, path repositories, `ui-library:install`, config publishing, assets, and a step-by-step first module walkthrough.

2. [**module-structure.md**](module-structure.md) — Full module anatomy under `app/Modules/{Module}/`. Covers required and optional directories, naming conventions, module registration, auto-discovery conventions and opt-outs, and the `navigation.php`, `workflows.php`, `permissions.php`, and `notifications.php` config schemas.

## Core Concepts

3. [**data-configs.md**](data-configs.md) — DataTable/Form/Detail config schemas with every key documented. Also covers dashboard, wizard, and report config schemas with widget types and step definitions.

4. [**contracts.md**](contracts.md) — Cookbook for implementing the 8 contracts: `Workflowable`, `Documentable`, `Notifiable`, `Reportable`, `CompanyProvider`, `WorkspaceResolver`, `ApproverResolver`, and `ApproverLabelResolver`. Includes engine usage examples and registration instructions.

5. [**ui-primitives.md**](ui-primitives.md) — Library-provided UI components: DataTable, DataTableForm, Approval UI (actions, timeline, request list), Wizards, Widgets, and Navigation components (NavigationLayout, TopNav, Sidebar, BottomBar, WorkspaceTabs, Breadcrumbs, Sidebar Filter).

## Advanced

6. [**multi-tenancy.md**](multi-tenancy.md) — Company scoping & multi-company: `CompanyScope`, `HasCompanyScope`, `ResolveCompanyContext` middleware, session-based scoping flow, tenancy configuration, `CompanyProvider` contract, and testing with tenancy.

7. [**multi-tenancy-vs-multi-company.md**](multi-tenancy-vs-multi-company.md) — Concise distinction between database-level multi-tenancy (deployment concern) and column-level multi-company (library mechanism), with a three-layer model reference and a practical enablement checklist.

8. [**permissions-and-notifications.md**](permissions-and-notifications.md) — Permission auto-generation from discovered models, `Config/permissions.php` overrides, role seeding, notification template registration (`Data/notifications.php`), `{placeholder}` variables, channel configuration, user preferences, and programmatic dispatch.

9. [**data-table-record-events.md**](data-table-record-events.md) — `DataTableRecordSaved` event payload (`model`, `action`, `original`), `DataTableRecordListener` abstract base with `handleCreated`/`handleUpdated`/`handleDeleted`/`handleRestored` hooks, common use cases (audit logging, workflow triggering, notifications, cache invalidation), and testing.

## Reference

10. [**18-workflow-approval-testing-checklist.md**](18-workflow-approval-testing-checklist.md) — Integration and end-to-end testing guidance for workflow and approval features.

11. [**19-notification-consuming-app-guide.md**](19-notification-consuming-app-guide.md) — Deep-dive notification guide for consuming apps: template customization, throttling, segmentation, and custom actions.

12. [**20-reference-workspace-scoped-approver-resolver.md**](20-reference-workspace-scoped-approver-resolver.md) — Complete reference implementation of a workspace-scoped `ApproverResolver`.

---

## Library-Internal Reference

For the library's own architecture, see [../library/README.md](../library/README.md). Key cross-references:

- [../library/08-contracts-and-interfaces.md](../library/08-contracts-and-interfaces.md) — Authoritative contract signatures
- [../library/09-engines-and-services.md](../library/09-engines-and-services.md) — Engine internals & architecture
- [../library/10-settings-and-config.md](../library/10-settings-and-config.md) — Full `config('ui-library')` schema
- [../library/26-module-auto-discovery.md](../library/26-module-auto-discovery.md) — DiscoveryRegistrar internals & caching