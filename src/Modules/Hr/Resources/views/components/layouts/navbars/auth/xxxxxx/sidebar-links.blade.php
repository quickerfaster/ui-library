{{-- Sidebar Links for Hr --}}

@include('hr::components.layouts.navbars.auth.sidebar-pre-links')

{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
<li class="nav-item text-nowrap">
<a href="/hr/saved-reports" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Saved Reports">
<i class="fas fa-user me-2"></i>
@if ($state === 'full')
<span>Saved Reports</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/hr/attendance-adjustments" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Attendance Adjustments">
<i class="fas fa-edit me-2"></i>
@if ($state === 'full')
<span>Attendance Adjustments</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
<a href="/hr/clock-events" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
data-bs-placement="right" title="Clock Events">
<i class="fas fa-clock me-2"></i>
@if ($state === 'full')
<span>Clock Events</span>
@endif
</a>
</li>
<li class="nav-item text-nowrap">
    <a href="/hr/attendance-sessions" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="Attendance Sessions">
        <i class="fas fa-hourglass-half me-2"></i>
        @if ($state === 'full')
            <span>Attendance Sessions</span>
        @endif
    </a>
</li>

@include('hr::components.layouts.navbars.auth.sidebar-post-links')
