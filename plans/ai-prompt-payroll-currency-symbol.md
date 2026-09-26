# AI Prompt — Payroll Currency Symbol Configuration

## Context

You are working on the QuickerFaster UI Library (quicker-faster/ui-library), a Laravel + Livewire 3 + Bootstrap 5 package at `/Users/mac/Projects/Libraries/ui-library`. The consuming app is at `/Users/mac/Projects/LaravelProjects/hr-consuming-app`.

## Library Philosophy (Critical — Do Not Violate)

From `docs/library/pilosophy.txt`:
- The library must be completely decoupled from the consuming app. The consuming app can depend on the library, but never the reverse.
- Convention over configuration — modules follow predictable folder conventions.
- qf namespace convention — all library assets use the qf prefix (views: `qf::`, Blade: `<x-qf::>`, Livewire: `qf.`).
- The consuming app must be self-contained: all files for a module (models, migrations, services, components, views) live inside that module's directory under `app/Modules/{ModuleName}/`.
 
## The Problem

The payroll module displays currency symbols inconsistently:

1. **Hardcoded "N" (Naira) in routes**: [`web.php`](app/Modules/Payroll/Routes/web.php:76) and line 134 have `$currencySymbol = "N"` — hardcoded Naira symbol
2. **Hardcoded symbol map in library**: [`HasCurrencySymbol.php`](src/Traits/HasCurrencySymbol.php:9) has a fixed array of 16 currency codes → symbols. Cannot be extended without modifying the library.
3. **No user-facing setting**: The currency symbol cannot be changed by the admin without code changes
4. **Per-company currency**: Different companies may use different currencies (e.g., Test Company uses NGN, Alpha Holdings uses USD)

## Existing Infrastructure

### Settings Panel (Library)

[`SettingsPanel.php`](src/Http/Livewire/Settings/SettingsPanel.php) — A Livewire component that renders a settings form with groups and fields. Supports three modes:
- `user` — My Preferences (user-level settings)
- `system` — General Settings (system-level)
- `company` — Company Settings (company-level)
- **Module context mode**: When `$context` and `$moduleName` are set, loads settings from `app/Modules/{ModuleName}/Config/settings.php` under `contexts.{context}.groups`

### HR Module Settings (Reference Implementation)

[`settings.php`](app/Modules/Hr/Config/settings.php) — HR module's settings config with one group "Auto-Generation" under the "people" context. Contains an `employee_number_pattern` text field.

The settings panel is accessed via sidebar link — HR places it as "People Settings" under the "people" context group.

### Sidebar/Navigation

The payroll module's navigation is configured in [`app/Modules/Payroll/Config/navigation.php`](app/Modules/Payroll/Config/navigation.php). It has a "processing" context group. Settings links follow the pattern of adding a sidebar item that opens a drawer with `<livewire:qf.settings-panel ... />`.

### Currency Symbol Usage

The [`HasCurrencySymbol`](src/Traits/HasCurrencySymbol.php) trait is used by:
- [`PayrollWizardPreview.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardPreview.php:28)
- [`PayrollWizardAdjustments.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardAdjustments.php:23)
- [`PayrollRunDetail.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php:18)
- [`PayslipItems.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayslipItems.php:11)

The symbol appears in:
- Wizard step 1 (payroll detail)
- Wizard step 2 (one-time adjustments)  
- Wizard step 3 (review & preview)
- Payroll run detail page
- Payslip items modal
- PDF exports and prints
- Email notifications

### Company Model

Companies have no `currency_code` or `default_currency` field currently. The currency comes from:
- `pay_schedules.currency_code`
- `employee_positions.salary_currency`
- `payroll_runs.base_currency`

## Analysis Required

Analyze, report, and recommend the best approach for making the currency symbol configurable. Consider these options:

### Option 1: Module Settings Panel (Recommended Pattern)

Place "Payroll Settings" on the sidebar of the "processing" context group in the payroll module, using the existing `SettingsPanel` component with a `settings.php` config file. This follows the exact pattern used by the HR module's "People Settings."

**Pros**: Consistent with existing architecture; no new components needed; familiar UX for users; supports per-company settings via `mode='company'`
**Cons**: Settings are per-module, not global

### Option 2: Configuration File Only

Add a `payroll.php` config file with currency settings. Simpler but requires code edits to change.

**Pros**: Simplest implementation
**Cons**: No UI; requires developer intervention to change

### Option 3: New "Settings" Context Group

Add a dedicated "settings" context group to the payroll sidebar for future payroll-specific settings (currency, tax policies, pension defaults, etc.).

**Pros**: Future-proof; can house multiple setting categories
**Cons**: More navigation clutter; may be premature

### Option 4: Company-Level Currency on Company Model

Add `currency_code` to the `companies` table. The payroll run inherits the company's currency. No dedicated settings UI needed — the currency is set when creating/editing a company.

**Pros**: Currency is a company attribute (data, not settings); follows competitor approach (QuickBooks, Xero)
**Cons**: Requires migration + model changes

### Option 5: Hybrid — Company Currency + Settings Override

Companies have a default currency (Option 4), but the payroll settings panel allows overriding per pay schedule or per payroll run.

**Pros**: Most flexible; matches Gusto/ADP approach
**Cons**: Most complex

## Evaluation Criteria

Analyze each option against:
1. **User expectation** — What do payroll administrators expect? (Competitor analysis: ADP, Gusto, QuickBooks, Xero)
2. **Best practice** — Is currency a "setting" or "data"? Where does it belong architecturally?
3. **Usability** — How many clicks to change? Is it discoverable?
4. **Library philosophy** — Does it keep the library decoupled? Can the module be plug-and-play?
5. **Scalability** — Does it support per-company, per-pay-schedule, or per-run currency?
6. **Implementation effort** — Files to create/modify, migration complexity

## Deliverables

1. Analysis of each option against the 6 criteria above
2. Clear recommendation with justification
3. If the recommendation involves settings: the `settings.php` config structure
4. If the recommendation involves company currency: migration + model changes
5. Pre-implementation checklist (files to read, dependencies to check)
6. Post-implementation checklist (verification steps)

## Key Files Reference

| File | Purpose |
|------|---------|
| [`HasCurrencySymbol.php`](src/Traits/HasCurrencySymbol.php) | Library trait — hardcoded currency map |
| [`SettingsPanel.php`](src/Http/Livewire/Settings/SettingsPanel.php) | Library settings component |
| [`settings.php`](app/Modules/Hr/Config/settings.php) | HR module settings (reference) |
| [`web.php`](app/Modules/Payroll/Routes/web.php:76) | Hardcoded "N" symbol |
| [`PayrollRunDetail.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollRunDetail.php) | Uses HasCurrencySymbol |
| [`PayrollWizardPreview.php`](app/Modules/Payroll/Http/Livewire/Payroll/PayrollWizardPreview.php) | Uses HasCurrencySymbol |
| [`navigation.php`](app/Modules/Payroll/Config/navigation.php) | Payroll sidebar config |
| [`Company.php`](app/Modules/Hr/Models/Company.php) | Company model (no currency field) |
| [`pilosophy.txt`](docs/library/pilosophy.txt) | Library philosophy |
| [`debug-checklist.md`](docs/debug-checklist.md) | Debug reference — update with findings |

## Previous Session Context

In the previous session, the following payroll fixes were completed:
- §19: Validation errors (trimFields, soft-delete unique rules, generateField guard)
- §19b: Onboarding wizard (removed autoCreatePosition, unified setOnboardingStatus)
- §26: Payroll module — pay_schedule_id migrated to employee_payroll_profiles, synchronous dispatch, atomic payslip sequence, mark-paid cascade, config key fix

See [`docs/debug-checklist.md`](docs/debug-checklist.md) §19, §19b, and §26 for details.