{{-- Sidebar Links for Admin --}}

@include('admin::components.layouts.navbars.auth.sidebar-pre-links')

{{-- Generated Links --}}
<li class="nav-item text-nowrap">
    <a href="/admin/system-settings" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="System Settings">
        <i class="fas fa-user me-2"></i>
        @if ($state === 'full')
            <span>System Settings</span>
        @endif
    </a>
</li>

@include('admin::components.layouts.navbars.auth.sidebar-post-links')
