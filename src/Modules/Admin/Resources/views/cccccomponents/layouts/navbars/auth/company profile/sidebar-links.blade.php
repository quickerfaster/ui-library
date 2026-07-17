{{-- Sidebar Links for Admin --}}

@include('admin::components.layouts.navbars.auth.organization/sidebar-pre-links')

{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
<li class="nav-item text-nowrap">
<a href="/admin/admin/dashboard-organization-overview" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Overview">
<i class="fas fa-chart-bar me-2"></i>
@if ($state === 'full')
<span>Overview</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/admin/admin/locations" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Locations">
<i class="fas fa-map-marker-alt me-2"></i>
@if ($state === 'full')
<span>Locations</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/admin/admin/companies" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Companies">
<i class="fas fa-building me-2"></i>
@if ($state === 'full')
<span>Companies</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/admin/admin/departments" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Departments">
<i class="fas fa-sitemap me-2"></i>
@if ($state === 'full')
<span>Departments</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/admin/admin/job-titles" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Job Titles">
<i class="fas fa-briefcase me-2"></i>
@if ($state === 'full')
<span>Job Titles</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
    <a href="/admin/admin/shifts" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="Shifts">
        <i class="fas fa-calendar-day me-2"></i>
        @if ($state === 'full')
            <span>Shifts</span>
        @endif
    </a>
</li>

@include('admin::components.layouts.navbars.auth.organization/sidebar-post-links')
