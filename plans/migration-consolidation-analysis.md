# Migration Consolidation Analysis

> **Date**: 2026-09-14
> **Status**: Analysis complete — action required
> **Context**: Production database is empty — safe to consolidate ALTER migrations into CREATE migrations

---

## 1. All ALTER TABLE Migrations Found

### Library (`quick-faster/ui-library`)

| # | File | Table | Change | Action |
|---|---|---|---|---|
| L1 | `dependencies/.../add_has_seen_tour_to_users_table.php` | users | Add `has_seen_tour` | ⏭️ Dependencies folder — reference only |
| L2 | `dependencies/.../add_expires_at_to_personal_access_tokens_table.php` | personal_access_tokens | Add `expires_at` | ⏭️ Dependencies folder — reference only |
| L3 | `dependencies/.../add_missing_columns_to_users_table.php` | users | Multiple columns | ⏭️ Dependencies folder — reference only |
| L4 | `Database/Migrations/2026_09_09_000000_add_reminded_at_to_invitations_table.php` | invitations | Add `reminded_at` | 🔀 Merge into invitations CREATE |
| L5 | `Database/Migrations/2026_08_15_000004_add_notifications_to_workflow_definitions.php` | workflow_definitions | Add `notifications` JSON | 🔀 Merge into workflow_definitions CREATE |
| L6 | `src/Core/Admin/Database/Migrations/2026_08_13_000000_add_status_and_company_id_to_users_table.php` | users | Add `status`, `company_id` | �️ Keep — users table created by Laravel, not library |

### Consuming App (`quick-hr-update`)

| # | File | Table | Change | Action |
|---|---|---|---|
| C1 | `database/migrations/2026_08_16_155423_add_two_factor_columns_to_users_table.php` | users | 2FA columns (Fortify) | �️ Keep — Laravel Fortify requirement |
| C2 | `database/migrations/2026_08_13_000000_add_status_and_company_id_to_users_table.php` | users | Add `status`, `company_id` | 🔴 **DELETE** — duplicate of L6! |
| C3 | `database/migrations/2026_09_08_155742_add_deleted_at_to_companies_table.php` | companies | Add `deleted_at` (soft deletes) | 🔀 Merge into companies CREATE |
| C4 | `database/migrations/2026_09_13_073215_add_last_accrual_date_to_leave_balances.php` | leave_balances | Add `last_accrual_date` | 🔀 Merge into leave_balances CREATE |
| C5 | `app/Modules/Leave/.../2026_09_02_175300_add_half_day_to_leave_requests.php` | leave_requests | Add `is_half_day`, `half_day_period` | 🔀 Merge into leave_requests CREATE |
| C6 | `app/Modules/Leave/.../2026_09_02_190000_add_attachments_to_leave_requests.php` | leave_requests | Add `attachments` JSON | 🔴 **DELETE** — reverted by C7 |
| C7 | `app/Modules/Leave/.../2026_09_04_000000_drop_attachments_from_leave_requests.php` | leave_requests | Drop `attachments` (reverts C6) | 🔴 **DELETE** — net zero with C6 |
| C8 | `app/Modules/Organization/.../2026_08_18_000001_add_business_columns_to_companies_table.php` | companies | Add 30+ business columns | 🔀 Merge into companies CREATE |
| C9 | `app/Modules/Hr/.../2026_09_10_000001_make_employee_id_nullable_in_documents_table.php` | documents | Make `employee_id` nullable | 🔀 Merge into documents CREATE |
| C10 | `app/Modules/Hr/.../2026_08_18_000002_add_hr_columns_to_documents_table.php` | documents | Add `company_id`, `employee_id`, `type`, `document`, `uploaded_at`, `expiry_date`, `description` | 🔀 Merge into documents CREATE |
| C11 | `app/Modules/Hr/.../2026_08_17_000001_add_hr_specific_columns_to_locations_table.php` | locations | Add `geofence_radius`, `external_id`, `last_synced_at`, `employee_count`, `department_count` | 🔀 Merge into locations CREATE |
| C12 | `app/Modules/Attendance/.../2026_09_14_000001_add_unique_constraint_to_clock_events_table.php` | clock_events | Add unique constraint + cleanup | 🔀 Merge into clock_events CREATE |

---

## 2. Recommendations

### 🔀 Merge into CREATE (9 migrations)

These ALTER migrations add columns that should have been in the original CREATE TABLE:

| Merge | ALTER File | Into CREATE File |
|---|---|---|
| M1 | L4 — `add_reminded_at_to_invitations_table` | `Database/Migrations/2026_08_08_000001_create_workflow_tables.php` (invitations portion) |
| M2 | L5 — `add_notifications_to_workflow_definitions` | `Database/Migrations/2026_08_15_000003_create_workflow_definition_tables.php` |
| M3 | C3 — `add_deleted_at_to_companies_table` | `src/Core/Organization/Database/Migrations/2026_06_11_000003_create_companies_table.php` |
| M4 | C4 — `add_last_accrual_date_to_leave_balances` | `app/Modules/Leave/Database/Migrations/2026_06_12_142523_create_leave_balances_table.php` |
| M5 | C5 — `add_half_day_to_leave_requests` | `app/Modules/Leave/Database/Migrations/2026_06_12_142522_create_leave_requests_table.php` |
| M6 | C8 — `add_business_columns_to_companies_table` | `src/Core/Organization/Database/Migrations/2026_06_11_000003_create_companies_table.php` |
| M7 | C9 + C10 — both documents ALTERs | `Database/Migrations/2026_08_08_000002_create_documents_table.php` |
| M8 | C11 — `add_hr_specific_columns_to_locations_table` | `app/Modules/Organization/Database/Migrations/2026_06_11_000008_create_locations_table.php` |
| M9 | C12 — `add_unique_constraint_to_clock_events_table` | `app/Modules/Attendance/Database/Migrations/2026_06_12_142529_create_clock_events_table.php` |

### 🔴 DELETE (3 migrations)

| Delete | File | Reason |
|---|---|---|
| D1 | C2 — `add_status_and_company_id_to_users_table` | Duplicate of library's L6. The library already owns this migration. |
| D2 | C6 — `add_attachments_to_leave_requests` | Immediately reverted by C7. Net zero effect. |
| D3 | C7 — `drop_attachments_from_leave_requests` | Reverts C6. Both cancel out. |

### ⚚️ KEEP (4 migrations)

| Keep | File | Reason |
|---|---|---|
| K1 | L6 — `add_status_and_company_id_to_users_table` | users table created by Laravel's `0001_01_01_000000_create_users_table`, not by the library. ALTER is correct. |
| K2 | C1 — `add_two_factor_columns_to_users_table` | Laravel Fortify requirement. users table not owned by our code. |
| K3-5 | L1-L3 — dependencies folder | Reference/dependency files, not active migrations. |

---

## 3. Library Philosophy Compliance

### Rule Check: "One owner per table"

> From [`27-architectture-boundary.md`](docs/library/27-architecture-boundary.md:317): "One owner per table. Modules only ALTER what they extend."

| Table | Owner | Current State | After Consolidation |
|---|---|---|---|
| `companies` | Library Core/Organization | CREATE in library, ALTER in Organization module + database root | ✅ All columns in library CREATE |
| `locations` | Organization module | CREATE in Organization, ALTER in Hr module | ✅ All columns in Organization CREATE |
| `documents` | Library | CREATE in library, ALTER in Hr module | ✅ All columns in library CREATE |
| `leave_requests` | Leave module | CREATE in Leave, ALTER in Leave | ✅ All columns in Leave CREATE |
| `leave_balances` | Leave module | CREATE in Leave, ALTER in database root | ✅ All columns in Leave CREATE |
| `clock_events` | Attendance module | CREATE in Attendance, ALTER (unique constraint) in Attendance | ✅ All in Attendance CREATE |
| `invitations` | Library | CREATE in library, ALTER in library | ✅ All in library CREATE |
| `workflow_definitions` | Library | CREATE in library, ALTER in library | ✅ All in library CREATE |
| `users` | Laravel core | Created by Laravel | ⚚️ Keep ALTERs — not our table |

### Rule Check: "Self-contained modules"

> From [`pilosophy.txt`](docs/library/pilosophy.txt): "all the related files ... to a given module must be housed inside that module"

After consolidation:
- `companies` table: fully defined in library's `src/Core/Organization/Database/Migrations/`
- `locations` table: fully defined in Organization module
- `documents` table: fully defined in library's `Database/Migrations/`
- `leave_*` tables: fully defined in Leave module
- `clock_events` table: fully defined in Attendance module

---

## 4. Implementation Plan

### Phase 1: Delete (safe, no data loss)
1. Delete C2 (duplicate users ALTER)
2. Delete C6 + C7 (add-then-drop attachments)

### Phase 2: Merge (add columns to CREATE migrations)
3. M1: Add `reminded_at` to invitations CREATE (library)
4. M2: Add `notifications` to workflow_definitions CREATE (library)
5. M3: Add `deleted_at` to companies CREATE (library)
6. M4: Add `last_accrual_date` to leave_balances CREATE (Leave module)
7. M5: Add `is_half_day`, `half_day_period` to leave_requests CREATE (Leave module)
8. M6: Merge 30+ business columns into companies CREATE (library)
9. M7: Merge Hr document columns into documents CREATE (library)
10. M8: Merge Hr location columns into locations CREATE (Organization module)
11. M9: Merge unique constraint into clock_events CREATE (Attendance module)

### Phase 3: Remove ALTER files
12. Delete all 9 merged ALTER migration files
13. Delete 3 deleted ALTER migration files

### Phase 4: Fresh migrate test
14. Run `php artisan migrate:fresh` to verify clean migration
15. Run `php artisan db:seed` to verify seeding

---

## 5. Files Affected Summary

| Action | Count | Files |
|---|---|---|
| 🔀 Merge into CREATE | 9 | L4, L5, C3, C4, C5, C8, C9+C10, C11, C12 |
| 🔴 Delete | 3 | C2, C6, C7 |
| ⚚️ Keep | 4 | L6, C1, L1-L3 |
| 📝 Modify (CREATE files) | 7 | companies, invitations, workflow_definitions, leave_requests, leave_balances, documents, locations, clock_events |

Net reduction: **12 migration files removed**, **7 CREATE files enriched**.