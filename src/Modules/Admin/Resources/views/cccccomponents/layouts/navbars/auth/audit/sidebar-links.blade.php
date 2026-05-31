{{-- Sidebar Links for Admin --}}

@include('admin::components.layouts.navbars.auth.audit/sidebar-pre-links')

{{-- Generated Links --}}
<li class="nav-item text-nowrap">
    <a href="/admin/admin/activity-logs" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="Activity Log">
        <i class="fas fa-history me-2"></i>
        @if ($state === 'full')
            <span>Activity Log</span>
        @endif
    </a>
</li>

@include('admin::components.layouts.navbars.auth.audit/sidebar-post-links')
