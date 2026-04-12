<x-guest-layout>


<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">{{ __('Login') }}</div>

            <div class="card-body">
                <form method="POST" action="{{ route('login') }}">
                    @csrf

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">{{ __('Email Address') }}</label>
                        <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email') }}" required autofocus>
                        @error('email')
                            <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">{{ __('Password') }}</label>
                        <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                        @error('password')
                            <div class="invalid-feedback" role="alert">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Remember Me -->
                    <div class="mb-3 form-check">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember" {{ old('remember') ? 'checked' : '' }}>
                        <label class="form-check-label" for="remember">{{ __('Remember Me') }}</label>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <button type="submit" class="btn btn-primary">{{ __('Login') }}</button>
                        @if (Route::has('password.request'))
                            <a class="btn btn-link" href="{{ route('password.request') }}">{{ __('Forgot Your Password?') }}</a>
                        @endif
                    </div>
                </form>

                <!-- Social Login Buttons (optional) -->
                @if(config('quicker-faster-ui.socialite.enabled', false))
                    <hr>
                    <div class="d-grid gap-2">
                        @if(config('quicker-faster-ui.socialite.providers.google', false))
                            <a href="{{ route('socialite.redirect', 'google') }}" class="btn btn-outline-dark">
                                <i class="bi bi-google"></i> {{ __('Login with Google') }}
                            </a>
                        @endif
                        @if(config('quicker-faster-ui.socialite.providers.github', false))
                            <a href="{{ route('socialite.redirect', 'github') }}" class="btn btn-outline-dark">
                                <i class="bi bi-github"></i> {{ __('Login with GitHub') }}
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

</x-guest-layout>

