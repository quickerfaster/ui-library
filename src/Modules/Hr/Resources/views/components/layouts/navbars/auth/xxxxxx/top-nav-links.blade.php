{{-- Top Nav Links for Hr --}}

@include('hr::components.layouts.navbars.auth.top-nav-pre-links')

{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
{{-- Generated Links --}}
<li class="nav-item">
    <a href="/hr/hr/dashboard-policies-overview"
        class="nav-link @if(request()->is('hr/hr/dashboard-policies-overview') || request()->is('hr/hr/dashboard-policies-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/hr/dashboard-policies-overview') || request()->is('hr/hr/dashboard-policies-overview/*'))
            <i class="fas fas fa-gavel" aria-hidden="true"></i>
        @endif
        <span>Policies</span>
    </a>
</li>
<li class="nav-item">
    <a href="/hr/reports"
        class="nav-link @if(request()->is('hr/reports') || request()->is('hr/reports/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/reports') || request()->is('hr/reports/*'))
            <i class="fas fas fa-file-alt" aria-hidden="true"></i>
        @endif
        <span>Reports</span>
    </a>
</li>
<li class="nav-item">
    <a href="/hr/hr/dashboard-people-overview"
        class="nav-link @if(request()->is('hr/hr/dashboard-people-overview') || request()->is('hr/hr/dashboard-people-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/hr/dashboard-people-overview') || request()->is('hr/hr/dashboard-people-overview/*'))
            <i class="fas fas fa-user-tie" aria-hidden="true"></i>
        @endif
        <span>People</span>
    </a>
</li>
<li class="nav-item">
    <a href="/hr/hr/dashboard-payroll-overview"
        class="nav-link @if(request()->is('hr/hr/dashboard-payroll-overview') || request()->is('hr/hr/dashboard-payroll-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/hr/dashboard-payroll-overview') || request()->is('hr/hr/dashboard-payroll-overview/*'))
            <i class="fas fas fa-file-invoice-dollar" aria-hidden="true"></i>
        @endif
        <span>Payroll</span>
    </a>
</li>
<li class="nav-item">
    <a href="/hr/hr/dashboard-leave-overview"
        class="nav-link @if(request()->is('hr/hr/dashboard-leave-overview') || request()->is('hr/hr/dashboard-leave-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/hr/dashboard-leave-overview') || request()->is('hr/hr/dashboard-leave-overview/*'))
            <i class="fas fas fa-user-check" aria-hidden="true"></i>
        @endif
        <span>Leave</span>
    </a>
</li>
<li class="nav-item">
    <a href="/hr/hr/dashboard-time-overview"
        class="nav-link @if(request()->is('hr/hr/dashboard-time-overview') || request()->is('hr/hr/dashboard-time-overview/*')) fw-bold text-primary @endif">
        @if(request()->is('hr/hr/dashboard-time-overview') || request()->is('hr/hr/dashboard-time-overview/*'))
            <i class="fas fas fa-user-clock" aria-hidden="true"></i>
        @endif
        <span>Time</span>
    </a>
</li>

@include('hr::components.layouts.navbars.auth.top-nav-post-links')
