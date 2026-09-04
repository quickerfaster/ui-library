# Pre-Coding Architecture Checklist

> **⚠️ Must be reviewed before writing any code in this project.**
> Violations of these rules have caused recurring bugs (undefined variables, wrong sidebars, library coupling, scattered module files).
> See also: [Library Independence Safeguards](../library/25-library-independence-safeguards.md), [Module Structure](module-structure.md), [Routing & Views](../library/04-routing-and-views.md), [Navigation System](../library/06-navigation-system.md)

---

## A. Before Creating a Blade View

- [ ] **Catch-all route awareness**: If this view is rendered by the `/{module}/{view}` catch-all route (no explicit `Route::get()`), it is a **thin wrapper only**. The only variable available is `$id` (from `/{module}/{view}/{id?}`). All Livewire state (`$activeTab`, `$formData`, etc.) must live in a Livewire component rendered via `@livewire('component-name')`.
- [ ] **Navigation layout**: Every page must use `<x-qf::navigation-layout>` with:
  - `configKey` — the data config key (e.g., `"hr.employee"`)
  - `context` — MUST match the context group key in `Config/navigation.php` (e.g., `"my-portal"`)
  - `moduleName` — the module name (e.g., `"hr"`)
  - `:overrides` — consistent with other views in the same context
- [ ] **Livewire component views**: If this blade is a Livewire component view (returned by `render()`), it must live in `Resources/views/livewire/` subdirectory, NOT in the root `Resources/views/` directory.
- [ ] **Employee scoping**: For ESS views, resolve the employee from `Auth::user()` and pass `:query-filters` to scope data. Abort 403 if no employee record exists.

### Correct Pattern (Thin Wrapper + Livewire)
```blade
{{-- resources/views/my-feature.blade.php --}}
@php
    $employee = \App\Modules\Hr\Models\Employee::where('user_id', Auth::id())->first();
    if (!$employee) { abort(403); }
@endphp
<x-qf::navigation-layout configKey="hr.employee" context="my-portal" moduleName="hr" :overrides="[...]">
    @livewire('my-feature', ['employeeId' => $employee->id])
</x-qf::navigation-layout>
```

### Wrong Pattern (Livewire State in Catch-All View)
```blade
{{-- BUG: $activeTab is undefined when rendered by catch-all route --}}
<x-qf::navigation-layout ...>
    <button class="{{ $activeTab === 'overview' ? 'active' : '' }}">Overview</button>
</x-qf::navigation-layout>
```

---

## B. Before Creating a Livewire Component

- [ ] **Library vs Module boundary**: Is this component domain-agnostic (belongs in the UI library `src/`) or domain-specific (belongs in the consuming app `app/Modules/`)?
  - **Library**: Must NOT reference any `App\Modules\*` namespace. Use contracts and service container binding.
  - **Consuming app**: Can reference module models, but must stay within `app/Modules/{ModuleName}/`.
- [ ] **Subclass pattern**: If extending a library component (e.g., `WizardForm`), create the subclass in `app/Modules/{Module}/Http/Livewire/` and register it in the module's service provider.
- [ ] **Component naming convention**: Check existing components in the module's service provider. Use the same prefix pattern (e.g., `qf.` prefix or bare name). Inconsistent naming causes "Unable to find component" errors.
- [ ] **Service provider registration**: Every Livewire component must be registered in the module's service provider via `Livewire::component('name', Class::class)` in the `boot()` method. Auto-discovery alone is not sufficient — the component alias must be explicitly registered.
- [ ] **View path**: `render()` must return the view from the `livewire/` subdirectory (e.g., `return view('hr::livewire.my-component');`).

---

## C. Before Modifying Library Code (`src/`)

- [ ] **No consuming app references**: Search for `App\Modules` in the file. If found, STOP. Use one of:
  - **Contract pattern**: Define an interface in `src/Contracts/`, bind implementation in consuming app's service provider.
  - **Subclass pattern**: Make the method a stub in the library, override in consuming app subclass.
  - **Config-driven**: Pass domain-specific values via config arrays, not hardcoded references.
- [ ] **Two-domain test**: Would this code work in a completely different application (e.g., a CRM, not HR)? If no, it belongs in the consuming app.
- [ ] **Backward compatibility**: Existing functionality must not break. New features should be opt-in via config.

---

## D. Before Adding Files to a Module

- [ ] **All files under `app/Modules/{ModuleName}/`**: No module files outside the module directory.
- [ ] **Correct subdirectories**:
  | File Type | Location |
  |-----------|----------|
  | Models | `app/Modules/{Module}/Models/` |
  | Data configs | `app/Modules/{Module}/Data/` |
  | Blade views | `app/Modules/{Module}/Resources/views/` |
  | Livewire components | `app/Modules/{Module}/Http/Livewire/` |
  | Livewire views | `app/Modules/{Module}/Resources/views/livewire/` |
  | Migrations | `app/Modules/{Module}/Database/Migrations/` |
  | Seeders | `app/Modules/{Module}/Database/Seeders/` |
  | Services | `app/Modules/{Module}/Services/` |
  | Routes | `app/Modules/{Module}/Routes/web.php` |
  | Navigation config | `app/Modules/{Module}/Config/navigation.php` |
  | Workflow config | `app/Modules/{Module}/Config/workflows.php` |
- [ ] **Self-contained test**: Could this module be copied to a fresh Laravel project with the UI library and work? If no, fix the dependencies.

---

## E. Before Adding Navigation Items

- [ ] **Context group match**: The `context` prop in the blade view MUST match the context group key in `Config/navigation.php`.
  - Example: Blade has `context="my-portal"` → Nav config must have `'my-portal' => ['items' => [...]]`
- [ ] **Route coverage**: Is the route covered by the catch-all `/{module}/{view}` pattern, or does it need an explicit `Route::get()`?
  - Catch-all covers: `/{module}/{view}` → `app/Modules/{Module}/Resources/views/{view}.blade.php`
  - Explicit route needed for: custom URLs, route parameters beyond `{id}`, named routes
- [ ] **Permission**: Every nav item should have a `permission` key for access control.
- [ ] **Icon**: Use Font Awesome 5 free icons (`fas fa-*`).

---

## Quick Reference: Common Violations & Fixes

| Violation | Symptom | Fix |
|-----------|---------|-----|
| Livewire state in catch-all blade | "Undefined variable $activeTab" | Move state to Livewire component, blade becomes thin wrapper with `@livewire()` |
| Wrong `context` in blade | Wrong sidebar links appear | Match `context` to nav config context group key |
| Library references `App\Modules` | Library coupled to one app | Use contract/subclass/config pattern |
| Module files outside module dir | Can't copy module to new project | Move files into `app/Modules/{Module}/` |
| Nav item missing permission | All users see the link | Add `permission` key |
| Blade not using navigation-layout | No sidebar/topbar on page | Wrap in `<x-qf::navigation-layout>` |