<div class="card border-0 shadow-sm rounded-4 h-100">
    <div class="card-body p-4 text-center">
        {{-- Status Icon --}}
        <div class="mb-3">
            @if ($status === 'clocked_in')
                <div class="icon-shape icon-xl rounded-circle bg-gradient-success text-white d-inline-flex align-items-center justify-content-center"
                     style="width: 72px; height: 72px;">
                    <i class="fa-solid fa-user-check fs-2"></i>
                </div>
            @else
                <div class="icon-shape icon-xl rounded-circle bg-gradient-secondary text-white d-inline-flex align-items-center justify-content-center"
                     style="width: 72px; height: 72px;">
                    <i class="fa-solid fa-user-clock fs-2"></i>
                </div>
            @endif
        </div>

        {{-- Status Text --}}
        <h5 class="fw-bolder mb-1">
            @if ($status === 'clocked_in')
                <span class="text-success">Clocked In</span>
            @else
                <span class="text-secondary">Not Clocked In</span>
            @endif
        </h5>

        @if ($status === 'clocked_in' && $clockedInSince)
            <p class="text-sm text-secondary mb-3">
                Since {{ $clockedInSince }}
            </p>
        @else
            <p class="text-sm text-secondary mb-3">
                Tap the button below to clock in
            </p>
        @endif

        {{-- Error Message --}}
        @if ($error)
            <div class="alert alert-danger alert-sm py-2 px-3 mb-3">
                <i class="fa-solid fa-triangle-exclamation me-1"></i>
                {{ $error }}
            </div>
        @endif

        {{-- Toggle Button --}}
        <button
            wire:click="toggle"
            wire:loading.attr="disabled"
            wire:target="toggle"
            class="btn btn-lg w-100 rounded-3 fw-bold
                @if ($status === 'clocked_out')
                    btn-success
                @else
                    btn-danger
                @endif
            "
            style="min-height: 56px; font-size: 1.1rem;"
        >
            <span wire:loading.remove wire:target="toggle">
                @if ($status === 'clocked_out')
                    <i class="fa-solid fa-sign-in-alt me-2"></i> Clock In
                @else
                    <i class="fa-solid fa-sign-out-alt me-2"></i> Clock Out
                @endif
            </span>
            <span wire:loading wire:target="toggle">
                <span class="spinner-border spinner-border-sm me-2" role="status"></span>
                Recording...
            </span>
        </button>

        {{-- Last Event Info --}}
        @if ($lastEventAt && $status === 'clocked_out')
            <p class="text-xs text-muted mt-2 mb-0">
                Last clock-out: {{ \Carbon\Carbon::parse($lastEventAt)->format('M j, g:i A') }}
            </p>
        @endif
    </div>
</div>