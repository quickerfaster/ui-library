# Phase A.0 — Inventory Freeze (Source of Truth)

> **Status**: FROZEN — this document is the authoritative file map for all subsequent phases.
> **Date**: 2026-08-18
> **Phase**: A.0 — Preflight & Baseline (read-only; no files modified)
> **Plan**: [`docs/project/implementation-plan-organization-hr-split.md`](implementation-plan-organization-hr-split.md)

---

## 1. Summary Counts

| Category | Count | Location |
|---|---|---|
| Library Organization files | **43** | `src/Core/Organization/` |
| HR module total files (excl. `.DS_Store`) | **434** | `app/Modules/Hr/` |
| HR Models | **45** | `app/Modules/Hr/Models/` |
| HR Data configs | **62** | `app/Modules/Hr/Data/` |
| HR Migrations | **40** | `app/Modules/Hr/Database/Migrations/` |

Notes on counts:
- `app/Modules/Hr/` also contains one stray `.DS_Store` file (excluded from the 434; raw `find -type f` reports **435**).
- `app/Modules/Hr/Models/` contains exactly **45** `.php` files — confirming the plan's authoritative count (not 43 or 44).

---

## 2. Library Organization Domain — Full File Listing (43)

`src/Core/Organization/` — 43 files across 7 directories:

### Config (5)
- `src/Core/Organization/Config/bottom_bar_menu.php`
- `src/Core/Organization/Config/navigation.php`
- `src/Core/Organization/Config/settings.php`
- `src/Core/Organization/Config/sidebar_menu.php`
- `src/Core/Organization/Config/top_bar_menu.php`

### Data (11)
- `src/Core/Organization/Data/branch.php`
- `src/Core/Organization/Data/business_unit.php`
- `src/Core/Organization/Data/company.php`
- `src/Core/Organization/Data/dashboard.php`
- `src/Core/Organization/Data/department.php`
- `src/Core/Organization/Data/division.php`
- `src/Core/Organization/Data/location.php`
- `src/Core/Organization/Data/team.php`
- `src/Core/Organization/Data/dashboards/dashboard_companies_overview.php`
- `src/Core/Organization/Data/dashboards/dashboard_locations_overview.php`
- `src/Core/Organization/Data/dashboards/dashboard_structure_overview.php`

### Database/Migrations (7)
- `src/Core/Organization/Database/Migrations/2026_06_11_000003_create_companies_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000004_create_branches_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000005_create_departments_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000006_create_divisions_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000007_create_business_units_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000008_create_locations_table.php`
- `src/Core/Organization/Database/Migrations/2026_06_11_000009_create_teams_table.php`

### Database/Seeders (1)
- `src/Core/Organization/Database/Seeders/OrganizationSeeder.php`

### Models (7)
- `src/Core/Organization/Models/Branch.php`
- `src/Core/Organization/Models/BusinessUnit.php`
- `src/Core/Organization/Models/Company.php`
- `src/Core/Organization/Models/Department.php`
- `src/Core/Organization/Models/Division.php`
- `src/Core/Organization/Models/Location.php`
- `src/Core/Organization/Models/Team.php`

### Resources/views/organization (11)
- `src/Core/Organization/Resources/views/organization/branches.blade.php`
- `src/Core/Organization/Resources/views/organization/business-units.blade.php`
- `src/Core/Organization/Resources/views/organization/companies.blade.php`
- `src/Core/Organization/Resources/views/organization/dashboard.blade.php`
- `src/Core/Organization/Resources/views/organization/dashboard-companies-overview.blade.php`
- `src/Core/Organization/Resources/views/organization/dashboard-locations-overview.blade.php`
- `src/Core/Organization/Resources/views/organization/dashboard-structure-overview.blade.php`
- `src/Core/Organization/Resources/views/organization/departments.blade.php`
- `src/Core/Organization/Resources/views/organization/divisions.blade.php`
- `src/Core/Organization/Resources/views/organization/locations.blade.php`
- `src/Core/Organization/Resources/views/organization/teams.blade.php`

### Routes (1)
- `src/Core/Organization/Routes/web.php`

---

## 3. HR Module — Full File Listing (434)

`/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/` — 434 files (excluding `.DS_Store`), by subdirectory:

### Commands (1)
- `Commands/SyncLeaveAttendance.php`

### Config (4)
- `Config/navigation.php`
- `Config/quick_hr_payroll.php`
- `Config/settings.php`
- `Config/workflows.php`

### Data (62) — see §5 for full list

### Database/Factories (12)
- `Database/Factories/AttendanceFactory.php`
- `Database/Factories/AttendancePolicyFactory.php`
- `Database/Factories/ClockEventFactory.php`
- `Database/Factories/CompanyFactory.php`
- `Database/Factories/DepartmentFactory.php`
- `Database/Factories/EmployeeFactory.php`
- `Database/Factories/EmployeePositionFactory.php`
- `Database/Factories/JobTitleFactory.php`
- `Database/Factories/LocationFactory.php`
- `Database/Factories/ShiftFactory.php`
- `Database/Factories/ShiftScheduleFactory.php`
- `Database/Factories/WorkPatternFactory.php`

### Database/Migrations (40) — see §6 for full list

### Database/Seeders (7)
- `Database/Seeders/BonusAndDeductionTypeSeeders.php`
- `Database/Seeders/EmployeeWithDependenciesSeeder.php`
- `Database/Seeders/JobTitleSeeder.php`
- `Database/Seeders/MultiCompanyPayrollTestDataSeeder.php`
- `Database/Seeders/OnboardingTaskSeeder.php`
- `Database/Seeders/WorkDaySeeder.php`
- `Database/Seeders/WorkShiftSeeder.php`

### Events (1)
- `Events/PayrollRunEvent.php`

### Exports (1)
- `Exports/PayrollRunSummaryExport.php`

### Http/Controllers (9)
- `Http/Controllers/ClockEventController.php`
- `Http/Controllers/EmployeePrintController.php`
- `Http/Controllers/EmployeeProfileController.php`
- `Http/Controllers/IdCardController.php`
- `Http/Controllers/PayrollReportController.php`
- `Http/Controllers/PayrollRunController.php`
- `Http/Controllers/PayslipController.php`
- `Http/Controllers/UserSyncController.php`
- `Http/Controllers/Payrolls/BankFileController.php`

### Http/Livewire/Payroll (7)
- `Http/Livewire/Payroll/PayrollExecutiveSummary.php`
- `Http/Livewire/Payroll/PayrollRunDetail.php`
- `Http/Livewire/Payroll/PayrollRunWizard.php`
- `Http/Livewire/Payroll/PayrollWizardAdjustments.php`
- `Http/Livewire/Payroll/PayrollWizardPreview.php`
- `Http/Livewire/Payroll/PayslipItems.php`
- `Http/Livewire/Payroll/PolicyCalculationBuilder.php`

### Jobs (5)
- `Jobs/ProcessAttendanceJob.php`
- `Jobs/Payrolls/FinalizePayrollRun.php`
- `Jobs/Payrolls/GeneratePayrollRunSummaryPdf.php`
- `Jobs/Payrolls/ProcessEmployeeBatch.php`
- `Jobs/Payrolls/ProcessPayrollRun.php`

### Listeners (3)
- `Listeners/AttendanceEventListener.php`
- `Listeners/LeaveRequestEventListener.php`
- `Listeners/PayrollRunEventListener.php`

### Models (45) — see §4 for full list

### Providers (1)
- `Providers/HrsServiceProvider.php`

### Routes (2)
- `Routes/api.php`
- `Routes/web.php`

### Services (8)
- `Services/AttendanceAggregator.php`
- `Services/AttendanceCalculator.php`
- `Services/LeaveAccrualService.php`
- `Services/LeaveAttendanceSync.php`
- `Services/Payroll/PayrollCalculator.php`
- `Services/PayrollReportService.php`
- `Services/PayrollRunProcessor.php`
- `Services/PayslipService.php`

### Tests/Unit (3)
- `Tests/Unit/AttendanceCalculatorStatusTest.php`
- `Tests/Unit/AttendanceCalculatorTest.php`
- `Tests/Unit/PayrollCalculatorTest.php`

### Traits (2)
- `Traits/HandlesAttendanceRecord.php`
- `Traits/HasPayPeriods.php`

### Resources/views (221)

Top-level entity/dashboard/wizard views:
- `Resources/views/adjust-attendance.blade.php`
- `Resources/views/attendance-adjustments.blade.php`
- `Resources/views/attendance-overviews.blade.php`
- `Resources/views/attendance-policies.blade.php`
- `Resources/views/attendance-policies/create.blade.php`
- `Resources/views/attendance-policies/edit.blade.php`
- `Resources/views/attendance-policies/show.blade.php`
- `Resources/views/attendance-sessions.blade.php`
- `Resources/views/attendance-work-sessions.blade.php`
- `Resources/views/attendances.blade.php`
- `Resources/views/attendances/create.blade.php`
- `Resources/views/attendances/edit.blade.php`
- `Resources/views/attendances/show.blade.php`
- `Resources/views/clock-events.blade.php`
- `Resources/views/companies.blade.php`
- `Resources/views/company-profile-overviews.blade.php`
- `Resources/views/dashboard.blade.php`
- `Resources/views/dashboard-employee-overview.blade.php`
- `Resources/views/dashboard-leave-overview.blade.php`
- `Resources/views/dashboard-organization-overview.blade.php`
- `Resources/views/dashboard-payroll-overview.blade.php`
- `Resources/views/dashboard-people-overview.blade.php`
- `Resources/views/dashboard-policies-overview.blade.php`
- `Resources/views/dashboard-time-overview.blade.php`
- `Resources/views/departments.blade.php`
- `Resources/views/documents.blade.php`
- `Resources/views/employee-adjustment-profiles.blade.php`
- `Resources/views/employee-groups.blade.php`
- `Resources/views/employee-job-histories.blade.php`
- `Resources/views/employee-job-histories/create.blade.php`
- `Resources/views/employee-job-histories/edit.blade.php`
- `Resources/views/employee-job-histories/show.blade.php`
- `Resources/views/employee-onboarding.blade.php`
- `Resources/views/employee-payroll-profiles.blade.php`
- `Resources/views/employee-payroll-profiles/create.blade.php`
- `Resources/views/employee-payroll-profiles/edit.blade.php`
- `Resources/views/employee-payroll-profiles/show.blade.php`
- `Resources/views/employee-positions.blade.php`
- `Resources/views/employee-positions/create.blade.php`
- `Resources/views/employee-positions/edit.blade.php`
- `Resources/views/employee-positions/show.blade.php`
- `Resources/views/employee-profiles.blade.php`
- `Resources/views/employee-profiles/create.blade.php`
- `Resources/views/employee-profiles/edit.blade.php`
- `Resources/views/employee-profiles/show.blade.php`
- `Resources/views/employee-self-service.blade.php`
- `Resources/views/employee-work-patterns.blade.php`
- `Resources/views/employees.blade.php`
- `Resources/views/employees/create.blade.php`
- `Resources/views/employees/edit.blade.php`
- `Resources/views/employees/print.blade.php`
- `Resources/views/employees/show.blade.php`
- `Resources/views/holiday-batch-creation.blade.php`
- `Resources/views/holiday-calendars.blade.php`
- `Resources/views/holidays.blade.php`
- `Resources/views/holidays/create.blade.php`
- `Resources/views/holidays/edit.blade.php`
- `Resources/views/holidays/show.blade.php`
- `Resources/views/job-titles.blade.php`
- `Resources/views/leave-approvers.blade.php`
- `Resources/views/leave-balances.blade.php`
- `Resources/views/leave-overviews.blade.php`
- `Resources/views/leave-requests.blade.php`
- `Resources/views/leave-types.blade.php`
- `Resources/views/locations.blade.php`
- `Resources/views/locations/create.blade.php`
- `Resources/views/locations/edit.blade.php`
- `Resources/views/locations/show.blade.php`
- `Resources/views/my-account.blade.php`
- `Resources/views/my-leave.blade.php`
- `Resources/views/my-preferences.blade.php`
- `Resources/views/my-profile.blade.php`
- `Resources/views/pay-schedules.blade.php`
- `Resources/views/pay-schedules/create.blade.php`
- `Resources/views/pay-schedules/edit.blade.php`
- `Resources/views/pay-schedules/show.blade.php`
- `Resources/views/payroll-overviews.blade.php`
- `Resources/views/payroll-payslips.blade.php`
- `Resources/views/payroll-payslips/create.blade.php`
- `Resources/views/payroll-payslips/edit.blade.php`
- `Resources/views/payroll-payslips/show.blade.php`
- `Resources/views/payroll-policies.blade.php`
- `Resources/views/payroll-policies/create.blade.php`
- `Resources/views/payroll-policies/edit.blade.php`
- `Resources/views/payroll-policies/show.blade.php`
- `Resources/views/payroll-policy-assignments.blade.php`
- `Resources/views/payroll-run-adjustments.blade.php`
- `Resources/views/payroll-run-wizard.blade.php`
- `Resources/views/payroll-runs.blade.php`
- `Resources/views/payroll-runs/create.blade.php`
- `Resources/views/payroll-runs/edit.blade.php`
- `Resources/views/payroll-runs/show.blade.php`
- `Resources/views/payroll-wizard.blade.php`
- `Resources/views/payslip-items.blade.php`
- `Resources/views/people-overviews.blade.php`
- `Resources/views/policy-assignments.blade.php`
- `Resources/views/policy-overviews.blade.php`
- `Resources/views/saved-reports.blade.php`
- `Resources/views/shift-schedules.blade.php`
- `Resources/views/shifts.blade.php`
- `Resources/views/sick-call-report.blade.php`
- `Resources/views/tags.blade.php`
- `Resources/views/teams.blade.php`
- `Resources/views/test-action-card.blade.php`
- `Resources/views/test-balance-card.blade.php`
- `Resources/views/test-status-card.blade.php`
- `Resources/views/work-patterns.blade.php`
- `Resources/views/work-patterns/create.blade.php`
- `Resources/views/work-patterns/edit.blade.php`
- `Resources/views/work-patterns/show.blade.php`

`Resources/views/components/layouts/navbars/auth/` (93 — tab-bar/sidebar/top-nav/bottom-nav link partials):
- `attendance-adjustments-tab-bar-links.blade.php`
- `attendance-overviews-tab-bar-links.blade.php`
- `attendance-sessions-tab-bar-links.blade.php`
- `bottom-bar-links.blade.php`
- `clock-events-tab-bar-links.blade.php`
- `companies-tab-bar-links.blade.php`
- `company-profile-overviews-tab-bar-links.blade.php`
- `departments-tab-bar-links.blade.php`
- `documents-tab-bar-links.blade.php`
- `employee-adjustment-profiles-tab-bar-links.blade.php`
- `employee-groups-tab-bar-links.blade.php`
- `employee-work-patterns-tab-bar-links.blade.php`
- `holiday-calendars-tab-bar-links.blade.php`
- `job-titles-tab-bar-links.blade.php`
- `leave-approvers-tab-bar-links.blade.php`
- `leave-balances-tab-bar-links.blade.php`
- `leave-overviews-tab-bar-links.blade.php`
- `leave-requests-tab-bar-links.blade.php`
- `leave-types-tab-bar-links.blade.php`
- `payroll-overviews-tab-bar-links.blade.php`
- `payroll-policy-assignments-tab-bar-links.blade.php`
- `payroll-run-adjustments-tab-bar-links.blade.php`
- `payslip-items-tab-bar-links.blade.php`
- `people-overviews-tab-bar-links.blade.php`
- `policy-assignments-tab-bar-links.blade.php`
- `policy-overviews-tab-bar-links.blade.php`
- `saved-reports-tab-bar-links.blade.php`
- `shift-schedules-tab-bar-links.blade.php`
- `shifts-tab-bar-links.blade.php`
- `sidebar-links.blade.php`
- `sidebar-post-links.blade.php`
- `sidebar-pre-links.blade.php`
- `tags-tab-bar-links.blade.php`
- `teams-tab-bar-links.blade.php`
- `top-nav-links.blade.php`
- `top-nav-post-links.blade.php`
- `top-nav-pre-links.blade.php`
- `company profile/bottom-bar-links.blade.php`
- `company profile/sidebar-links.blade.php`
- `company profile/sidebar-post-links.blade.php`
- `company profile/sidebar-pre-links.blade.php`
- `leave/bottom-bar-links.blade.php`
- `leave/sidebar-links.blade.php`
- `leave/sidebar-post-links.blade.php`
- `leave/sidebar-pre-links.blade.php`
- `payroll/bottom-bar-links.blade.php`
- `payroll/sidebar-links.blade.php`
- `payroll/sidebar-post-links.blade.php`
- `payroll/sidebar-pre-links.blade.php`
- `people/bottom-bar-links.blade.php`
- `people/sidebar-links.blade.php`
- `people/sidebar-post-links.blade.php`
- `people/sidebar-pre-links.blade.php`
- `policies/bottom-bar-links.blade.php`
- `policies/sidebar-links.blade.php`
- `policies/sidebar-post-links.blade.php`
- `policies/sidebar-pre-links.blade.php`
- `time/bottom-bar-links.blade.php`
- `time/sidebar-links.blade.php`
- `time/sidebar-post-links.blade.php`
- `time/sidebar-pre-links.blade.php`
- `xxxxxx/attendance-adjustments-tab-bar-links.blade.php`
- `xxxxxx/attendance-overviews-tab-bar-links.blade.php`
- `xxxxxx/attendance-sessions-tab-bar-links.blade.php`
- `xxxxxx/bottom-bar-links.blade.php`
- `xxxxxx/clock-events-tab-bar-links.blade.php`
- `xxxxxx/documents-tab-bar-links.blade.php`
- `xxxxxx/employee-adjustment-profiles-tab-bar-links.blade.php`
- `xxxxxx/employee-groups-tab-bar-links.blade.php`
- `xxxxxx/employee-work-patterns-tab-bar-links.blade.php`
- `xxxxxx/holiday-calendars-tab-bar-links.blade.php`
- `xxxxxx/leave-approvers-tab-bar-links.blade.php`
- `xxxxxx/leave-balances-tab-bar-links.blade.php`
- `xxxxxx/leave-overviews-tab-bar-links.blade.php`
- `xxxxxx/leave-requests-tab-bar-links.blade.php`
- `xxxxxx/leave-types-tab-bar-links.blade.php`
- `xxxxxx/payroll-overviews-tab-bar-links.blade.php`
- `xxxxxx/payroll-policy-assignments-tab-bar-links.blade.php`
- `xxxxxx/payroll-run-adjustments-tab-bar-links.blade.php`
- `xxxxxx/payslip-items-tab-bar-links.blade.php`
- `xxxxxx/people-overviews-tab-bar-links.blade.php`
- `xxxxxx/policy-assignments-tab-bar-links.blade.php`
- `xxxxxx/policy-overviews-tab-bar-links.blade.php`
- `xxxxxx/saved-reports-tab-bar-links.blade.php`
- `xxxxxx/shift-schedules-tab-bar-links.blade.php`
- `xxxxxx/sidebar-links.blade.php`
- `xxxxxx/sidebar-post-links.blade.php`
- `xxxxxx/sidebar-pre-links.blade.php`
- `xxxxxx/tags-tab-bar-links.blade.php`
- `xxxxxx/teams-tab-bar-links.blade.php`
- `xxxxxx/top-nav-links.blade.php`
- `xxxxxx/top-nav-post-links.blade.php`
- `xxxxxx/top-nav-pre-links.blade.php`

`Resources/views/components/livewire/bootstrap/` (6):
- `payroll/payroll-run-manager.blade.php`
- `payroll/payroll-run-preview.blade.php`
- `payroll/payslips/payslip-pdf.blade.php`
- `payroll/reports/run-report-pdf.blade.php`
- `payroll/reports/run-report.blade.php`
- `time/attendance/adjust-attendance-mvp.blade.php`

`Resources/views/livewire/payroll/` (12):
- `exports/payroll_run_summary_pdf.blade.php`
- `partials/employee_name_label.blade.php`
- `partials/payroll_payslip_items_table.blade.php`
- `payroll-executive-summary.blade.php`
- `payroll-run-detail.blade.php`
- `payroll-run-wizard.blade.php`
- `payslip-items.blade.php`
- `policy-calculation-builder.blade.php`
- `print/payroll-run-summary-grouped.blade.php`
- `print/payroll-run-summary.blade.php`
- `wizard-adjustments.blade.php`
- `wizard-preview.blade.php`

---

## 4. HR Models — Full List (45)

`app/Modules/Hr/Models/` — 45 `.php` files:

1. `Attendance.php`
2. `AttendanceAdjustment.php`
3. `AttendanceOverview.php`
4. `AttendancePolicy.php`
5. `AttendanceSession.php`
6. `ClockEvent.php`
7. `Company.php`
8. `CompanyProfileOverview.php`
9. `Department.php`
10. `Document.php`
11. `Employee.php`
12. `EmployeeAdjustmentProfile.php`
13. `EmployeeGroup.php`
14. `EmployeeJobHistory.php`
15. `EmployeePayrollProfile.php`
16. `EmployeePosition.php`
17. `EmployeeProfile.php`
18. `EmployeeWorkPattern.php`
19. `Holiday.php`
20. `HolidayCalendar.php`
21. `JobTitle.php`
22. `LeaveApprover.php`
23. `LeaveBalance.php`
24. `LeaveOverview.php`
25. `LeaveRequest.php`
26. `LeaveType.php`
27. `Location.php`
28. `PaySchedule.php`
29. `PayrollOverview.php`
30. `PayrollPayslip.php`
31. `PayrollPolicy.php`
32. `PayrollPolicyAssignment.php`
33. `PayrollRun.php`
34. `PayrollRunAdjustment.php`
35. `PayrollRunProgress.php`
36. `PayslipItem.php`
37. `PeopleOverview.php`
38. `PolicyAssignment.php`
39. `PolicyOverview.php`
40. `SavedReport.php`
41. `Shift.php`
42. `ShiftSchedule.php`
43. `Tag.php`
44. `Team.php`
45. `WorkPattern.php`

This exactly matches the plan's §5.1 (38 models) + §5.2 (7 models) mapping = 45. ✔

---

## 5. HR Data Configs — Full List (62)

`app/Modules/Hr/Data/` — 62 `.php` files:

### Top-level (44)
1. `attendance.php`
2. `attendance_adjustment.php`
3. `attendance_overview.php`
4. `attendance_policy.php`
5. `attendance_session.php`
6. `clock_event.php`
7. `company.php`
8. `company_profile_overview.php`
9. `department.php`
10. `document.php`
11. `employee.php`
12. `employee_adjustment_profile.php`
13. `employee_group.php`
14. `employee_job_history.php`
15. `employee_payroll_profile.php`
16. `employee_position.php`
17. `employee_profile.php`
18. `employee_work_pattern.php`
19. `holiday.php`
20. `holiday_calendar.php`
21. `job_title.php`
22. `leave_approver.php`
23. `leave_balance.php`
24. `leave_overview.php`
25. `leave_request.php`
26. `leave_type.php`
27. `location.php`
28. `pay_schedule.php`
29. `payroll_overview.php`
30. `payroll_payslip.php`
31. `payroll_policy.php`
32. `payroll_policy_assignment.php`
33. `payroll_run.php`
34. `payroll_run_adjustment.php`
35. `payslip_item.php`
36. `people_overview.php`
37. `policy_assignment.php`
38. `policy_overview.php`
39. `saved_report.php`
40. `shift.php`
41. `shift_schedule.php`
42. `tag.php`
43. `team.php`
44. `work_pattern.php`

### dashboards/ (9)
45. `dashboards/dashboard.php`
46. `dashboards/dashboard_employee_overview.php`
47. `dashboards/dashboard_leave_overview.php`
48. `dashboards/dashboard_organization_overview.php`
49. `dashboards/dashboard_payroll_overview.php`
50. `dashboards/dashboard_policies_overview.php`
51. `dashboards/dashboard_time_overview.php`
52. `dashboards/default.php`
53. `dashboards/people_overview.php`

### reports/ (4)
54. `reports/absence_leave.php`
55. `reports/compliance_audit.php`
56. `reports/employee_directory.php`
57. `reports/employee_summary.php`

### wizards/ (5)
58. `wizards/employee_onboarding.php`
59. `wizards/employee_self_service.php`
60. `wizards/holiday_batch_creation.php`
61. `wizards/payroll_run_wizard.php`
62. `wizards/sick_call_report.php`

---

## 6. HR Migrations — Full List (40)

`app/Modules/Hr/Database/Migrations/` — 40 `.php` files:

1. `2026_06_12_142457_create_job_titles_table.php`
2. `2026_06_12_142458_create_attendance_policies_table.php`
3. `2026_06_12_142458_create_shifts_table.php`
4. `2026_06_12_142459_create_employee_groups_table.php`
5. `2026_06_12_142500_create_tags_table.php`
6. `2026_06_12_142501_create_employees_table.php`
7. `2026_06_12_142502_create_taggable_table.php`
8. `2026_06_12_142503_create_employee_job_histories_table.php`
9. `2026_06_12_142504_create_employee_profiles_table.php`
10. `2026_06_12_142506_create_employee_team_table.php`
11. `2026_06_12_142507_create_documents_table.php`
12. `2026_06_12_142508_create_policy_assignments_table.php`
13. `2026_06_12_142509_create_work_patterns_table.php`
14. `2026_06_12_142510_create_employee_work_patterns_table.php`
15. `2026_06_12_142511_create_pay_schedules_table.php`
16. `2026_06_12_142512_create_employee_payroll_profiles_table.php`
17. `2026_06_12_142513_create_payroll_runs_table.php`
18. `2026_06_12_142514_create_payroll_payslips_table.php`
19. `2026_06_12_142515_create_payroll_policies_table.php`
20. `2026_06_12_142516_create_payroll_run_adjustments_table.php`
21. `2026_06_12_142517_create_employee_adjustment_profiles_table.php`
22. `2026_06_12_142518_create_payslip_items_table.php`
23. `2026_06_12_142519_create_payroll_policy_assignments_table.php`
24. `2026_06_12_142520_create_employee_positions_table.php`
25. `2026_06_12_142521_create_leave_types_table.php`
26. `2026_06_12_142522_create_leave_requests_table.php`
27. `2026_06_12_142523_create_leave_balances_table.php`
28. `2026_06_12_142524_create_leave_approvers_table.php`
29. `2026_06_12_142525_create_leave_approver_leave_type_table.php`
30. `2026_06_12_142526_create_attendances_table.php`
31. `2026_06_12_142527_create_attendance_adjustments_table.php`
32. `2026_06_12_142528_create_shift_schedules_table.php`
33. `2026_06_12_142529_create_clock_events_table.php`
34. `2026_06_12_142530_create_attendance_sessions_table.php`
35. `2026_06_12_142531_create_holiday_calendars_table.php`
36. `2026_06_12_142532_create_department_holiday_calendar_table.php`
37. `2026_06_12_142533_create_holiday_calendar_location_table.php`
38. `2026_06_12_142534_create_holidays_table.php`
39. `2026_08_17_000001_add_hr_specific_columns_to_locations_table.php`
40. `create_payroll_run_progress.php`

---

## 7. Git State Snapshot (2026-08-18)

### Library repo — `/Users/mac/Projects/Libraries/ui-library`
- **Branch**: `decoupling`
- **Working tree**: **NOT clean** (dirty). `git status --short` (first 20 lines shown) reports modified and deleted files, including:
  - `M composer.json`, `M composer.lock`
  - `D dependencies/database/migrations/2026_2_create_ export_chunks_table.php`
  - `D dependencies/database/migrations/create_payroll_run_progress.php`
  - `M dependencies/database/migrations/create_saved_filters_table.php`
  - `M dependencies/database/migrations/create_saved_reports_table.php`
  - `M dependencies/database/seeders/DatabaseSeeder.php`
  - `M dependencies/database/seeders/UserSeeder.php`
  - `M dependencies/deployment/models snipets.txt`
  - `D docs/ai-optimized-architecture-blueprint.md` (and numerous other `docs/` deletions)

### HR consuming app — `/Users/mac/Projects/LaravelProjects/hr-consuming-app`
- **Branch**: `main`
- **Working tree**: **clean** (`git status --short` produced no output).

---

## 8. Discrepancy Report

### 8.1 Confirmed — HR Models = 45
- The plan flagged that HR Models "may have 45 files, not 43/44." **Confirmed: 45.** The §5.1/§5.2 mapping in the plan resolves to exactly 45 and matches the on-disk list.

### 8.2 HR Migrations = 40, NOT 44
- Plan §0.1 states the authoritative HR migration inventory is **44** files. The actual `app/Modules/Hr/Database/Migrations/` directory contains **40** `.php` files — a **−4** discrepancy.
- Potential cause: the plan's §A.9 step 3 references HR migrations that "create org tables" (`create_companies_table`, `create_departments_table`, `create_locations_table`, `create_teams_table`). **These four migrations are absent from HR** — they live in the library under `src/Core/Organization/Database/Migrations/` (companies, branches, departments, divisions, business_units, locations, teams). This accounts for the −4 delta and must be reconciled in Part A/B.

### 8.3 Consuming-app repo path mismatch
- The plan (§0.1) names the consuming app `/Users/mac/Projects/LaravelProjects/quick-hr/`. The actual repo used for this Phase A.0 inventory is `/Users/mac/Projects/LaravelProjects/hr-consuming-app/`. Subsequent phases must use `hr-consuming-app` (or the plan must be updated to `quick-hr`).

### 8.4 Library working tree is dirty
- Phase A.0 step 1 expects both repos to have clean working trees before any change. The **library repo is dirty** (branch `decoupling`, many modified/deleted files). The HR consuming app is clean (branch `main`). A clean baseline was therefore **not** achievable on the library side at inventory time; this must be acknowledged before tagging `pre-org-split`.

### 8.5 Untimestamped migration
- `app/Modules/Hr/Database/Migrations/create_payroll_run_progress.php` lacks the standard `YYYY_MM_DD_HHMMSS_` prefix (all 39 other HR migrations have one). This may affect migration ordering/discovery and should be normalized.
- Note: the library repo `git status` shows `D dependencies/database/migrations/create_payroll_run_progress.php` (deleted) — a related migration with the same untimestamped name exists in both repos' histories.

### 8.6 Stray `.DS_Store`
- `app/Modules/Hr/.DS_Store` exists at the module root. It is not a PHP/source file and is excluded from the 434 count (raw file count including it is 435).

### 8.7 Data configs (62) exceed model count (45)
- Expected by design: the 62 Data configs include 44 entity configs + 9 dashboards + 4 reports + 5 wizards. No data-config discrepancy vs. plan is implied (the plan does not state an expected Data count), but this is recorded for the subsequent module-split tallies.

---

## 9. Phase A.0 Conclusion

- Library Organization domain frozen at **43** files.
- HR module frozen at **434** source files across all subdirectories.
- HR Models frozen at **45**.
- HR Data configs frozen at **62**.
- HR Migrations frozen at **40** (flagging the plan's stated 44).
- Both repos' git state captured; library repo is dirty on branch `decoupling`, HR app is clean on branch `main`.

This inventory is the source of truth for Phases A.1 → B.6.
