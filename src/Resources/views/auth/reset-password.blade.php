<x-guest-layout>
<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">{{ __('Reset Password') }}</div>

            <div class="card-body">
                <form method="POST" action="{{ route('password.update') }}">
                    @csrf

                    <input type="hidden" name="token" value="{{ $token ?? request()->route('token') ?? '' }}">

                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('Email Address') }}</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $email ?? '') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('New Password') }}</label>
                        <div class="position-relative">
                            <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent px-2"
                                    onclick="var i=this.parentElement.querySelector('input');var e=this.querySelector('i');i.type=i.type==='password'?'text':'password';e.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';"
                                    tabindex="-1">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password-confirm" class="form-label">{{ __('Confirm New Password') }}</label>
                        <div class="position-relative">
                            <input id="password-confirm" type="password" class="form-control" name="password_confirmation" required>
                            <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent px-2"
                                    onclick="var i=this.parentElement.querySelector('input');var e=this.querySelector('i');i.type=i.type==='password'?'text':'password';e.className=i.type==='password'?'fas fa-eye':'fas fa-eye-slash';"
                                    tabindex="-1">
                                <i class="fas fa-eye text-muted"></i>
                            </button>
                        </div>
                    </div>

                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">{{ __('Reset Password') }}</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
</x-guest-layout>
