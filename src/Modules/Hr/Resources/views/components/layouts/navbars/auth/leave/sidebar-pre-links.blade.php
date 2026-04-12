{{-- Pre-links section for hr sidebar --}}


<li class="nav-item text-nowrap">
    <a href="/hr/my-leave" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="My Leave">
        <i class="fas fa-user-check me-2"></i>
        @if ($state === 'full')
            <span>My Leave</span>
        @endif
    </a>
</li>

<li class="nav-item text-nowrap">
    <a href="/hr/leave-pending-approval" class="nav-link d-flex align-items-center" data-bs-toggle="tooltip" wire:ignore.self
        data-bs-placement="right" title="Pending Approval">
        <i class="fas fa-clock-rotate-left me-2"></i>
        @if ($state === 'full')
            <span>Pending Approval</span>
        @endif
    </a>
</li>




