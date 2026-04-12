<x-layout>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">{{ __('Dashboard') }}</div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    {{ __('You are logged in!') }}
                </div>
                
                <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                    @csrf
                    <button type="submit" class="btn btn-link nav-link"
                        style="display: inline; border: none; background: none; padding: 0; cursor: pointer;">
                        {{ __('Logout') }}
                    </button>
                </form>

            </div>
        </div>
    </div>
</x-layout>
