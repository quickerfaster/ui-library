{{-- Pre-links section for Hr --}}
    <a href="/admin/dashboard" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1 m-0 mt-2 me-2" style="height:2.2em">
        <i class="fas fa-cogs"></i>
        <span>Admin</span>
    </a>
    <li class="nav-item ms-4">
        <a href="/hr/dashboard"
            class="nav-link @if(request()->is('hr/dashboard') || request()->is('hr/dashboard/*')) fw-bold text-primary @endif">
            @if(request()->is('hr/dashboard') || request()->is('hr/dashboard/*'))
                <i class="fas fa-tachometer-alt" aria-hidden="true"></i>
            @endif
            <span>Dashboard</span>
        </a>
    </li>
