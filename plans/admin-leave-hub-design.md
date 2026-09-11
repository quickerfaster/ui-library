# Admin Leave Hub — Design & Implementation Plan

## 1. How the ESS LeaveHub Works

### Component: [`LeaveHub.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Http/Livewire/LeaveHub.php)

```mermaid
flowchart TD
    A[User visits /hr/leave-hub?tab=apply] --> B[mount()]
    B --> C[Resolve employee from Auth::id]
    C --> D{Employee exists?}
    D -->|No| E[abort 403]
    D -->|Yes| F[Set employeeId, employeeNumber, employeeName]
    F --> G[Read tab from query string]
    G --> H[activeTab = 'apply']
    H --> I[render view]
    
    J[User clicks tab] --> K[switchTab method]
    K --> L[Set activeTab]
    L --> M[dispatch update-url to browser]
    M --> N[Alpine.js replaces URL via history.replaceState]
```

**Key Properties:**
| Property | Type | Purpose |
|----------|------|---------|
| `$activeTab` | `string` | Current tab: `overview`, `my-leaves`, or `apply` |
| `$employeeId` | `?int` | Authenticated employee's ID (resolved in `mount()`) |
| `$employeeNumber` | `?string` | Employee number for dashboard placeholders |
| `$employeeName` | `?string` | Display name |

**Tab Routing:**
- `mount()` reads `?tab=` from query string, validated against allowed values
- `switchTab()` emits `update-url` browser event; Alpine.js listener does `history.replaceState(null, '', '?tab=' + $event.detail.tab)`

### Views

**Wrapper** ([`leave-hub.blade.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/leave-hub.blade.php)):
```blade
<x-qf::navigation-layout context="my-portal" moduleName="hr" :overrides="[...]">
    @livewire('qf.leave-hub')
</x-qf::navigation-layout>
```

**Livewire View** ([`livewire/leave-hub.blade.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/livewire/leave-hub.blade.php)):

| Tab | Component | Config Key | Parameters |
|-----|-----------|------------|------------|
| Overview | `qf.dashboard` | `hr.dashboards.dashboard_leave_hub` | `employee_id`, `employee_number` |
| My Leaves | `qf.data-table` | `leave.leave_request` | `queryFilters: [['employee_id', '=', $employeeId]]` |
| Apply | `qf.wizard` | `leave.wizards.employee_self_service` | `presetData: ['employee_id' => $employeeId]` |

**Critical detail:** The Apply tab passes `presetData => ['employee_id' => $employeeId]`, which pre-fills the employee field and hides it. **Without** `presetData`, the `employee_id` field renders as a `livewire-searchable-select` (see [`leave_request.php:28-50`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/leave_request.php:28-50)), allowing the HR officer to search and pick any employee.

### Routing
`/hr/leave-hub` is resolved by the library's **catch-all route** in [`System/Routes/web.php:64`](/Users/mac/Projects/Libraries/ui-library/src/Core/System/Routes/web.php:64):
```php
Route::get('/{module}/{view}/{id?}', function ($module, $view, $id = null) { ... });
```
This resolves `hr` (module) → `leave-hub` (view) → loads [`hr::leave-hub`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Hr/Resources/views/leave-hub.blade.php).

---

## 2. Current Admin Page Structure

### Route
[`Leave/Routes/web.php:45-47`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Routes/web.php:45-47):
```php
Route::get('/leave/leave-requests', function () {
    return view('leave::leave-requests');
})->name('leave.leave-requests');
```

### Blade
[`leave-requests.blade.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Resources/views/leave-requests.blade.php):
```blade
<x-qf::navigation-layout configKey="leave.leave_request" context="leave" moduleName="leave">
    <livewire:qf.data-table configKey="leave.leave_request" :page-title="$pageTitle" />
</x-qf::navigation-layout>
```
A single data table showing all leave requests. No tabs, no overview, no wizard.

### Navigation
[`Leave/Config/navigation.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Config/navigation.php):
- **Requests** context group: Overview → Leave Requests → Approvals → Leave Balances
- The "Leave Requests" item (`key: leave_request`) points to `/leave/leave-requests`
- All dashboard `view_all_link` fields in [`dashboard_requests_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php) point to `/leave/leave-requests`

---

## 3. Recommendation: Separate Component (NOT reuse LeaveHub with flag)

| Factor | ESS LeaveHub | Admin LeaveHub |
|--------|-------------|----------------|
| **mount() logic** | Resolves employee from `Auth::id()` — always scoped | No employee resolution — admin views all data |
| **Overview dashboard** | Employee-scoped stats (my balance, my pending) | Company-wide stats (all pending, trend charts, team who's out) |
| **Data table tab** | Filtered by `employee_id` | Unfiltered (all requests) |
| **Wizard tab** | `presetData: ['employee_id' => ...]` | No preset — HR selects employee in form |
| **Context** | `my-portal` | `leave` |
| **Models** | Uses `App\Modules\Hr\Models\*` (HR module) | Uses `App\Modules\Leave\Models\*` (Leave module) |

A context flag (`isAdmin = true`) would require conditional logic in every part of the component and views, making it brittle. A separate component is cleaner and follows the existing module boundary.

---

## 4. Implementation Plan

### New Files to Create

#### 4a. `app/Modules/Leave/Http/Livewire/AdminLeaveHub.php`

```php
namespace App\Modules\Leave\Http\Livewire;

use Livewire\Component;

class AdminLeaveHub extends Component
{
    public string $activeTab = 'overview';

    public function mount(): void
    {
        if (request()->has('tab') && in_array(request()->query('tab'), ['overview', 'all-requests', 'apply-for-employee'])) {
            $this->activeTab = request()->query('tab');
        }
    }

    public function switchTab(string $tab): void
    {
        $this->activeTab = $tab;
        $this->dispatch('update-url', tab: $tab);
    }

    public function render()
    {
        return view('leave::livewire.admin-leave-hub');
    }
}
```

Key difference from ESS: **no employee lookup**, **no employee properties**.

#### 4b. `app/Modules/Leave/Resources/views/admin-leave-hub.blade.php`

Wrapper blade (same pattern as ESS wrapper but with `leave` context):
```blade
<x-qf::navigation-layout
    context="leave"
    moduleName="leave"
    :overrides="[
        'top_bar' => ['enabled' => true],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => true],
    ]"
>
    @livewire('qf.admin-leave-hub')
</x-qf::navigation-layout>
```

#### 4c. `app/Modules/Leave/Resources/views/livewire/admin-leave-hub.blade.php`

```blade
<div>
    <div class="d-flex flex-wrap gap-2 align-items-center mb-4">
        <h2>Leave Hub</h2>
    </div>

    <ul class="nav nav-tabs mb-3"
        x-data
        @update-url.window="history.replaceState(null, '', '?tab=' + $event.detail.tab)">
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'overview' ? 'active' : '' }}" wire:click="switchTab('overview')">
                <i class="fas fa-chart-pie"></i> Overview
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'all-requests' ? 'active' : '' }}" wire:click="switchTab('all-requests')">
                <i class="fas fa-list"></i> All Requests
            </button>
        </li>
        <li class="nav-item">
            <button class="nav-link {{ $activeTab === 'apply-for-employee' ? 'active' : '' }}" wire:click="switchTab('apply-for-employee')">
                <i class="fas fa-calendar-plus"></i> Apply for Employee
            </button>
        </li>
    </ul>

    <div>
        @if ($activeTab === 'overview')
            @livewire('qf.dashboard', [
                'configKey' => 'leave.dashboards.dashboard_requests_overview',
            ], key('admin-leave-hub-overview'))
        @elseif ($activeTab === 'all-requests')
            @livewire('qf.data-table', [
                'configKey' => 'leave.leave_request',
            ], key('admin-leave-hub-all-requests'))
        @else
            @livewire('qf.wizard', [
                'configKey' => 'leave.wizards.employee_self_service',
                // NO presetData — employee_id field renders as searchable select
            ], key('admin-leave-hub-apply'))
        @endif
    </div>
</div>
```

### Files to Modify

#### 4d. `app/Modules/Leave/Providers/LeaveServiceProvider.php`

Add to the `boot()` method:
```php
\Livewire\Livewire::component('qf.admin-leave-hub', \App\Modules\Leave\Http\Livewire\AdminLeaveHub::class);
```

#### 4e. `app/Modules/Leave/Routes/web.php`

Add the new route (keep old `/leave/leave-requests` for backward compatibility or redirect):
```php
// NEW: Admin Leave Hub (tabbed layout)
Route::get('/leave/leave-hub', function () {
    return view('leave::admin-leave-hub');
})->name('leave.leave-hub');

// Optional: redirect old page to new hub
Route::redirect('/leave/leave-requests', '/leave/leave-hub?tab=all-requests');
```

#### 4f. `app/Modules/Leave/Config/navigation.php`

Update the "Leave Requests" navigation item to point to the new hub:
```php
[
    'key' => 'leave_request',
    'label' => 'Leave Requests',
    'icon' => 'fas fa-calendar-alt',
    'route' => '/leave/leave-hub',        // was: /leave/leave-requests
    'permission' => 'view_leave_request',
    'order' => 2,
],
```

Optionally, the "Overview" item could also redirect:
```php
'route' => '/leave/leave-hub?tab=overview',  // was: /leave/dashboard-requests-overview
```

#### 4g. Dashboard config `view_all_link` updates

In [`dashboard_requests_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/dashboards/dashboard_requests_overview.php), update `view_all_link` references:
- Line 118: `/leave/leave-requests` → `/leave/leave-hub?tab=all-requests`
- Line 144: `/leave/leave-requests?filter[status]=Approved` → `/leave/leave-hub?tab=all-requests&filter[status]=Approved`

Also in [`dashboard_leave_overview.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/dashboards/dashboard_leave_overview.php):
- Line 187, 239, 374: similar updates

And in [`dashboard.php`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/dashboards/dashboard.php):
- Line 189, 247: similar updates

---

## 5. Tab Content Summary

| Tab | Component | Config Key | Notes |
|-----|-----------|------------|-------|
| **Overview** | `qf.dashboard` | `leave.dashboards.dashboard_requests_overview` | Reuses existing admin dashboard: stat cards, charts, recent requests list, upcoming approved list, quick actions |
| **All Requests** | `qf.data-table` | `leave.leave_request` | Full leave request data table, no employee filter — same as current `/leave/leave-requests` |
| **Apply for Employee** | `qf.wizard` | `leave.wizards.employee_self_service` | Same wizard, but **without** `presetData` — the `employee_id` field renders as a `livewire-searchable-select` letting HR pick any employee |

### ⚠️ Wizard Return Path Issue

The ESS wizard config ([`employee_self_service.php:7`](/Users/mac/Projects/LaravelProjects/hr-consuming-app/app/Modules/Leave/Data/wizards/employee_self_service.php:7)) has:
```php
'returnPath' => '/hr/leave-hub?tab=my-leaves',
```

For the admin version, this should return to `/leave/leave-hub?tab=all-requests`. This can be handled by either:

**Option A:** Override `returnPath` when calling the wizard:
```blade
@livewire('qf.wizard', [
    'configKey' => 'leave.wizards.employee_self_service',
    'returnPath' => '/leave/leave-hub?tab=all-requests',
])
```

**Option B:** Create a separate wizard config `employee_self_service_admin` with a different `returnPath` (more work, but cleaner separation).

**Recommendation:** Option A — pass `returnPath` as a parameter to the wizard component. If the wizard component doesn't support a `returnPath` parameter override, check if it reads it from the config only or can accept a parameter override.

---

## 6. Navigation Flow Diagram

```mermaid
flowchart LR
    subgraph ESS [Employee Self-Service]
        A[/hr/leave-hub] --> B[LeaveHub Livewire]
        B --> C[Overview: My Stats]
        B --> D[My Leaves: Filtered Table]
        B --> E[Apply: Preset Employee]
    end

    subgraph ADMIN [Admin Leave]
        F[/leave/leave-hub] --> G[AdminLeaveHub Livewire]
        G --> H[Overview: Company Stats]
        G --> I[All Requests: Full Table]
        G --> J[Apply for Employee: Searchable Select]
    end

    style ESS fill:#e3f2fd
    style ADMIN fill:#fff3e0