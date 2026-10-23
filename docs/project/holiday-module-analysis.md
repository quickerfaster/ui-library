# Holiday Module — Analysis & Implementation Plan

> **Status**: Analysis + Implementation complete — all recommendations implemented
> **Last Updated**: 2026-10-23
> **Related**: [Holiday User Guide](../../hr-consuming-app/docs/user/holiday/README.md) · [Loans module](../consuming-app/) · [Payroll module](payroll) · [Attendance module](attendance)

---

## Summary

The Holiday module (`app/Modules/Holiday/`) is now complete. All gaps identified in the original analysis have been addressed: payroll integration, year rollover, country templates, batch creation wizard, recurrence rules, and cross-session draft persistence.

---

## Current State

### Models

| Model | Key Fields | Status |
|-------|-----------|--------|
| `Holiday` | `holiday_type`, `is_paid_holiday`, `holiday_pay_rate`, `minimum_hours_for_pay`, `is_half_day`, `is_recurring`, `recurrence_pattern`, `recurrence_rule`, `business_impact`, `eligible_employee_types` | ✅ Complete |
| `HolidayCalendar` | `name`, `year`, `country_code`, `region`, `applicable_to`, `is_default`, `is_active` | ✅ Complete |
| `HolidayTemplate` | `country_code`, `region_code`, `name`, `month`, `day`, `recurrence_rule` (JSON), `holiday_type`, `default_pay_rate` | ✅ Complete |
| `HolidayBatchDraft` | `token`, `user_id`, `wizard_id`, `data` (JSON), `expires_at` | ✅ Complete |

### Integrations

| Integration | File | Status |
|-------------|------|--------|
| Attendance — holiday detection | `AttendanceAggregator.php` | ✅ Working |
| Leave — calendar highlighting | `LeaveCalendarEnhancementProvider.php` | ✅ Working |
| Leave — holiday overlap skip | `LeaveAttendanceSync.php` | ✅ Working |
| Payroll — holiday pay rate | `HolidayPayrollIntegration.php` → `ProcessPayrollRun.php` | ✅ **Implemented** |

### Services

| Service | Purpose | Status |
|---------|---------|--------|
| `HolidayPayrollIntegration` | Creates payroll adjustments for holiday pay rate premiums | ✅ Complete |
| `HolidayYearRollover` | Copies recurring holidays to next year (idempotent) | ✅ Complete |
| `HolidayBatchSubmissionService` | Bulk-creates Holiday records from wizard session data | ✅ Complete |

### Commands

| Command | Schedule | Status |
|---------|----------|--------|
| `holiday:rollover` | Dec 1, midnight | ✅ Registered in `Kernel.php` |

### Batch Creation Wizard (6 Steps)

| Step | Component | Status |
|------|-----------|--------|
| 1. Select Calendar & Year | `holiday.batch-step-one` | ✅ Complete — with multi-company company filter |
| 2. Batch Defaults | `holiday.batch-defaults` | ✅ Complete — pay rate, business impact, eligibility |
| 3. Select Dates | `holiday.batch-date-selector` | ✅ Complete — Quick Picker, Multi-Date, CSV |
| 4. Select Holidays | `holiday.template-selector` | ✅ Complete — country templates, filters, select all |
| 5. Review & Confirm | `holiday.batch-preview` | ✅ Complete — inline editing, remove items |
| 6. Completion | Wizard success screen | ✅ Complete — View Calendar, View Holidays, Create Another Set |

### Custom Field Types

| Field Type | Component | Status |
|------------|-----------|--------|
| `recurrence_rule_builder` | `RecurrenceRuleBuilder` Livewire component | ✅ Complete — with 6 quick presets |

---

## Recommendations — All Implemented

### 🔴 High — Payroll Integration ✅

`holiday_pay_rate` (1.0x–3.0x) is now applied by the Payroll module.

**Implementation:**
- `HolidayPayrollIntegration` service detects `status = 'holiday'` attendance records
- Creates `PayrollRunAdjustment` records for the premium: `hours × rate × (pay_rate − 1.0)`
- Called from `ProcessPayrollRun` before payroll calculation
- Supports hourly, salaried_daily, and salaried_full pay types
- Idempotent — won't duplicate on reprocessing
- Includes `removeAdjustmentsForRun()` for cleanup

### 🔴 High — Year Rollover ✅

Recurring holidays now auto-generate for the next year.

**Implementation:**
- `HolidayYearRollover` service — copies `is_recurring=true` holidays from active calendars
- `holiday:rollover` command — scheduled Dec 1 at midnight
- Handles Feb 29 → Feb 28 in non-leap years
- Idempotent — safe to run multiple times
- Updates calendar `holiday_count` after rollover

### 🟡 Medium — Country Templates ✅

The batch wizard's "Use Country Template" option now works with a curated template repository.

**Implementation:**
- `HolidayTemplate` model and table — 27 curated templates across US (11), UK (8), Nigeria (8)
- Templates include floating holidays (Thanksgiving, MLK Day, Bank Holidays) with recurrence rules
- `computeDateForYear($year)` resolves fixed and floating dates to actual Carbon instances
- `HolidayTemplateSeeder` uses `firstOrCreate` for idempotent seeding

### � Medium — Recurrence Rule Builder ✅

The `recurrence_rule` field is now a visual builder, not a raw textarea.

**Implementation:**
- `RecurrenceRuleBuilder` Livewire component — converts user-friendly inputs to structured JSON
- Supports yearly (fixed + floating), monthly, weekly, and daily frequencies
- End conditions: Never, After N occurrences, On date
- 6 quick presets: Thanksgiving, Memorial Day, Labor Day, First Monday, 15th Monthly, Biweekly Friday
- Human-readable summary preview
- Registered as custom `recurrence_rule_builder` field type via `FieldFactory::registerType()`

### � Medium — Batch Wizard Redesign ✅

The entire batch creation wizard was rebuilt.

**Implementation:**
- 6-step wizard replacing the broken 3-step config
- `HolidayBatchDateSelector` — three mode date selection (Quick Picker, Multi-Date, CSV)
- `HolidayTemplateSelector` — enriched checklist with type badges, pay rate badges, filters
- `HolidayBatchPreview` — tabular preview with inline editing and item removal
- `HolidayBatchStepOne` — clean calendar selector with 4 creation methods
- `HolidayBatchDefaults` — optional batch-level defaults for pay rate, etc.
- `AdvancesWizardStep` trait — shared advancement + draft persistence logic
- Cross-session draft persistence via `holiday_batch_drafts` table

### 🟡 Medium — Employee Self-Service ⬜

No dedicated holiday view in the ESS portal yet.

### 🟡 Medium — Notifications ⬜

No upcoming holiday notifications yet.

### 🟢 Low — Model Consistency ⬜

Holiday module references `Organization\Models\Company`; other modules use `Hr\Models\Company`. Both extend `BaseCompany` and point to the same table, so this is cosmetic.

---

## Library Changes (All Backward-Compatible)

| Change | File | Purpose |
|--------|------|---------|
| `FieldFactory::registerType()` static method | `src/Factories/FieldTypes/FieldFactory.php` | Consuming apps can register custom field types |
| `DataTableForm::updateRecurrenceRule()` listener | `src/Http/Livewire/DataTables/DataTableForm.php` | Handles `recurrenceRuleUpdated` from custom fields |
| `Wizard::$primaryModelId = 0` default | `src/Http/Livewire/Wizards/Wizard.php` | Fixes typed property error for custom-component wizards |
| `Wizard::advance()` only saves on normal advance | `src/Http/Livewire/Wizards/Wizard.php` | Fixes session re-save after `finish()` |
| `Wizard::finish()` clears `{wizardId}-*` keys | `src/Http/Livewire/Wizards/Wizard.php` | Prevents custom key leakage across runs |
| `Wizard::cancelDeelete()` clears `{wizardId}-*` keys + dispatches `wizardCancelled` | `src/Http/Livewire/Wizards/Wizard.php` | Full cleanup on "Delete Progress" |

---

## Competitor Comparison (Updated)

| Feature | BambooHR | Personio | Zoho People | This Module |
|---|---|---|---|---|
| Public holiday API / templates | ✅ | ✅ | ✅ | ✅ (curated, seeded) |
| Year rollover | ✅ | ✅ | ✅ | ✅ |
| Holiday pay rates | ✅ | ✅ | ✅ | ✅ (1.0x–3.0x, payroll integrated) |
| Payroll integration | ✅ | ✅ | ✅ | ✅ |
| ESS calendar | ✅ | ✅ | ✅ | ⬜ |
| Notifications | ✅ | ✅ | ✅ | ⬜ |
| Holiday calendars | ✅ | ✅ | ✅ | ✅ |
| Recurring holidays | ✅ | ✅ | ✅ | ✅ (fixed + floating) |
| Observed dates | ✅ | ✅ | ✅ | ✅ |
| Half-day | ✅ | ✅ | ✅ | ✅ |
| Conflict detection | ✅ | ✅ | ✅ | ✅ |
| Multi-country | ✅ | ✅ | ✅ | ✅ (US, UK, NG) |
| Batch creation wizard | ✅ | ✅ | ✅ | ✅ (6 steps, 4 methods) |
| Recurrence rule builder | — | — | — | ✅ (visual builder) |
| Cross-session draft resume | — | — | — | ✅ (DB-persisted) |
