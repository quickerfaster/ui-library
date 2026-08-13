<x-qf::navigation-layout
    moduleName="common"
    :overrides="[
        'top_bar' => ['enabled' => false],
        'sidebar' => ['enabled' => false],
        'breadcrumb' => ['enabled' => false],
        'title' => ['enabled' => false],
        'titleRow' => ['enabled' => false],
        'context_menu' => ['enabled' => false],
    ]"
>
    @php
        $user = auth()->user();

        $userName = data_get($user, 'name') ?: data_get($user, 'first_name');

        // Resolve quick-access module cards. Routes are checked at render time
        // so the page never blows up when a module is not registered in the
        // consuming application.
        $modules = [
            [
                'title'       => 'Admin Panel',
                'description' => 'Manage users, roles, permissions and access control across the platform.',
                'icon'        => 'fa-shield-halved',
                'route'       => 'admin.dashboard',
                'color'       => 'primary',
            ],
            [
                'title'       => 'Organization',
                'description' => 'Explore company structure, departments, locations and people.',
                'icon'        => 'fa-sitemap',
                'route'       => 'organization.dashboard',
                'color'       => 'success',
            ],
            [
                'title'       => 'System Settings',
                'description' => 'Configure general settings, integrations and system-level preferences.',
                'icon'        => 'fa-gear',
                'route'       => 'system.dashboard',
                'color'       => 'info',
            ],
        ];

        // Stats are defensive: consuming apps may or may not ship the related
        // models, so each count is guarded and only rendered when available.
        $stats = [
            [
                'label' => 'Users',
                'icon'  => 'fa-users',
                'color' => 'primary',
                'value' => class_exists(\App\Models\User::class) ? rescue(fn () => \App\Models\User::count(), null, false) : null,
            ],
            [
                'label' => 'Companies',
                'icon'  => 'fa-building',
                'color' => 'success',
                'value' => class_exists(\App\Models\Company::class) ? rescue(fn () => \App\Models\Company::count(), null, false) : null,
            ],
            [
                'label' => 'Roles',
                'icon'  => 'fa-user-shield',
                'color' => 'warning',
                'value' => class_exists(\Spatie\Permission\Models\Role::class) ? rescue(fn () => \Spatie\Permission\Models\Role::count(), null, false) : null,
            ],
            [
                'label' => 'System Settings',
                'icon'  => 'fa-sliders',
                'color' => 'info',
                'value' => class_exists(\QuickerFaster\UILibrary\Models\SystemSetting::class) ? rescue(fn () => \QuickerFaster\UILibrary\Models\SystemSetting::count(), null, false) : null,
            ],
        ];

        $visibleStats = array_values(array_filter($stats, fn ($stat) => $stat['value'] !== null));

        $gettingStarted = [
            [
                'title'       => 'Complete your profile',
                'description' => 'Add a profile picture and keep your personal details up to date.',
                'icon'        => 'fa-user-pen',
                'route'       => 'profile',
                'cta'         => 'Update Profile',
            ],
            [
                'title'       => 'Explore the dashboard',
                'description' => 'Get familiar with the modules, navigation and quick actions.',
                'icon'        => 'fa-compass',
                'route'       => 'home',
                'cta'         => 'View Dashboard',
            ],
            [
                'title'       => 'Run the setup wizard',
                'description' => 'Follow the guided steps to configure your workspace.',
                'icon'        => 'fa-wand-magic-sparkles',
                'route'       => 'setup.wizard',
                'cta'         => 'Start Setup',
            ],
        ];
    @endphp

    <div class="container-fluid py-4">
        {{-- Welcome hero --}}
        <div class="row">
            <div class="col-12">
                <div class="card mb-4 border-0 shadow-lg overflow-hidden rounded-4">
                    <div class="card-body bg-gradient-primary p-4 p-md-5 position-relative">
                        <div class="row align-items-center">
                            <div class="col-12 col-lg-8">
                                <span class="badge bg-white text-primary text-uppercase text-xs mb-3 px-3 py-2">
                                    {{ config('app.name', 'QuickerFaster') }}
                                </span>
                                <h3 class="text-white fw-bolder mb-2">
                                    {{ __('Welcome back') }}{{ $userName ? ', ' . $userName : '' }}!
                                </h3>
                                <p class="text-white opacity-8 mb-4 mb-lg-0 pe-lg-5">
                                    {{ __('Here is a quick overview of your workspace. Jump into a module below or pick up where you left off.') }}
                                </p>
                            </div>
                            <div class="col-12 col-lg-4 d-none d-lg-flex justify-content-end">
                                <i class="fa-solid fa-house-circle-check text-white opacity-6" style="font-size: 6rem;"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Stats summary --}}
        @if (!empty($visibleStats))
            <div class="row g-3 mb-4">
                @foreach ($visibleStats as $stat)
                    <div class="col-6 col-lg-3">
                        <div class="card h-100 border-0 shadow-sm rounded-4">
                            <div class="card-body p-3 p-lg-4">
                                <div class="d-flex align-items-center">
                                    <div class="icon-shape icon-md rounded-3 bg-gradient-{{ $stat['color'] }} text-white me-3 d-flex align-items-center justify-content-center" style="width: 52px; height: 52px; flex-shrink: 0;">
                                        <i class="fa-solid {{ $stat['icon'] }}"></i>
                                    </div>
                                    <div class="min-width-0">
                                        <p class="text-sm text-secondary mb-0 text-uppercase fw-semibold">{{ $stat['label'] }}</p>
                                        <h5 class="fw-bolder mb-0">{{ number_format($stat['value']) }}</h5>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

        {{-- Module quick actions --}}
        <div class="row g-3 mb-4">
            @foreach ($modules as $module)
                @php
                    $href = Route::has($module['route']) ? route($module['route']) : '#home';
                @endphp
                <div class="col-12 col-md-6 col-xl-4">
                    <a href="{{ $href }}" class="text-decoration-none">
                        <div class="card h-100 border-0 shadow-sm rounded-4 transition-hover" style="transition: transform .2s ease, box-shadow .2s ease;">
                            <div class="card-body p-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="icon-shape icon-lg rounded-3 bg-gradient-{{ $module['color'] }} text-white d-flex align-items-center justify-content-center me-3" style="width: 56px; height: 56px; flex-shrink: 0;">
                                        <i class="fa-solid {{ $module['icon'] }} fs-5"></i>
                                    </div>
                                    <h6 class="fw-bolder mb-0 text-body">{{ $module['title'] }}</h6>
                                </div>
                                <p class="text-sm text-secondary mb-3">{{ $module['description'] }}</p>
                                <span class="text-{{ $module['color'] }} text-sm fw-bold">
                                    {{ __('Open') }} <i class="fa-solid fa-arrow-right-long ms-1"></i>
                                </span>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>

        {{-- Getting started --}}
        <div class="row">
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-header bg-white border-0 px-4 pt-4 pb-0">
                        <h5 class="fw-bolder mb-1">{{ __('Getting Started') }}</h5>
                        <p class="text-sm text-secondary mb-0">{{ __('A few steps to help you make the most of your workspace.') }}</p>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach ($gettingStarted as $step)
                                @php
                                    $href = Route::has($step['route']) ? route($step['route']) : '#home';
                                @endphp
                                <div class="col-12 col-md-6 col-xl-4">
                                    <a href="{{ $href }}" class="text-decoration-none">
                                        <div class="d-flex align-items-start rounded-3 p-3 h-100 bg-light bg-gradient" style="transition: background .2s ease;">
                                            <i class="fa-solid {{ $step['icon'] }} text-primary fs-5 me-3 mt-1"></i>
                                            <div>
                                                <h6 class="fw-bold text-body mb-1">{{ $step['title'] }}</h6>
                                                <p class="text-sm text-secondary mb-2">{{ $step['description'] }}</p>
                                                <span class="badge bg-white text-primary shadow-sm px-3 py-2 text-xs fw-semibold">
                                                    {{ $step['cta'] }} <i class="fa-solid fa-arrow-right ms-1"></i>
                                                </span>
                                            </div>
                                        </div>
                                    </a>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
        .transition-hover:hover {
            transform: translateY(-4px);
            box-shadow: 0 1rem 2rem rgba(0, 0, 0, .08) !important;
        }
        .opacity-6 {
            opacity: .6;
        }
        .opacity-8 {
            opacity: .8;
        }
    </style>
</x-qf::navigation-layout>
