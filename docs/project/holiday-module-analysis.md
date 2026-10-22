# Holiday Module — Analysis & Implementation Plan

> **Status**: Analysis complete — implementation pending
> **Last Updated**: 2026-10-22
> **Related**: [Loans module](../consuming-app/) · [Payroll module](payroll) · [Attendance module](attendance)

---

## Summary

The Holiday module (`app/Modules/Holiday/`) is ~70% complete. Core data model, calendar management, and cross-module integrations (Attendance + Leave) are solid. Remaining gaps are payroll integration, year rollover automation, and employee-facing features.

---

## Current State

### Models

| Model | Key Fields | Status |
|-------|-----------|--------|
| `Holiday` | `holiday_type`, `is_paid_holiday`, `holiday_pay_rate`, `minimum_hours_for_pay`, `is_half_day`, `is_recurring`, `recurrence_pattern`, `business_impact`, `eligible_employee_types` | ✅ Complete |
| `HolidayCalendar` | `name`, `year`, `country_code`, `region`, `applicable_to`, `is_default`, `is_active` | ✅ Complete |

### Integrations

| Integration | File | Status |
|-------------|------|--------|
| Attendance — holiday detection | [`AttendanceAggregator.php`](../../hr-consuming-app/app/Modules/Attendance/Services/AttendanceAggregator.php:112) | ✅ Working |
| Leave — calendar highlighting | [`LeaveCalendarEnhancementProvider.php`](../../hr-consuming-app/app/Modules/Leave/Services/LeaveCalendarEnhancementProvider.php:19) | ✅ Working |
| Leave — holiday overlap skip | [`LeaveAttendanceSync.php`](../../hr-consuming-app/app/Modules/Leave/Services/LeaveAttendanceSync.php:69) | ✅ Working |
| **Payroll — holiday pay rate** | — | ❌ **Missing** |

---

## Recommendations (Priority Order)

### 🔴 High — Payroll Integration

`holiday_pay_rate` (1.0x–3.0x) exists but isn't applied by the Payroll module.

**Action**: Detect `status = 'holiday'` attendance records in `PayrollCalculator` and apply `holiday_pay_rate` multiplier.

### 🔴 High — Year Rollover

Recurring holidays don't auto-generate for the next year.

**Action**: Create `HolidayYearRollover` service + scheduled command (Dec 1). Extract "copy previous year" logic from the batch creation wizard.

### 🟡 Medium — Public Holiday API / Templates

Batch wizard references `getAvailableHolidayTemplates` but no implementation exists.

**Action**: Implement country templates or integrate with a free API.

### 🟡 Medium — Employee Self-Service

No dedicated holiday view in the ESS portal.

**Action**: Add "Upcoming Holidays" widget to My Portal dashboard.

### 🟡 Medium — Notifications

No upcoming holiday notifications.

**Action**: Add scheduled command for 7-14 day holiday reminders.

### 🟢 Low — Model Consistency

Holiday module references `Organization\Models\Company`; other modules use `Hr\Models\Company`.

**Action**: Standardize.

---

## Competitor Comparison

| Feature | BambooHR | Personio | Zoho People | This Module |
|---|---|---|---|---|
| Public holiday API | ✅ | ✅ | ✅ | ❌ |
| Year rollover | ✅ | ✅ | ✅ | ❌ |
| Holiday pay rates | ✅ | ✅ | ✅ | Field exists, unused |
| Payroll integration | ✅ | ✅ | ✅ | ❌ |
| ESS calendar | ✅ | ✅ | ✅ | ❌ |
| Notifications | ✅ | ✅ | ✅ | ❌ |
| Holiday calendars | ✅ | ✅ | ✅ | ✅ |
| Recurring holidays | ✅ | ✅ | ✅ | ✅ |
| Observed dates | ✅ | ✅ | ✅ | ✅ |
| Half-day | ✅ | ✅ | ✅ | ✅ |
| Conflict detection | ✅ | ✅ | ✅ | ✅ |
| Multi-country | ✅ | ✅ | ✅ | ✅ |
