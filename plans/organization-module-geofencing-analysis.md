# Organization Module & Geofencing — Analysis Report (v2)

> **Date**: 2026-09-14
> **Status**: Analysis complete, Phase 1 implementation done
> **Implementation**: See [`geofencing-implementation.md`](geofencing-implementation.md) for current status
> **Scope**: Library Core/Organization, consuming-app Organization module, Hr module Company/Location, all org models, EmployeePosition, geofencing feasibility

---

## 1. Model Inheritance Chain

### Company (3-tier hierarchy)

```
QuickerFaster\UILibrary\Core\Organization\Models\Company  (LIBRARY)
  │  Table: companies
  │  Fillable: name, code
  │  Role: "Scoping anchor" — minimal tenant record
  │
  └── App\Modules\Organization\Models\Company  (ORGANIZATION MODULE)
        │  Extends library Company
        │  Fillable: +subdomain, logo, email, phone, website, address, city,
        │            state_code, country_code, postal_code, tax_id,
        │            registration_number, currency_code, timezone, date_format,
        │            is_active, status, metadata, level, parent_company_id,
        │            billing_* (6 fields), database_name, is_placeholder
        │  Traits: SoftDeletes, HasSettings
        │  Relations: branches, departments, divisions, businessUnits,
        │             locations, teams, parentCompany, children
        │  ❌ NO latitude/longitude
        │
        └── App\Modules\Hr\Models\Company  (HR MODULE)
              │  Extends Organization Company
              │  Fillable: name, subdomain, level, parent_company_id, status,
              │            billing_email, billing_address_line_1/2, billing_city,
              │            billing_state_code, billing_postal_code,
              │            billing_country_code, timezone, currency_code,
              │            is_placeholder
              │  ❌ MISSING from fillable: code, email, phone, website, address,
              │     city, state_code, country_code, postal_code, tax_id,
              │     registration_number, date_format, is_active, metadata,
              │     database_name, logo
              │  Relations: locations()→Hr\Location, employees()→Hr\Employee,
              │             parentCompany()→Hr\Company
              │  ❌ NO latitude/longitude
```

### Location (2-tier hierarchy)

```
App\Modules\Organization\Models\Location  (ORGANIZATION MODULE)
  │  Table: locations
  │  Fillable: company_id, name, code, type, address, city, postal_code,
  │            latitude, longitude, phone, email, timezone, is_headquarters,
  │            is_active, metadata, address_line_1/2, website, is_remote,
  │            capacity, opening_hours, opening_date, closing_date,
  │            country_code, state_code
  │  ✅ HAS latitude (decimal:7), longitude (decimal:7)
  │
  └── App\Modules\Hr\Models\Location  (HR MODULE)
        │  Extends Organization Location
        │  Fillable: +geofence_radius, external_id (+ all parent fields)
        │  Traits: HasCompanyScope, HasFactory, SoftDeletes
        │  ✅ HAS geofence_radius (decimal:2, default: 100 meters)
        │  ✅ HAS last_synced_at, employee_count, department_count
        │  Relations: company()→Hr\Company, employeePositions()
```

---

## 2. Geofencing-Ready Infrastructure (Already in Place)

| Component | Field | Type | Status |
|---|---|---|---|
| `clock_events` table | `latitude` | decimal(10,8) | ✅ Exists, nullable |
| `clock_events` table | `longitude` | decimal(11,8) | ✅ Exists, nullable |
| `clock_events` table | `location_name` | varchar | ✅ Exists, nullable |
| `locations` table | `latitude` | decimal(10,7) | ✅ Exists, nullable |
| `locations` table | `longitude` | decimal(10,7) | ✅ Exists, nullable |
| `locations` table | `geofence_radius` | decimal(6,2) | ✅ Exists, default 100m |
| `locations` table | `is_headquarters` | boolean | ✅ Exists |
| `locations` table | `is_remote` | boolean | ✅ Exists |
| `locations` table | `capacity` | integer | ✅ Exists |
| `locations` table | `opening_hours` | text | ✅ Exists |
| Hr Location model | `geofence_radius` | cast: decimal:2 | ✅ Default 100 |
| `ClockEventRecorderService` | captures `ip_address`, `device_name` | — | ✅ Ready for lat/lng |

**The database schema is already geofencing-ready.** The `clock_events` table can store GPS coordinates, and the `locations` table has coordinates plus a `geofence_radius` for boundary validation.

---

## 3. Gaps Found in Organization Module (Post-Decoupling)

### Gap 1: Hr Company Fillable Override — INTENTIONAL, Not a Gap ✅

**Files**:
- [`Organization/Models/Company.php`](app/Modules/Organization/Models/Company.php:15) — 33 fillable fields (owns Company CRUD)
- [`Hr/Models/Company.php`](app/Modules/Hr/Models/Company.php:23) — 15 fillable fields (read-only query model)

**Finding**: The Hr Company is **never created or updated** through Hr module code. A search for `Company::create`, `Company::update`, `new Company`, `->fill(` across the entire Hr module returned **0 results**. The Hr Company exists solely for:
- Relation definitions (`employees()`, `locations()`)
- Querying via `HrsCompanyProvider`
- Type-hinting in Hr-specific code

The reduced `$fillable` is intentional — it lists only the fields the Hr module might need to reference in queries or relations. All Company CRUD is handled by the Organization module's Company model.

**Verdict**: NOT a gap. This is correct separation of concerns — the Organization module owns Company data, the Hr module reads it.

---

### Gap 2: Employee Single-Company — CORRECT Design ✅

**Files**:
- [`Employee`](app/Modules/Hr/Models/Employee.php:47) — `company_id` (single FK)
- [`company_user`](database/migrations/2026_09_07_000000_create_company_user_table.php:11) — many-to-many pivot (User↔Company)

**Finding**: These serve different purposes:
- **User↔Company** (`company_user`): Navigation/scoping — which companies can the user switch between in the UI. A consultant with multiple clients needs this.
- **Employee↔Company** (`employee.company_id`): HR data ownership — which company employs this person for payroll, attendance, leave. An employee works for ONE employer at a time.

When a User switches to Company B via the company switcher, they should have a **separate Employee record** in Company B. The `HrsCompanyProvider` already handles this — it resolves the employee via `user_id` within the current company context.

**Verdict**: NOT a gap. Single `company_id` on Employee is correct. Multi-company is a User-level navigation concern, not an Employee-level HR concern.

**For geofencing**: The employee's `company_id` determines which company's locations to validate against. The employee's `EmployeePosition.location_id` determines the specific office location.

---

### Gap 3: OrganizationSeeder Missing Coordinates 🟡

**File**: [`OrganizationSeeder.php`](app/Modules/Organization/Database/Seeders/OrganizationSeeder.php:41)

**Problem**: The seeder creates a Location ("Main Office") without setting `latitude` or `longitude`. All seeded locations have null coordinates.

**Impact**: Geofencing validation has no reference points to compare against.

**Recommendation**: Add realistic coordinates to the seeder, or create a separate `GeolocationSeeder`.

---

### Gap 4: Company Has No Coordinates 🟡

**Files**:
- [`Organization/Models/Company.php`](app/Modules/Organization/Models/Company.php) — has `address`, `city`, `state_code`, `country_code`, `postal_code` but NO `latitude`/`longitude`
- [`Hr/Models/Company.php`](app/Modules/Hr/Models/Company.php) — same gap

**Problem**: The Company model has address fields for geocoding but no coordinate fields. For geofencing, you need to know WHERE the company is. The Location model has coordinates, but a company can have multiple locations.

**Design question**: Should geofencing validate against the **company's headquarters location** or **any of the company's locations**? If the latter, the current Location model is sufficient. If the former, the Company needs coordinates.

**Recommendation**: Leverage the Location model. A company can have multiple office locations, each with its own geofence. Clock-in validation checks if the employee's GPS coordinates fall within ANY of the company's active locations' geofence radii.

---

### Gap 5: No Geofencing Validation Service 🔴

**Problem**: Despite having all the data infrastructure (`clock_events.latitude/longitude`, `locations.latitude/longitude/geofence_radius`), there is **no service** that validates clock-in coordinates against location boundaries.

**What's needed**:
- A `GeofenceValidator` service that:
  1. Receives clock-in coordinates (from browser geolocation)
  2. Resolves the employee's company
  3. Queries the company's active locations with coordinates
  4. Calculates Haversine distance between clock-in point and each location
  5. Returns pass/fail based on `geofence_radius`
- Integration into `ClockEventRecorderService::record()` to validate before creating the event
- A `GeofenceViolation` model or log for tracking out-of-bounds clock-ins

---

### Gap 6: Hr Module Migration Modifies Organization-Owned Table 🟠

**File**: [`Hr/Database/Migrations/2026_08_17_000001_add_hr_specific_columns_to_locations_table.php`](app/Modules/Hr/Database/Migrations/2026_08_17_000001_add_hr_specific_columns_to_locations_table.php)

**Problem**: This migration adds `geofence_radius`, `external_id`, `last_synced_at`, `employee_count`, `department_count` to the `locations` table. But the `locations` table is **owned by the Organization module** (created in `Organization/Database/Migrations/2026_06_11_000008_create_locations_table.php`).

This violates the architecture rule from [`27-architecture-boundary.md`](docs/library/27-architecture-boundary.md:317): "One owner per table. Modules only ALTER what they extend."

**Impact**: If the Organization module is deployed without the Hr module, the `locations` table is missing these columns. The Hr Location model references fields that may not exist.

**Recommendation**: Either:
- Move the migration to the Organization module (since it owns the table)
- Or ensure the Hr Location model handles missing columns gracefully
- Or use the "ALTER TABLE" pattern documented in the architecture (which this technically follows, but the migration location is misleading)

---

### Gap 7: CompanyProvider Bound in Hr Module, Not Organization Module 🟡

**Files**:
- [`HrsServiceProvider`](app/Modules/Hr/Providers/HrsServiceProvider.php:17) — binds `CompanyProvider` to `HrsCompanyProvider`
- [`OrganizationServiceProvider`](app/Modules/Organization/Providers/OrganizationServiceProvider.php) — does NOT bind `CompanyProvider`

**Problem**: The `CompanyProvider` contract is a **navigation/scoping concern** that resolves which companies a user can access. The Organization module owns the Company entity, so it should own this binding. Instead, it's in the Hr module.

**Impact**: If the Organization module is used without the Hr module (e.g., for an inventory app), the `CompanyProvider` is never bound, and the company switcher shows nothing.

**Recommendation**: Move the `CompanyProvider` binding to `OrganizationServiceProvider`, or create a default `OrganizationCompanyProvider` in the Organization module.

---

### Gap 8: No Browser Geolocation Capture 🟡

**File**: [`ClockEventRecorderService::record()`](app/Modules/Attendance/Services/ClockEventRecorderService.php:67)

**Problem**: The service captures `ip_address` and `device_name` but does not capture `latitude`, `longitude`, or `location_name` from the browser. The `clock_events` table has these columns ready.

**What's needed**: 
- JavaScript geolocation API call (`navigator.geolocation.getCurrentPosition()`) before dispatching the clock-in event
- Pass coordinates as `$meta` to `ClockEventRecorderService::record()`
- Store in `clock_events.latitude`/`longitude`

---

## 4. Geofencing Implementation Assessment

### Can we leverage the existing Location model?

**Yes.** The `Location` model already has:
- `latitude` / `longitude` — GPS coordinates
- `geofence_radius` — allowable distance in meters (default 100m)
- `is_headquarters` — can prioritize HQ for validation
- `is_remote` — can skip geofencing for remote locations
- `is_active` — can skip inactive locations
- `company_id` — scoped to company

### Should we introduce a new GeoLocation model?

**No.** A separate `GeoLocation` model would duplicate the Location model's coordinate fields and create confusion about which model is authoritative for geofencing. The Location model is sufficient.

### What needs to be built:

```
┌─────────────────────────────────────────────────────────────┐
│  BROWSER                                                     │
│  navigator.geolocation.getCurrentPosition()                  │
│  → { latitude, longitude }                                   │
│  → passed as $meta to ClockEventRecorderService              │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  ClockEventRecorderService::record()                         │
│  → stores lat/lng in clock_events                            │
│  → calls GeofenceValidator::validate()                       │
└──────────────────────────┬──────────────────────────────────┘
                           │
┌──────────────────────────▼──────────────────────────────────┐
│  GeofenceValidator (NEW SERVICE)                             │
│  → Resolves employee's company                               │
│  → Queries company's active, non-remote locations            │
│  → Haversine distance calculation                            │
│  → Returns: { passed: bool, nearest_location: ...,           │
│               distance_meters: ... }                         │
└─────────────────────────────────────────────────────────────┘
```

### Haversine formula (standard for GPS distance):

```php
function haversineDistance($lat1, $lon1, $lat2, $lon2): float
{
    $earthRadius = 6371000; // meters
    $dLat = deg2rad($lat2 - $lat1);
    $dLon = deg2rad($lon2 - $lon1);
    $a = sin($dLat / 2) * sin($dLat / 2) +
         cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
         sin($dLon / 2) * sin($dLon / 2);
    $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
    return $earthRadius * $c;
}
```

---

## 5. Organization Module — Complete Model Audit

All Organization module models were analyzed for post-decoupling gaps. Only Company and Location have Hr module counterparts; the rest are clean.

| Model | Hr Counterpart? | Fillable Fields | Has `company_id`? | Has coordinates? | Status |
|---|---|---|---|---|---|
| [`Company`](app/Modules/Organization/Models/Company.php) | ✅ Hr\Company (read-only) | 33 fields | N/A (is the company) | ❌ No lat/lng | Clean — Hr extension is read-only |
| [`Location`](app/Modules/Organization/Models/Location.php) | ✅ Hr\Location (adds geofence_radius) | 22 fields | ✅ Yes | ✅ lat/lng (decimal:7) | Clean — Hr extension adds geofencing fields |
| [`Branch`](app/Modules/Organization/Models/Branch.php) | ❌ None | 13 fields | ✅ Yes | ❌ No | Clean — no issues |
| [`Department`](app/Modules/Organization/Models/Department.php) | ❌ None | 11 fields | ✅ Yes | ❌ No | Clean — hierarchical (parent/children) |
| [`Division`](app/Modules/Organization/Models/Division.php) | ❌ None | 8 fields | ✅ Yes | ❌ No | Clean — belongs to Department |
| [`BusinessUnit`](app/Modules/Organization/Models/BusinessUnit.php) | ❌ None | 7 fields | ✅ Yes | ❌ No | Clean — no issues |
| [`Team`](app/Modules/Organization/Models/Team.php) | ❌ None | 9 fields | ✅ Yes | ❌ No | Clean — belongs to Department |

**Finding**: Only Company and Location have Hr module extensions. The Hr extensions are justified:
- **Hr\Company**: Adds `employees()` and `locations()` relations pointing to Hr models. Read-only — never creates/updates companies.
- **Hr\Location**: Adds `geofence_radius`, `employeePositions()` relation, `HasCompanyScope`.

The other models (Branch, Department, Division, BusinessUnit, Team) have no Hr counterparts and are clean.

---

## 6. EmployeePosition — The Key Geofencing Link

[`EmployeePosition`](app/Modules/Hr/Models/EmployeePosition.php:40) is the critical link between an employee and their work location:

```
Employee ──→ EmployeePosition ──→ location_id ──→ Location
             │                                    │
             ├── company_id ──→ Company           ├── latitude
             ├── department_id ──→ Department      ├── longitude
             ├── shift_id ──→ Shift               ├── geofence_radius
             └── attendance_policy_id              ├── is_headquarters
                                                  └── is_remote
```

**For geofencing**, the validation chain is:
1. Get the clocking-in employee
2. Get their `EmployeePosition` (has `location_id`, `company_id`)
3. If `location_id` is set → validate against that specific Location's geofence
4. If `location_id` is null → fall back to the company's headquarters Location
5. If the Location has `is_remote = true` → skip geofencing entirely
6. If no Location has coordinates → skip geofencing (graceful degradation)

---

## 7. Geofencing Design Decisions

### Q1: Should employees have multiple companies?

**No.** Keep Employee single-company. The User↔Company many-to-many is for **navigation context switching** (which company's dashboard am I viewing?). The Employee↔Company one-to-one is for **HR data ownership** (which company pays this person?).

When a User switches companies, they should have a separate Employee record in each company. This is already supported by `HrsCompanyProvider` which resolves the employee via `user_id` within the current company scope.

### Q2: Should clock-in geofencing validate against selected/configured companies' locations only?

**Yes — validate against the employee's assigned location, with fallbacks:**

| Priority | Validation Target | When |
|---|---|---|
| 1 | Employee's assigned Location (`EmployeePosition.location_id`) | Primary — the office they work at |
| 2 | Company's headquarters Location (`is_headquarters = true`) | Fallback if no location assigned |
| 3 | Any active, non-remote company Location | Fallback if no HQ |
| 4 | Skip geofencing | If Location is remote, or no coordinates available |

### Q3: Should we introduce a new GeoLocation model?

**No.** The existing `Location` model already has `latitude`, `longitude`, `geofence_radius`, `is_headquarters`, `is_remote`, `is_active`. A separate model would duplicate this and create ambiguity about which is authoritative.

---

## 8. Summary of All Gaps (Revised)

| # | Severity | Gap | Location | Status |
|---|---|---|---|---|
| 1 | ✅ Not a gap | Hr Company fillable override — intentional (read-only, no creates/updates) | [`Hr/Models/Company.php:23`](app/Modules/Hr/Models/Company.php:23) | By design |
| 2 | ✅ Not a gap | Employee single-company — correct (User multi-company is for navigation) | [`Employee.php:47`](app/Modules/Hr/Models/Employee.php:47) | By design |
| 3 | 🟡 Medium | OrganizationSeeder doesn't populate lat/lng | [`OrganizationSeeder.php:41`](app/Modules/Organization/Database/Seeders/OrganizationSeeder.php:41) | Needs fix |
| 4 | ✅ Not a gap | Company model has no lat/lng — Location model handles coordinates | [`Organization/Models/Company.php`](app/Modules/Organization/Models/Company.php) | By design |
| 5 | ✅ Done | GeofenceValidator service | [`GeofenceValidator.php`](app/Modules/Attendance/Services/GeofenceValidator.php) | Implemented |
| 6 | 🟠 High | Hr migration modifies Organization-owned locations table | [`Hr/Database/Migrations/...add_hr_specific_columns...`](app/Modules/Hr/Database/Migrations/2026_08_17_000001_add_hr_specific_columns_to_locations_table.php) | Deferred |
| 7 | 🟡 Medium | CompanyProvider bound in Hr, not Organization | [`HrsServiceProvider.php:17`](app/Modules/Hr/Providers/HrsServiceProvider.php:17) | Deferred |
| 8 | ✅ Done | Browser geolocation capture in web clock flow | [`ClockInOut.php`](src/Http/Livewire/QuickActions/ClockInOut.php) + [`clock-in-out.blade.php`](src/Resources/views/livewire/quick-actions/clock-in-out.blade.php) | Implemented |

---

## 9. Recommended Implementation Order (Revised)

1. **Build GeofenceValidator** (Gap 5) — core geofencing logic with Haversine formula
2. **Add browser geolocation** (Gap 8) — capture coordinates via `navigator.geolocation`
3. **Integrate validation into ClockEventRecorderService** — resolve employee→position→location, validate, store lat/lng
4. **Fix Gap 3** — populate seeder coordinates for testing
5. **Address Gap 6** — relocate or document the Hr migration
6. **Address Gap 7** — move CompanyProvider binding to Organization module