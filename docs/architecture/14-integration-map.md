# QuickerFaster UI Library — Integration & Dependency Map

> **Package**: `quicker-faster/ui-library`
> **Namespace**: `QuickerFaster\UILibrary\`
> **Last Updated**: 2026-08-14

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`13-adr.md`](./13-adr.md)

---

## 8. Integration & Dependency Map

### 8.1 Composer Dependencies and Their Roles

| Package | Version | Role |
|---------|---------|------|
| `livewire/livewire` | `^3` | Interactive, stateful UI components (tables, forms, modals, wizards) |
| `barryvdh/laravel-dompdf` | `^3.0` | PDF export rendering for data tables and detail pages |
| `maatwebsite/excel` | `^3.1` | Excel/CSV import and export infrastructure |
| `laravel/fortify` | `^1.0` | Authentication scaffolds (login, register, password reset, 2FA) |
| `laravel/socialite` | `^5.0` | OAuth social authentication (Google, GitHub) |
| `spatie/laravel-permission` | `^6.21` | Role and permission management |
| `spatie/laravel-onboard` | `^2.6` | Onboarding checklist and step flows |

### 8.2 Service Provider Wiring

```
┌─────────────────────────────────────────────────────────────┐
│                    UILibraryServiceProvider                  │
│  register():                                                │
│    • ImportProcessor (singleton)                            │
│    • SettingsManager (singleton, 3-tier resolver chain)     │
│    • ModelConfigRepository (singleton)                      │
│    • path.public binding (shared hosting aware)             │
│  boot():                                                    │
│    • registerCommands()                                     │
│    • registerLivewireComponents() — 50+ components          │
│    • registerPublishables() — views, config                 │
│    • registerFortifyViews() — login, register, reset        │
│    • registerSocialiteProviders() — Google, GitHub          │
│    • loadViewsFrom(__DIR__.'/../Resources/views', 'qf')     │
│    • Blade::component('qf::layouts.app', 'layout')          │
│    • Blade::component('qf::layouts.guest', 'guest-layout')  │
│    • Blade::component('qf::components.breadcrumb', ...)     │
│    • Blade::componentNamespace('QuickerFaster\\...', 'qf')  │
│    • Blade::directive('setting', ...)                       │
│    • mergeConfigFrom(... 'quicker-faster-ui')               │
│    • loadTranslationsFrom(... 'qf')                         │
├─────────────────────────────────────────────────────────────┤
│                    ModuleServiceProvider                     │
│  boot():                                                    │
│    • registerPublishables() — qf-public-assets, qf-modules  │
│    • registerModuleConfig() — global + dashboard + report   │
│    • setupModules()                                         │
│      ├── registerModuleViewAlias()                          │
│      ├── registerModuleRoutes()                             │
│      ├── registerModuleMigrations()                         │
│      └── registerModuleEvents()                             │
│    • registerAppOnboardingCnfig() — Spatie Onboard steps    │
├─────────────────────────────────────────────────────────────┤
│                    FortifyServiceProvider                    │
│  boot():                                                    │
│    • Fortify::createUsersUsing(CreateNewUser::class)        │
│    • Fortify::updateUserProfileInformationUsing(...)        │
│    • Fortify::updateUserPasswordsUsing(...)                 │
│    • Fortify::resetUserPasswordsUsing(...)                  │
│    • RateLimiter: login (5/min), two-factor (5/min)         │
└─────────────────────────────────────────────────────────────┘
```

> See [`03-module-pattern.md`](./03-module-pattern.md) for the full `ModuleServiceProvider` registration protocol (view aliases, route loading order, migration loading, event auto-discovery).

### 8.3 Inter-Package Communication Patterns

| Pattern | Implementation | Example |
|---------|---------------|---------|
| **Service binding** | Singleton bindings in service providers | `ImportProcessor`, `ModelConfigRepository`, `SettingsManager` |
| **Event listeners** | Auto-discovered via reflection on `handle()` signatures | `app/Modules/Hr/Listeners/SendWelcomeEmail.php` |
| **Config merge** | `mergeConfigFrom()` in service providers | Dashboard configs: `hr_employee_overview` |
| **View namespace** | `loadViewsFrom()` with module alias | `view('hr::dashboard')` |
| **Blade component** | `Blade::component()` and `Blade::componentNamespace()` | `<x-layout>`, `<x-qf::text-field>` |
| **Livewire registration** | `Livewire::component('qf.name', Class::class)` | `<livewire:qf.data-table>` |
| **Blade directive** | `Blade::directive('setting', ...)` | `@setting('date_format', 'Y-m-d')` |

### 8.4 Database Assumptions

The library's scaffolds assume the consuming app has:

| Requirement | Details |
|-------------|---------|
| `users` table | With columns: `has_seen_tour` (boolean), `company_id` (nullable FK) |
| `system_settings` table | Polymorphic: `settingable_type`, `settingable_id`, `key`, `value`, `group` |
| `system` table | Singleton system record (id=1) for system-level defaults |
| `exports` table | Export job tracking |
| `export_chunks` table | Export chunk file tracking |
| `imports` table | Import job tracking |
| `import_chunks` table | Import chunk tracking |
| `saved_filters` table | User-saved filter presets |
| `saved_reports` table | User-saved reports |
| `personal_access_tokens` table | With `expires_at` column |
| Spatie Permission tables | `roles`, `permissions`, `model_has_roles`, etc. |

> **Cross-link**: §8.5 Settings Architecture (the [`SettingsManager`](../../src/Services/Settings/SettingsManager.php) 3-tier cascading resolver and [`HasSettings`](../../src/Traits/HasSettings.php) trait) is documented in [`10-settings-and-config.md`](./10-settings-and-config.md).

---

**Related files**: [`00-index.md`](./00-index.md) · [`03-module-pattern.md`](./03-module-pattern.md) · [`09-engines-and-services.md`](./09-engines-and-services.md) · [`10-settings-and-config.md`](./10-settings-and-config.md) · [`13-adr.md`](./13-adr.md)
