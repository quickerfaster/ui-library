# Geofencing Implementation — Status & Recommendations

> **Date**: 2026-09-14
> **Status**: Implemented (Phase 1 complete)
> **Related**: [`organization-module-geofencing-analysis.md`](organization-module-geofencing-analysis.md), [`clock-in-out-fix-plan.md`](clock-in-out-fix-plan.md)

---

## 1. What Was Implemented

### 1.1 GeofenceValidator Service

**File**: [`app/Modules/Attendance/Services/GeofenceValidator.php`](../app/Modules/Attendance/Services/GeofenceValidator.php)

- Haversine distance calculation (Earth radius: 6,371 km)
- 4-tier validation chain:
  1. Employee's assigned Location (`EmployeePosition.location_id`)
  2. Company's headquarters Location (`is_headquarters = true`)
  3. Any active, non-remote company Location with coordinates
  4. Skip validation (graceful degradation)
- Remote locations (`is_remote = true`) always skip geofencing
- Returns structured result: `{passed, location_id, location_name, distance_meters, geofence_radius, reason}`

### 1.2 Browser Geolocation Capture

**Files**: 
- [`src/Http/Livewire/QuickActions/ClockInOut.php`](../src/Http/Livewire/QuickActions/ClockInOut.php)
- [`src/Resources/views/livewire/quick-actions/clock-in-out.blade.php`](../src/Resources/views/livewire/quick-actions/clock-in-out.blade.php)

- `toggle(?float $latitude, ?float $longitude)` accepts GPS coordinates
- Alpine.js `navigator.geolocation.getCurrentPosition()` captures GPS before toggle
- Falls back gracefully if geolocation denied/unavailable
- Coordinates passed directly as method params (avoids async `$wire.set()` race)

### 1.3 ClockEventRecorderService Integration

**File**: [`app/Modules/Attendance/Services/ClockEventRecorderService.php`](../app/Modules/Attendance/Services/ClockEventRecorderService.php)

- `record()` accepts `latitude`/`longitude` in `$meta`
- Runs `GeofenceValidator::validate()` for **all events** when GPS available
- Enforces geofence for `clock_in` only (blocks if outside)
- Stores `latitude`, `longitude`, `location_name` on every `ClockEvent`
- Clock-out is unrestricted (audit only) — employees may leave before clocking out

### 1.4 Seeder Coordinates

**File**: [`app/Modules/Organization/Database/Seeders/OrganizationSeeder.php`](../app/Modules/Organization/Database/Seeders/OrganizationSeeder.php)

- Location "Main Office" now has `latitude`, `longitude`, `geofence_radius`, `address`, `city`, `country_code`, `state_code`

---

## 2. Current Behavior Matrix

| Scenario | GPS captured? | Geofence validated? | Location name stored? | Blocked? |
|---|---|---|---|---|
| Clock-in, inside geofence | ✅ | ✅ | ✅ | ❌ Allowed |
| Clock-in, outside geofence | ✅ | ✅ | ✅ | ✅ Blocked |
| Clock-in, no GPS | ❌ | ❌ | ❌ | ❌ Allowed |
| Clock-out, inside geofence | ✅ | ✅ | ✅ | ❌ Allowed |
| Clock-out, outside geofence | ✅ | ✅ | ✅ | ❌ Allowed (audit) |
| Clock-out, no GPS | ❌ | ❌ | ❌ | ❌ Allowed |

---

## 3. Future Recommendation: `geofence_policy` Config

Currently, clock-in is **allowed** when GPS is unavailable (e.g., desktop browser on HTTP, user denied permission). This is the safest default but may not meet strict compliance requirements.

### Proposed Config

```php
// config('attendance.geofence_policy', 'optional')
//   'optional' — allow clock-in without GPS (current default)
//   'required' — block clock-in without GPS
//   'warn'     — allow clock-in but flag for manager review
```

### Policy Semantics

| Policy | Clock-in without GPS | Clock-in outside geofence | Clock-out (any) |
|---|---|---|---|
| `optional` | ✅ Allow | ❌ Block | ✅ Allow |
| `required` | ❌ Block | ❌ Block | ✅ Allow |
| `warn` | ⚠️ Allow + flag | ⚠️ Allow + flag | ✅ Allow |

### Implementation Sketch (future)

```php
// In ClockEventRecorderService::record()
$policy = config('attendance.geofence_policy', 'optional');

if ($eventType === 'clock_in') {
    if ($latitude === null || $longitude === null) {
        if ($policy === 'required') {
            throw new \RuntimeException('Location required to clock in.');
        }
        if ($policy === 'warn') {
            // Create event with needs_review flag
            $meta['needs_review'] = true;
        }
        // 'optional' — proceed without GPS
    } else {
        $geofenceResult = $validator->validate(...);
        if (!$geofenceResult['passed']) {
            if ($policy === 'warn') {
                // Allow but flag
                $meta['needs_review'] = true;
            } else {
                // 'optional' and 'required' both block
                throw new \RuntimeException($geofenceResult['reason']);
            }
        }
    }
}
```

### Where to Configure

The policy could be set at multiple levels (most specific wins):
1. **Per-location**: `locations.geofence_policy` column (new)
2. **Per-company**: `companies.geofence_policy` column (new)
3. **Global**: `config('attendance.geofence_policy')`

---

## 4. Remaining Gaps (from analysis)

| # | Gap | Status |
|---|---|---|
| 5 | GeofenceValidator service | ✅ Implemented |
| 8 | Browser geolocation capture | ✅ Implemented |
| 3 | Seeder coordinates | ✅ Implemented |
| 6 | Hr migration modifies Organization-owned locations table | ⏳ Deferred — needs review |
| 7 | CompanyProvider bound in Hr, not Organization | ⏳ Deferred — needs review |

---

## 5. Testing Notes

- **HTTPS required**: Browser geolocation only works on HTTPS origins. Dev URL `http://hr-consuming-app.test` blocks GPS. Use `herd secure hr-consuming-app.test` for HTTPS.
- **Geofence radius**: Test location "Warehouse" (id:6) has `geofence_radius = 3000m` (widened for testing). Production should use realistic values (100-500m).
- **Location name**: Stored on clock events when GPS available, regardless of pass/fail.