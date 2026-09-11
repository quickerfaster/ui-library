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
  | Factories | `app/Modules/{Module}/Database/Factories/` |
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

---

## F. Before Adding Custom Row Actions (`moreActions`)

- [ ] **Use the `event` key pattern**: When defining custom row actions in a data config's `moreActions` array, use the `event` key to dispatch a Livewire event. The value is the event name that your DataTable subclass will listen for.

  ```php
  // Correct — flat 'event' key dispatches a Livewire event
  'moreActions' => [
      [
          'title' => 'Resend Invitation',
          'icon' => 'fas fa-paper-plane',
          'event' => 'resendInvitation',          // ← dispatched as Livewire event
          'permission' => 'view_invitation',
          'condition' => ['status' => 'pending'],  // ← flat format
      ],
  ],
  ```

- [ ] **Use the flat `condition` format**: Conditions are specified as a simple key-value array (`['status' => 'pending']`), NOT the nested `{field, operator, value}` format. The flat format means "show this action only when the record's field equals this value."

  ```php
  // Correct — flat key-value condition
  'condition' => ['status' => 'pending']

  // Wrong — nested operator format (not supported by moreActions)
  'condition' => ['field' => 'status', 'operator' => '=', 'value' => 'pending']
  ```

- [ ] **Extend `DataTable` with `executeRowAction()` override**: The base `DataTable` does not handle a plain `event` key in `moreActions` — it only handles `dispatchLivewireEvent` (with `eventName`/`params`). To support the `event` key, create a subclass that overrides `executeRowAction()`:

  ```php
  // In your custom DataTable subclass (e.g., InvitationDataTable)
  public function executeRowAction($params): void
  {
      if (empty($params) || !is_array($params)) {
          return;
      }

      if (!isset($params['actionIndex']) || !isset($params['recordId'])) {
          return;
      }

      $action = $this->moreActions[$params['actionIndex']] ?? null;
      if (!$action) {
          return;
      }

      // If the action defines a plain 'event' key, dispatch it as a
      // Livewire event and let the dedicated listener handle the rest.
      if (!empty($action['event'])) {
          $this->dispatch($action['event'], $params['recordId']);
          return;
      }

      // Fall through to the parent implementation for all other action types.
      parent::executeRowAction($params);
  }
  ```

- [ ] **Register dedicated listeners**: Each `event` value must have a corresponding listener method in your DataTable subclass. Add the event name to the `$listeners` array and implement the handler method:

  ```php
  protected $listeners = [
      // ... base listeners ...
      'resendInvitation' => 'resendInvitation',
      'revokeInvitation' => 'revokeInvitation',
      'copyInvitationLink' => 'copyInvitationLink',
  ];

  public function resendInvitation($recordId): void
  {
      // Handle resend logic, then refresh the table
      $this->dispatch('$refresh');
  }
  ```

- [ ] **Reference canonical examples**: See [`InvitationDataTable`](src/Http/Livewire/DataTables/InvitationDataTable.php) for the complete `executeRowAction()` override and listener pattern, and [`invitation.php`](src/Core/Admin/Data/invitation.php) for the `moreActions` config definition with `event` keys and flat `condition` format.

---

## G. Before Adding Boolean Fields to a Data Config

- [ ] Use `checkbox` for a plain true/false flag — it is the safe default.
- [ ] Use `boolradio` only when `1 = Yes` / `0 = No` genuinely matches the stored semantics.
- [ ] **Never** define inverted `boolradio` options (e.g. `0 => 'Yes'`) — save casts to `(bool)` and the inversion is silently lost.
- [ ] All three boolean types (`checkbox`, `boolcheckbox`, `boolradio`) are auto-cast on save; unchecked/absent → `false`.

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
| Inverted `boolradio` for a boolean flag | Value saved opposite of what the user selected | Use `field_type => 'checkbox'` |