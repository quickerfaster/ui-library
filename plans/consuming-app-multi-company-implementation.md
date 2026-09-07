# Consuming App: Multi-Company User Assignment — Implementation Guide

> **Status**: Implementation Guide  
> **Date**: 2026-09-07  
> **Audience**: Consuming app developers  
> **Prerequisite**: Library changes documented in [`plans/multi-company-user-assignment-recommendation.md`](plans/multi-company-user-assignment-recommendation.md) are already applied  

---

## Overview

This document describes the consuming-app-side implementation steps required to enable multi-company user assignment. The library-side changes (pivot table relationship on the User model trait, company switcher UX improvements, and config defaults) are already in place. The consuming app must provide the database migration, the [`CompanyProvider`](src/Contracts/Navigation/CompanyProvider.php:8) implementation, the assignment UI, a default company seeder, and the config overrides.

All files referenced in this document live under `app/` in the consuming application. **Do not create any of these files in the library workspace** — they belong to the consuming app and are documented here for reference.

---

## Phase 1 — Database & Models

### 1.1 Pivot Migration

Create `app/Modules/Organization/Database/Migrations/2026_09_07_000000_create_company_user_table.php`:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('company_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained('companies')->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['user_id', 'company_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('company_user');
    }
};
```

**Design notes**:
- The `company_user` table name matches the convention used in [`HasUILibraryUser::companies()`](src/Traits/HasUILibraryUser.php:83) — the library trait already defines `$this->belongsToMany(Company::class, 'company_user')`.
- The composite unique index on `[user_id, company_id]` prevents duplicate assignments.
- `cascadeOnDelete()` ensures cleanup when a user or company is deleted.
- The `companies` table is already created by the library's core migration.

### 1.2 User Model Relationship

If the consuming app's `User` model already uses [`HasUILibraryUser`](src/Traits/HasUILibraryUser.php:34), the `companies()` BelongsToMany relationship is **already inherited** from the trait. No additional model changes are required.

If the User model does **not** use `HasUILibraryUser`, add the relationship manually to `app/Models/User.php`:

```php
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;

public function companies(): BelongsToMany
{
    return $this->belongsToMany(Company::class, 'company_user')->withTimestamps();
}
```

**Verification**: After migration, `$user->companies` should return a `BelongsToMany` relationship. Test with:

```php
$user = User::first();
$user->companies()->sync([1, 2, 3]); // Should create 3 pivot rows
$user->companies->pluck('id');       // Should return [1, 2, 3]
```

---

## Phase 2 — CompanyProvider Rewrite

### 2.1 Update `HrsCompanyProvider`

The consuming app's existing [`CompanyProvider`](src/Contracts/Navigation/CompanyProvider.php:8) implementation (typically `app/Modules/Hr/Providers/HrsCompanyProvider.php`) must be rewritten to support multi-company users.

**File**: `app/Modules/Hr/Providers/HrsCompanyProvider.php`

```php
<?php

namespace App\Modules\Hr\Providers;

use Illuminate\Support\Collection;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;

class HrsCompanyProvider implements CompanyProvider
{
    /**
     * Get all companies the user has access to.
     *
     * - super_admin / company_admin: all companies
     * - other users: companies from the company_user pivot table
     * - fallback: single-company lookup via employee record (legacy)
     */
    public function getCompanies($user): Collection
    {
        // Admins see all companies
        if ($user->hasAnyRole(['super_admin', 'company_admin'])) {
            return \QuickerFaster\UILibrary\Core\Organization\Models\Company::orderBy('name')->get();
        }

        // Multi-company users: load from pivot
        $companies = $user->companies()->orderBy('name')->get();

        if ($companies->isNotEmpty()) {
            return $companies;
        }

        // Fallback: legacy single-company lookup via employee record
        // (preserves backwards compatibility during migration)
        $employee = $user->employee;
        if ($employee && $employee->company) {
            return collect([$employee->company]);
        }

        return collect();
    }

    /**
     * Get the user's current/default company ID.
     *
     * - super_admin / company_admin: 0 (All Companies)
     * - multi-company users: first company from pivot
     * - fallback: user->company_id (legacy single-company)
     */
    public function getCurrentCompanyId($user): ?int
    {
        // Admins default to "All Companies"
        if ($user->hasAnyRole(['super_admin', 'company_admin'])) {
            return 0;
        }

        // Multi-company users: first assigned company
        $firstCompany = $user->companies()->first();
        if ($firstCompany) {
            return $firstCompany->id;
        }

        // Fallback: legacy company_id column
        return $user->company_id ?? null;
    }
}
```

### 2.2 Bind the Provider

In `app/Providers/AppServiceProvider.php` (or a dedicated service provider), ensure the binding is registered:

```php
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use App\Modules\Hr\Providers\HrsCompanyProvider;

public function register(): void
{
    $this->app->singleton(CompanyProvider::class, HrsCompanyProvider::class);
}
```

Alternatively, publish the library config and set the `company_provider` key:

```php
// config/ui-library.php (consuming app override)
'navigation' => [
    'company_provider' => \App\Modules\Hr\Providers\HrsCompanyProvider::class,
],
```

---

## Phase 3 — Assignment UI

### 3.1 Livewire Component

Create `app/Modules/Organization/Http/Livewire/UserCompanyAssignment.php`:

```php
<?php

namespace App\Modules\Organization\Http\Livewire;

use Livewire\Component;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;
use App\Models\User;

class UserCompanyAssignment extends Component
{
    /** @var int|null The currently selected user ID */
    public ?int $userId = null;

    /** @var string Search term for the user selector */
    public string $userSearch = '';

    /** @var string Search term for the company selector */
    public string $companySearch = '';

    /** @var array Company IDs currently assigned to the selected user */
    public array $assignedCompanyIds = [];

    /** @var \Illuminate\Support\Collection All companies (filtered by search) */
    public $availableCompanies;

    /** @var \Illuminate\Support\Collection Users matching the search */
    public $userResults;

    /** @var User|null The loaded user model */
    public $selectedUser = null;

    protected $listeners = ['refreshCompanyAssignment' => '$refresh'];

    public function mount(): void
    {
        $this->availableCompanies = collect();
        $this->userResults = collect();
    }

    /**
     * Search for users by name or email.
     */
    public function updatedUserSearch(): void
    {
        if (strlen($this->userSearch) < 2) {
            $this->userResults = collect();
            return;
        }

        $this->userResults = User::where('name', 'like', "%{$this->userSearch}%")
            ->orWhere('email', 'like', "%{$this->userSearch}%")
            ->limit(10)
            ->get();
    }

    /**
     * Select a user and load their assigned companies.
     */
    public function selectUser(int $userId): void
    {
        $this->userId = $userId;
        $this->selectedUser = User::find($userId);
        $this->userSearch = $this->selectedUser?->name ?? '';
        $this->userResults = collect();

        if ($this->selectedUser) {
            $this->assignedCompanyIds = $this->selectedUser->companies()->pluck('companies.id')->toArray();
        }

        $this->loadAvailableCompanies();
    }

    /**
     * Clear the selected user.
     */
    public function clearUser(): void
    {
        $this->userId = null;
        $this->selectedUser = null;
        $this->userSearch = '';
        $this->assignedCompanyIds = [];
        $this->availableCompanies = collect();
    }

    /**
     * Search for available companies.
     */
    public function updatedCompanySearch(): void
    {
        $this->loadAvailableCompanies();
    }

    /**
     * Toggle a company assignment for the selected user.
     */
    public function toggleCompany(int $companyId): void
    {
        if (in_array($companyId, $this->assignedCompanyIds)) {
            $this->assignedCompanyIds = array_values(array_diff($this->assignedCompanyIds, [$companyId]));
        } else {
            $this->assignedCompanyIds[] = $companyId;
        }
    }

    /**
     * Save the current assignments.
     */
    public function save(): void
    {
        if (!$this->selectedUser) {
            $this->dispatch('notify', type: 'error', message: 'No user selected.');
            return;
        }

        $this->selectedUser->companies()->sync($this->assignedCompanyIds);

        $this->dispatch('notify', type: 'success', message: 'Company assignments saved.');
        $this->dispatch('companyAssignmentSaved');
    }

    /**
     * Load available companies, filtered by search.
     */
    protected function loadAvailableCompanies(): void
    {
        $query = Company::orderBy('name');

        if (!empty($this->companySearch)) {
            $query->where('name', 'like', "%{$this->companySearch}%");
        }

        $this->availableCompanies = $query->limit(50)->get();
    }

    public function render()
    {
        return view('livewire.organization.user-company-assignment');
    }
}
```

### 3.2 Blade View

Create `app/Modules/Organization/Resources/views/livewire/user-company-assignment.blade.php`:

```blade
<div class="card">
    <div class="card-header">
        <h6 class="mb-0">User Company Assignment</h6>
    </div>
    <div class="card-body">

        {{-- User Selector --}}
        <div class="mb-4">
            <label class="form-label">Select User</label>
            <div class="position-relative">
                <input
                    type="text"
                    class="form-control"
                    placeholder="Search by name or email..."
                    wire:model.live.debounce.300ms="userSearch"
                    @if($selectedUser) readonly @endif
                >

                @if ($selectedUser)
                    <button
                        type="button"
                        class="btn btn-sm btn-link position-absolute end-0 top-50 translate-middle-y"
                        wire:click="clearUser">
                        <i class="fas fa-times text-danger"></i>
                    </button>
                @endif

                {{-- User search results dropdown --}}
                @if ($userResults->isNotEmpty() && !$selectedUser)
                    <ul class="dropdown-menu show w-100 shadow border-0" style="max-height: 200px; overflow-y: auto;">
                        @foreach ($userResults as $user)
                            <li>
                                <a class="dropdown-item" href="#" wire:click.prevent="selectUser({{ $user->id }})">
                                    <span class="fw-medium">{{ $user->name }}</span>
                                    <small class="text-muted d-block">{{ $user->email }}</small>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>
        </div>

        {{-- Company Assignment --}}
        @if ($selectedUser)
            <div class="mb-3">
                <label class="form-label">Assigned Companies</label>

                {{-- Currently assigned companies (chips) --}}
                @if (!empty($assignedCompanyIds))
                    <div class="mb-2">
                        @php
                            $assignedCompanies = \QuickerFaster\UILibrary\Core\Organization\Models\Company::whereIn('id', $assignedCompanyIds)->get();
                        @endphp
                        @foreach ($assignedCompanies as $company)
                            <span class="badge bg-primary me-1 mb-1 p-2">
                                {{ $company->name }}
                                <a href="#" class="text-white ms-1" wire:click.prevent="toggleCompany({{ $company->id }})">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                        @endforeach
                    </div>
                @else
                    <p class="text-muted mb-2">No companies assigned.</p>
                @endif

                {{-- Company search --}}
                <div class="input-group mb-3">
                    <span class="input-group-text"><i class="fas fa-search"></i></span>
                    <input
                        type="text"
                        class="form-control"
                        placeholder="Search companies..."
                        wire:model.live.debounce.300ms="companySearch">
                </div>

                {{-- Available companies list --}}
                <div class="list-group" style="max-height: 250px; overflow-y: auto;">
                    @forelse ($availableCompanies as $company)
                        <label class="list-group-item d-flex align-items-center">
                            <input
                                type="checkbox"
                                class="form-check-input me-2"
                                value="{{ $company->id }}"
                                wire:model.live="assignedCompanyIds"
                            >
                            <span>{{ $company->name }}</span>
                            @if ($company->code)
                                <small class="text-muted ms-2">({{ $company->code }})</small>
                            @endif
                        </label>
                    @empty
                        <div class="list-group-item text-muted">
                            @if ($companySearch)
                                No companies matching "{{ $companySearch }}".
                            @else
                                No companies available.
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>

            {{-- Save button --}}
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-primary" wire:click="save">
                    <i class="fas fa-save me-1"></i> Save Assignments
                </button>
            </div>
        @else
            <div class="text-center text-muted py-4">
                <i class="fas fa-user-friends fa-3x mb-3 opacity-4"></i>
                <p>Search and select a user to manage their company assignments.</p>
            </div>
        @endif

    </div>
</div>
```

### 3.3 Register the Component

In the consuming app's service provider (e.g., `app/Modules/Organization/Providers/OrganizationServiceProvider.php`):

```php
use Livewire\Livewire;
use App\Modules\Organization\Http\Livewire\UserCompanyAssignment;

public function boot(): void
{
    Livewire::component('qf.user-company-assignment', UserCompanyAssignment::class);
}
```

### 3.4 Usage

Embed the component in any Blade view (e.g., a user management page):

```blade
@livewire('qf.user-company-assignment')
```

---

## Phase 4 — Default Company Seeder

### 4.1 Create the Seeder

Create `app/Modules/Organization/Database/Seeders/DefaultCompanySeeder.php`:

```php
<?php

namespace App\Modules\Organization\Database\Seeders;

use Illuminate\Database\Seeder;
use QuickerFaster\UILibrary\Core\Organization\Models\Company;

class DefaultCompanySeeder extends Seeder
{
    /**
     * Create a default company if none exists, and assign all users to it.
     *
     * This seeder is idempotent — it checks Company::count() === 0 before
     * creating, and uses syncWithoutDetaching to avoid duplicate assignments
     * on re-runs.
     */
    public function run(): void
    {
        // Create the default company (idempotent)
        if (Company::count() === 0) {
            $company = Company::create([
                'name' => config('app.name', 'Default Company'),
                'code' => 'DEFAULT',
            ]);

            $this->command?->info("Default company created: {$company->name} (ID: {$company->id})");
        } else {
            $company = Company::first();

            $this->command?->info("Default company already exists: {$company->name} (ID: {$company->id})");
        }

        // Assign all existing users to the default company
        $userModel = config('ui-library.user.model')
            ?? config('auth.providers.users.model')
            ?? 'App\\Models\\User';

        if (!class_exists($userModel)) {
            $this->command?->warn("User model '{$userModel}' not found. Skipping user assignment.");
            return;
        }

        $users = $userModel::all();
        $count = 0;

        foreach ($users as $user) {
            // syncWithoutDetaching ensures no duplicates on re-run
            $user->companies()->syncWithoutDetaching([$company->id]);
            $count++;
        }

        $this->command?->info("Assigned {$count} users to default company.");
    }
}
```

### 4.2 Register in DatabaseSeeder

In `database/seeders/DatabaseSeeder.php`:

```php
public function run(): void
{
    $this->call([
        // ... other seeders
        \App\Modules\Organization\Database\Seeders\DefaultCompanySeeder::class,
    ]);
}
```

---

## Phase 5 — Configuration

### 5.1 Consuming App Config Override

In the consuming app's published `config/ui-library.php`, verify or set the following keys:

```php
'multitenancy' => [

    /*
    |------------------------------------------------------------------
    | Company Switcher Roles
    |------------------------------------------------------------------
    | Set to ['*'] to allow all authenticated users to see the company
    | switcher. The library default is already ['*'] as of 2026-09-07.
    |
    | For tighter control, restrict to specific roles:
    |   'switcher_roles' => ['super_admin', 'company_admin'],
    */
    'switcher_roles' => ['*'],

    /*
    |------------------------------------------------------------------
    | All Companies Access
    |------------------------------------------------------------------
    | Roles that can select "All Companies". Use '*' for all roles or
    | an array of specific role names.
    */
    'all_companies_roles' => '*',

    /*
    |------------------------------------------------------------------
    | Default Company Mode
    |------------------------------------------------------------------
    | 'first' — default to the first company in the user's list
    | 'all'   — default to "All Companies" mode (0)
    | 'none'  — no default, user must pick
    */
    'default_mode' => 'first',
],
```

### 5.2 Module Registration

If the `Organization` module is not already registered in `config/ui-library.php` under `modules`, add it:

```php
'modules' => [
    // ... existing modules
    'organization' => [
        'enabled' => true,
        'label' => 'Organization',
        'icon' => 'fa-sitemap',
        'route' => 'organization.dashboard',
        'order' => 100,
        'roles' => ['super_admin', 'company_admin'],
        'core' => false,
        'user_facing' => true,
        'depends_on' => [],
    ],
],
```

---

## Phase 6 — Navigation

The library already provides the navigation entry point for the Company Assignments page. The `user_company_assignments` item is registered in [`src/Core/System/Config/navigation.php`](src/Core/System/Config/navigation.php) under the `accounts` context group, with:

- **Key**: `user_company_assignments`
- **Label**: `Company Assignments`
- **Icon**: `fa-solid fa-building`
- **Route**: `/system/user-company-assignments`
- **Permission**: `manage_user_company_assignments`
- **Order**: 45 (between Invitations and Account Activity)

### 6.1 Create the Blade View

The consuming app needs to create a Blade view at `app/Modules/Admin/Resources/views/user-company-assignments.blade.php` that embeds the assignment component:

```blade
{{-- app/Modules/Admin/Resources/views/user-company-assignments.blade.php --}}
@extends('layouts.app')

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                @livewire('qf.user-company-assignment')
            </div>
        </div>
    </div>
@endsection
```

### 6.2 Route Resolution

The navigation route `/system/user-company-assignments` must resolve to this view. In the consuming app's route file (e.g., `app/Modules/Admin/Routes/web.php`), register:

```php
Route::get('/system/user-company-assignments', function () {
    return view('admin.user-company-assignments');
})->middleware(['auth', 'can:manage_user_company_assignments']);
```

### 6.3 Permission Registration

The permission `manage_user_company_assignments` should be registered in the consuming app's permission seeder (or a dedicated seeder) to ensure it is available for role-based access control.

---

## Implementation Checklist

| # | Phase | File | Status |
|---|-------|------|:------:|
| 1 | Phase 1 | `app/Modules/Organization/Database/Migrations/2026_09_07_000000_create_company_user_table.php` | ☐ |
| 2 | Phase 1 | Verify `User` model has `companies()` BelongsToMany (inherited from `HasUILibraryUser`) | ☐ |
| 3 | Phase 2 | `app/Modules/Hr/Providers/HrsCompanyProvider.php` — rewrite `getCompanies()` and `getCurrentCompanyId()` | ☐ |
| 4 | Phase 2 | Bind `CompanyProvider` contract in `AppServiceProvider` or config | ☐ |
| 5 | Phase 3 | `app/Modules/Organization/Http/Livewire/UserCompanyAssignment.php` — Livewire component | ☐ |
| 6 | Phase 3 | `app/Modules/Organization/Resources/views/livewire/user-company-assignment.blade.php` — Blade view | ☐ |
| 7 | Phase 3 | Register component in service provider | ☐ |
| 8 | Phase 3 | Embed `@livewire('qf.user-company-assignment')` in a user management page | ☐ |
| 9 | Phase 4 | `app/Modules/Organization/Database/Seeders/DefaultCompanySeeder.php` | ☐ |
| 10 | Phase 4 | Register seeder in `database/seeders/DatabaseSeeder.php` | ☐ |
| 11 | Phase 5 | Verify/update `config/ui-library.php` multitenancy settings | ☐ |
| 12 | Phase 5 | Register `organization` module if needed | ☐ |
| 13 | Phase 6 | `app/Modules/Admin/Resources/views/user-company-assignments.blade.php` — page view | ☐ |
| 14 | Phase 6 | Register route `/system/user-company-assignments` in admin routes | ☐ |
| 15 | Phase 6 | Register `manage_user_company_assignments` permission in seeder | ☐ |

---

## Notes

- **Library changes are already applied**: The [`HasUILibraryUser::companies()`](src/Traits/HasUILibraryUser.php:83) BelongsToMany relationship, the [`InstallCommand`](src/Console/Commands/InstallCommand.php) fix (removing non-existent `OrganizationSeeder`), the [`TopNav`](src/Http/Livewire/Layouts/Navs/TopNav.php:767) single-company hide behavior, and the [`switcher_roles`](src/Config/ui-library.php:556) default of `['*']` are all already implemented in the library.
- **No changes to `app/` in this workspace**: All files described in this document are consuming-app files. They do not exist in the library workspace and should not be created there.
- **Backward compatibility**: The `HrsCompanyProvider` implementation preserves the legacy `user->company_id` fallback path, allowing a gradual migration from single-company to multi-company.
- **Pivot table simplicity**: The `company_user` pivot in this implementation is minimal (`user_id`, `company_id`, `timestamps`). If pivot extra data (e.g., `is_default`, `role`) is needed later, add columns to the migration and use `sync()` with pivot data arrays.