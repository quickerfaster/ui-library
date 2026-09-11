# AI Prompt: Leave Request Wizard — Empty Card Troubleshooting

> **Type**: Bug investigation + fix
> **Severity**: High — Leave Request Wizard renders an empty card with no form fields
> **For**: Next AI session (fresh context)
> **Date**: 2026-09-10

---

## 1. Session Summary — What Was Accomplished

The previous session resolved ~20+ issues across the onboarding wizard, employee detail page, authorization system, and UI/UX. All code is committed and pushed.

### Onboarding Wizard
- Fixed Step 1 UNIQUE constraint violation by adding `employee_id` to the `job_history` table's unique index
- Removed Step 4 (Documents) after 8 rounds of debugging confirmed a Livewire 3 `wire:model` auto-upload incompatibility with nested Livewire components
- Added "Finish" button completing the wizard flow
- Fixed employee linking for invitation-accepted users
- Fixed tab persistence across navigation

### Employee Detail Page
- Resolved 403 authorization errors for self-service employees
- Restored Edit buttons on the self-service profile view
- Locked preset fields (fields set via `presetData`) to read-only display
- Fixed date field rendering (Carbon→Y-m-d string conversion)
- Fixed multi-select field rendering
- Fixed profile card layout regressions

### Drawer System
- Converted Modal to Drawer pattern
- Fixed Livewire dispatch format (`dispatch('event', ...)` syntax)
- Fixed `overflow: hidden` scroll lock when drawer opens
- Fixed flatpickr calendar initialization on drawer content

### Company Switching
- Prevented "All Companies" records from leaking across scoped sessions
- Fixed stale session data after company switch
- Gated role-restricted views by company context

### Infrastructure
- Cleaned up stale views (removed unused blade files)
- Fixed [`FieldFactory`](src/Factories/FieldTypes/FieldFactory.php) date mapping for `datepicker` field type
- Fixed flatpickr JavaScript initialization
- Published assets to consuming app (`php artisan vendor:publish`)
- Applied mobile responsive fixes

### Key Documentation
- [`plans/invitation-onboarding-consolidated-roadmap.md`](plans/invitation-onboarding-consolidated-roadmap.md) — §8 documents all fixes applied

---

## 2. Current Problem — Leave Request Wizard Empty Card

### Symptom
Navigating to the Leave Request Wizard renders a card with the title/step indicator but **no form fields**. The card body is empty.

### What We Know
- The [`WizardForm::isPresetField()`](src/Http/Livewire/Wizards/WizardForm.php:303) method is intact — the `presetFields` array is properly populated in [`applyPresetData()`](src/Http/Livewire/Wizards/WizardForm.php:284)
- The [`wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php:22) bypass for preset fields (lines 23-31) is safe — the `method_exists` guard prevents crashes even if someone removes `isPresetField`
- The session's changes did NOT cause this bug

### Most Likely Root Causes

1. **Config cache staleness** — [`ModelConfigRepository::get()`](src/Services/Config/ModelConfigRepository.php:38) uses `Cache::rememberForever()`. If `leave_request.php` data config was modified without clearing the cache, stale (or empty/incomplete) config is served. The `displayGroups` variable in the blade would be empty if `fieldGroups` returns `[]`.

2. **Module discovery failure** — The consuming app may not have run `php artisan ui-library:discover` after recent module config changes. If the Leave module's service provider didn't re-register configs, the wizard config key resolution fails.

3. **Config key mismatch** — The [`getModelConfigKey()`](src/Http/Livewire/Wizards/Wizard.php:297) method builds the config key as `{module}.{model_name_snake}` (e.g., `leave.leave_request`). If the actual config file path doesn't match this resolution, [`ModelConfigRepository::loadFromFile()`](src/Services/Config/ModelConfigRepository.php:116) would throw — but a caught exception could leave `fieldDefinitions` and `fieldGroups` as empty arrays.

4. **Step groups filtering** — In [`WizardForm::render()`](src/Http/Livewire/Wizards/WizardForm.php:875), if `stepGroups` is populated but references group keys that don't exist in `fieldGroups`, `$displayGroups` would be empty.

---

## 3. Key Files to Reference

### Consuming App (HR Module)

| File | Purpose |
|------|---------|
| `app/Modules/Leave/Http/Livewire/LeaveWizardForm.php` | Subclass of `WizardForm` with leave-specific overrides (conflict detection, leave type options, hints) |
| `app/Modules/Leave/Data/leave_request.php` | **The data config** — defines `fieldDefinitions`, `fieldGroups`, `hiddenFields`, `model` |
| `app/Modules/Leave/Data/wizards/employee_self_service.php` | **The wizard config** — defines `steps`, `models`, `completion`, linking rules |
| `app/Modules/Leave/Providers/LeaveServiceProvider.php` | Registers Livewire components, binds module services |

### UI Library (Read-Only Unless Absolutely Necessary)

| File | Purpose |
|------|---------|
| [`src/Http/Livewire/Wizards/Wizard.php`](src/Http/Livewire/Wizards/Wizard.php) | Parent wizard orchestrator — loads wizard config, manages steps, dispatches save events |
| [`src/Http/Livewire/Wizards/WizardForm.php`](src/Http/Livewire/Wizards/WizardForm.php) | Form component — loads model config via [`ConfigResolver`](src/Services/Config/ConfigResolver.php), initializes fields, handles save |
| [`src/Resources/views/livewire/wizards/wizard.blade.php`](src/Resources/views/livewire/wizards/wizard.blade.php) | Wizard blade — renders progress bar, embeds step form via `qf.wizard-form` |
| [`src/Resources/views/livewire/wizards/wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php) | Form blade — iterates `$displayGroups` and renders each field |
| [`src/Services/Config/ModelConfigRepository.php`](src/Services/Config/ModelConfigRepository.php) | Config loader — caches config arrays via `Cache::rememberForever()`, resolves file paths across consuming app + library |
| [`src/Services/Config/Wizards/WizardConfigResolver.php`](src/Services/Config/Wizards/WizardConfigResolver.php) | Wizard config reader — loads the wizard step configuration |
| [`src/Services/Config/ConfigResolver.php`](src/Services/Config/ConfigResolver.php) | Model config reader — loads field definitions, groups, hidden fields |

---

## 4. Architecture Rules (Non-Negotiable)

These rules are enforced by [`docs/library/25-library-independence-safeguards.md`](docs/library/25-library-independence-safeguards.md) and [`docs/library/27-architecture-boundary.md`](docs/library/27-architecture-boundary.md):

1. **Library MUST NOT reference any `App\Modules\*` namespace.** If you find yourself wanting to add an `App\Modules\Leave` reference in `src/`, STOP. Use a contract or stub pattern instead.

2. **All library interactions go through contracts** defined in [`src/Contracts/`](src/Contracts/).

3. **Consuming app modules must be self-contained** under `app/Modules/{ModuleName}/`. See [`docs/consuming-app/pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) §D for the exact directory structure.

4. **One owner per database table.** The Leave module owns its tables; the library never touches them directly.

5. **Contract/subclass/config-driven patterns**: Library defines contracts + stubs → consuming app implements/overrides → config files wire everything together.

6. **Before modifying library code**, apply the two-domain test from [`27-architecture-boundary.md`](docs/library/27-architecture-boundary.md:35): "Would this work identically for at least two unrelated business domains?"

---

## 5. Investigation & Fix Instructions

### Step 1 — Start in Architect Mode

Begin by analyzing the problem systematically. Do NOT jump to fixes.

### Step 2 — Trace the Config Loading Chain

Open and read these files in order:

1. **Wizard entry point** → [`wizard.blade.php`](src/Resources/views/livewire/wizards/wizard.blade.php:89-104) — confirm how the form component is embedded and what `$modelConfigKey` resolves to for the Leave step.

2. **Wizard config** → `app/Modules/Leave/Data/wizards/employee_self_service.php` — check the `steps` array, specifically the model class and `groups` key for each step.

3. **Model config key resolution** → [`getModelConfigKey()`](src/Http/Livewire/Wizards/Wizard.php:297) — trace how `"App\Modules\Leave\Models\LeaveRequest"` becomes the config key `"leave.leave_request"`.

4. **Config loading** → [`ModelConfigRepository::get()`](src/Services/Config/ModelConfigRepository.php:38) uses `Cache::rememberForever()`. This is the critical caching layer.

5. **Config file resolution** → [`ModelConfigRepository::loadFromFile()`](src/Services/Config/ModelConfigRepository.php:116) — for key `leave.leave_request`, the expected path is `app/Modules/Leave/Data/leave_request.php`.

6. **Data config** → `app/Modules/Leave/Data/leave_request.php` — confirm `fieldDefinitions`, `fieldGroups`, and `hiddenFields` are all defined.

7. **WizardForm initialization** → [`loadConfiguration()`](src/Http/Livewire/Wizards/WizardForm.php:198) populates `modelClass`, `fieldDefinitions`, `fieldGroups`, `hiddenFields`. Then [`initializeFields()`](src/Http/Livewire/Wizards/WizardForm.php:226) builds the `fields` array.

8. **Rendering** → [`WizardForm::render()`](src/Http/Livewire/Wizards/WizardForm.php:875) builds `$displayGroups` from `stepGroups` or `fieldGroups`.

### Step 3 — Identify the Root Cause

The empty card means `$displayGroups` is empty in [`wizard-form.blade.php`](src/Resources/views/livewire/wizards/wizard-form.blade.php:12). This can happen because:

| Cause | What to check |
|-------|---------------|
| **Cache** | Run `php artisan cache:clear && php artisan config:clear` in the consuming app |
| **Config file missing/wrong** | Verify `app/Modules/Leave/Data/leave_request.php` exists and returns an array with `fieldGroups` |
| **Config key mismatch** | Does `getModelConfigKey()` produce `leave.leave_request`? Does the file resolve? |
| **Step groups mismatch** | Do the `groups` keys in the wizard step config match keys in `fieldGroups`? |
| **LeaveWizardForm override** | Check if the subclass `mount()` or `render()` overrides break the chain |
| **Service provider** | Is `LeaveWizardForm` properly registered in `LeaveServiceProvider`? |

### Step 4 — Fix (Switch to Code Mode)

Once the root cause is identified:

- **If config cache**: Clear caches and document the flush call pattern
- **If config file issue**: Fix the data config file (in `app/Modules/Leave/Data/`) — this is the most likely fix location
- **If module discovery**: Run `php artisan ui-library:discover` and check the service provider registration
- **If library bug**: Only modify library files as a last resort, and only after confirming the two-domain test passes

### Step 5 — Verify

After the fix, confirm:
- [ ] Leave Request Wizard renders all form fields for each step
- [ ] Preset fields (e.g., `employee_id`) appear as read-only badges
- [ ] Date pickers, selects, and text inputs work correctly
- [ ] Save Draft and Save & Continue both work
- [ ] Field hints (showInfo, showDuration, showConflicts) render when configured

### Constraints
- Do NOT create files outside `app/Modules/Leave/`
- Do NOT modify library files unless absolutely necessary and the two-domain test passes
- All changes must follow the [`pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md)

---

## 6. Troubleshooting Cheat Sheet

### Quick Cache Flush (always try first)
```bash
cd /path/to/hr-consuming-app
php artisan cache:clear
php artisan config:clear
php artisan optimize:clear
```

### Check ModelConfigRepository cache keys
The cache key for `leave.leave_request` is `model_config_leave_leave_request`.

```bash
php artisan tinker
>>> Cache::get('model_config_index')
>>> Cache::get('model_config_leave_leave_request')
```

### Force re-load without cache
Temporarily wrap the `loadFromFile()` call in `ModelConfigRepository::get()` with a direct file read to bypass `Cache::rememberForever`.

### Check if displayGroups is empty
Add temporary logging in [`WizardForm::render()`](src/Http/Livewire/Wizards/WizardForm.php:875):
```php
\Log::info('WizardForm render', [
    'stepGroups' => $this->stepGroups,
    'fieldGroups' => $this->fieldGroups,
    'displayGroups' => $displayGroups,
]);
```

---

## 7. Related Documentation

| Document | Relevance |
|----------|-----------|
| [`docs/consuming-app/contracts.md`](docs/consuming-app/contracts.md) | Contract implementation patterns for consuming apps |
| [`docs/consuming-app/pre-coding-checklist.md`](docs/consuming-app/pre-coding-checklist.md) | Rules for creating views, components, modifying library code |
| [`docs/library/25-library-independence-safeguards.md`](docs/library/25-library-independence-safeguards.md) | Non-negotiable: library must not reference `App\Modules` |
| [`docs/library/27-architecture-boundary.md`](docs/library/27-architecture-boundary.md) | Library vs module decision rules, two-domain test |
| [`plans/invitation-onboarding-consolidated-roadmap.md`](plans/invitation-onboarding-consolidated-roadmap.md) | §8 — all fixes from the previous session |