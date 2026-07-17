{{-- Top Nav Links for Admin --}}

@include('admin::components.layouts.navbars.auth.top-nav-pre-links')

{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
<li class="nav-item">
    <a href="/admin/admin/users"
        class="nav-link @if(request()->is('admin/admin/users') || request()->is('admin/admin/users/*')) fw-bold text-primary @endif">
        @if(request()->is('admin/admin/users') || request()->is('admin/admin/users/*'))
            <i class="fas fas fa-users-cog" aria-hidden="true"></i>
        @endif
        <span>Users & Permissions</span>
    </a>
</li>
<li class="nav-item">
    <a href="/admin/admin/activity-logs"
        class="nav-link @if(request()->is('admin/admin/activity-logs') || request()->is('admin/admin/activity-logs/*')) fw-bold text-primary @endif">
        @if(request()->is('admin/admin/activity-logs') || request()->is('admin/admin/activity-logs/*'))
            <i class="fas fas fa-history" aria-hidden="true"></i>
        @endif
        <span>Audit</span>
    </a>
</li>
<li class="nav-item">
    <a href="/admin/admin/general-settings"
        class="nav-link @if(request()->is('admin/admin/general-settings') || request()->is('admin/admin/general-settings/*')) fw-bold text-primary @endif">
        @if(request()->is('admin/admin/general-settings') || request()->is('admin/admin/general-settings/*'))
            <i class="fas fas fa-cogs" aria-hidden="true"></i>
        @endif
        <span>General Settings</span>
    </a>
</li>
<li class="nav-item">
    <a href="/admin/admin/dashboard-organization-overview"
        class="nav-link @if(request()->is('admin/admin/dashboard-organization-overview') || request()->is('admin/admin/dashboard-organization-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('admin/admin/dashboard-organization-overview') || request()->is('admin/admin/dashboard-organization-overview/*'))
            <i class="fas fas fa-building" aria-hidden="true"></i>
        @endif
        <span>Organization</span>
    </a>
</li>

@include('admin::components.layouts.navbars.auth.top-nav-post-links')
