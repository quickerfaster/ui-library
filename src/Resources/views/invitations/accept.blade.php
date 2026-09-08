{{-- Accept Invitation Page --}}
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-6 col-lg-5">

            {{-- Error State --}}
            @if($error)
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-exclamation-triangle fa-3x text-warning"></i>
                        </div>
                        <h4 class="mb-3">Invitation Unavailable</h4>
                        <p class="text-muted">{{ $error }}</p>
                        <a href="{{ url('/') }}" class="btn btn-outline-secondary mt-2">
                            <i class="fas fa-home me-1"></i> Go Home
                        </a>
                    </div>
                </div>

            {{-- Success State --}}
            @elseif($isAccepted)
                <div class="card shadow-sm border-0">
                    <div class="card-body text-center p-5">
                        <div class="mb-4">
                            <i class="fas fa-check-circle fa-3x text-success"></i>
                        </div>
                        <h4 class="mb-3">Setup Complete!</h4>
                        <p class="text-muted">Your account has been created and you are now logged in.</p>
                        <a href="{{ route(config('ui-library.home_route', 'admin.dashboard')) }}" class="btn btn-primary mt-2">
                            <i class="fas fa-arrow-right me-1"></i> Go to Dashboard
                        </a>
                    </div>
                </div>

            {{-- Accept Form --}}
            @elseif($isValid)
                <div class="card shadow-sm border-0">
                    <div class="card-header bg-white border-0 pt-4 pb-0 text-center">
                        <h4 class="mb-1">Complete Your Setup</h4>
                        <p class="text-muted small mb-0">
                            You've been invited as <strong>{{ $email }}</strong>
                        </p>
                    </div>
                    <div class="card-body p-4">
                        <form wire:submit.prevent="accept">
                            @if(!$email)
                                <div class="mb-3">
                                    <label for="name" class="form-label">Full Name</label>
                                    <input type="text" id="name" class="form-control @error('name') is-invalid @enderror"
                                        wire:model.lazy="name" placeholder="Enter your full name">
                                    @error('name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            @endif

                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" id="password" class="form-control @error('password') is-invalid @enderror"
                                    wire:model.lazy="password" placeholder="Create a password (min. 8 characters)">
                                @error('password')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="mb-4">
                                <label for="password_confirmation" class="form-label">Confirm Password</label>
                                <input type="password" id="password_confirmation" class="form-control"
                                    wire:model.lazy="password_confirmation" placeholder="Re-enter your password">
                            </div>

                            <button type="submit" class="btn btn-primary w-100">
                                <i class="fas fa-check me-1"></i> Complete Setup
                            </button>
                        </form>
                    </div>
                </div>
            @endif

        </div>
    </div>
</div>