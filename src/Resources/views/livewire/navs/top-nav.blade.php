<nav id = "main-nav" class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top" style="z-index: 1030;">




    {{-- Toggler – now inside container, with correct Bootstrap 5 attributes --}}
    <button class="navbar-toggler border-primary bg-light" type="button" data-bs-toggle="collapse"
        data-bs-target="#topNavCollapse" aria-controls="topNavCollapse" aria-expanded="false"
        aria-label="Toggle navigation">
        <i class="fa-solid fa-bars text-dark"></i>
    </button>

    {{-- Brand / Module Switcher --}}
    @php
        $currentModule = strtolower($this->moduleName); // request()->segment(1);
        $moduleNames = ['hr' => 'QuickHR'];
        $currentModuleName = $moduleNames[$currentModule] ?? 'QuickHR';
        $moduleIcons = ['hr' => 'fas fa-users'];
        $currentModuleIcon = $moduleIcons[$currentModule] ?? 'fas fa-th-large';
    @endphp


    <div class="dropdown">
        <a class="navbar-brand d-flex align-items-center dropdown-toggle module-switcher {{ 'dashboard' === $activeContext ? 'active fw-bold text-primary' : '' }}"
            href="#" data-bs-toggle="dropdown" data-bs-auto-close="true">
            <i class="{{ $currentModuleIcon }} me-2"></i>
            <span class="fw-bold">{{ $currentModuleName }}</span>
        </a>
        <ul class="dropdown-menu shadow border-0 p-3">
            <li>
                <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">Active Modules</h6>
            </li>
            <li>
                <a class="dropdown-item border-radius-md d-flex align-items-center {{ $currentModule === 'hr' ? 'bg-light text-primary' : '' }}"
                    href="{{ url('/hr/dashboard') }}">
                    <i class="fas fa-users me-2"></i>
                    <span>HR Module</span>
                    @if ($currentModule === 'hr')
                        <i class="fas fa-check ms-auto text-primary"></i>
                    @endif
                </a>
            </li>
            {{-- Strategic "Coming Soon" section --}}
            <li class="mt-3">
                <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">Next Up</h6>
            </li>
            <li>
                <span class="dropdown-item d-flex align-items-center text-muted opacity-5" style="cursor: not-allowed;">
                    <i class="fas fa-calculator me-2"></i> Accounts (Coming Soon)
                </span>
            </li>
        </ul>

    </div>




    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="topNavCollapse" wire:key="navbar-collapse">
            {{-- Left: nav items (desktop) --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-none d-md-flex">

                {{-- Constant Left shared items [Admin/HR and Dashboard] --}}

                @if (
                    $currentModule == 'hr' &&
                        auth()->user() &&
                        auth()->user()->hasAnyRole(\App\Modules\Admin\Services\AuthorizationService::ADMIN_ROLES))
                    <li class="nav-item border-end-lg border-start-lg me" wire:key="nav-item-admin-dashboard">
                        <a href="/admin/dashboard" role="link"
                            class="btn btn-sm px-3 py-1 mx-3 mb-0 mt-1 rounded-pill btn-outline-primary">
                            <i class="fas fa-cog me-1"></i> Admin Panel
                        </a>
                    </li>
                @endif
                @if ($currentModule == 'admin')
                    <li class="nav-item border-end-lg border-start-lg me" wire:key="nav-item-hr-dashboard">
                        <a href="/hr/dashboard" role="link"
                            class="btn btn-sm px-3 py-1 mx-3 mb-0 mt-1 rounded-pill btn-outline-primary">
                            <i class="fas fa-reply me-1"></i> Back to HR
                        </a>
                    </li>
                @endif

                <li class="nav-item " wire:key="nav-item-Policies">
                    <a href="/{{ $currentModule }}/dashboard"
                        class="nav-link {{ 'dashboard' === $activeContext ? 'active fw-bold text-primary' : '' }}">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        <span>Dashboard</span>
                    </a>
                </li>


                @php
                    use Illuminate\Support\Str;
                    $visibleDesktop = collect($items)->take($maxDesktop);
                @endphp


                {{-- Left shared items --}}
                @foreach ($leftShared as $item)
                    @include('qf::livewire.navs.partials.top-nav-item', ['item' => $item])
                @endforeach

                {{-- Main context groups --}}
                {{-- Wrap the loop in a span or div with the ID --}}
                <div id="main-features-nav" class="d-none d-md-flex align-items-center">
                    @foreach ($visibleDesktop as $key => $item)
                        @include('qf::livewire.navs.partials.top-nav-item', [
                            'item' => $item,
                            'key' => $key,
                        ])
                    @endforeach
                </div>




                @if ($this->overflowDesktop->isNotEmpty())
                    @php
                        // Check if any item inside the overflow is the active one
                        $isOverflowActive = $this->overflowDesktop->has($activeContext);
                    @endphp

                    <li class="nav-item dropdown" wire:key="overflow-dropdown">
                        <a class="nav-link dropdown-toggle {{ $isOverflowActive ? 'active fw-bold text-primary' : '' }}"
                            href="#" role="button" data-bs-toggle="dropdown">
                            {{ __('qf::nav.more') }}
                        </a>
                        <ul class="dropdown-menu">
                            @foreach ($this->overflowDesktop as $key => $item)
                                @php
                                    $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
                                    $url = $isNamedRoute
                                        ? route($item['route'])
                                        : url($item['url'] ?? Str::kebab($key));
                                @endphp
                                <li wire:key="overflow-item-{{ $key }}">
                                    <a href="{{ $url }}"
                                        class="dropdown-item d-flex align-items-center {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
                                        @if (!empty($item['icon']))
                                            <i class="{{ $item['icon'] }} me-2" style="width: 20px;"></i>
                                        @endif
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </li>
                @endif

            </ul>

            {{-- Right side: mobile scroll, locale switcher, profile --}}
            <div class="d-flex align-items-center">
                <div class="d-md-none mobile-scroll-wrapper me-2">
                    <div class="d-flex overflow-auto" style="gap:.5rem;">
                        @php
                            $visibleMobile = collect($items)->take($maxMobile);
                        @endphp

                        @foreach ($visibleMobile as $key => $item)
                            @php
                                $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
                                $url = $isNamedRoute
                                    ? route($item['route'])
                                    : url(strtolower($this->moduleName) . '/' . ($item['url'] ?? Str::kebab($key)));
                            @endphp
                            <a href="{{ $url }}"
                                class="btn btn-light btn-sm {{ $key === $activeContext ? 'active' : '' }}"
                                wire:key="mobile-item-{{ $key }}">
                                <i class="fa {{ $item['icon'] }} me-1"></i>
                                <span>{{ $item['label'] }}</span>
                            </a>
                        @endforeach

                        @if ($this->overflowMobile->isNotEmpty())
                            <div class="btn-group position-static" wire:key="mobile-overflow">
                                <button class="btn btn-light btn-sm dropdown-toggle" data-bs-toggle="dropdown"></button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    @foreach ($this->overflowMobile as $key => $item)
                                        @php
                                            $isNamedRoute =
                                                isset($item['route']) && !Str::contains($item['route'], '/');
                                            $url = $isNamedRoute
                                                ? route($item['route'])
                                                : url(
                                                    strtolower($this->moduleName) .
                                                        '/' .
                                                        ($item['url'] ?? Str::kebab($key)),
                                                );
                                        @endphp
                                        <li wire:key="mobile-overflow-item-{{ $key }}">
                                            <a href="{{ $url }}" class="dropdown-item">
                                                {{ $item['label'] }}
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>

                {{-- Company Switcher --}}
                @if ($companies && $companies->isNotEmpty())
                    @php
                        $isAllCompanies = $currentCompanyId === 0;
                    @endphp
                    <div class="dropdown me-2" id="company-switcher" wire:key="company-switcher">
                        <button class="btn btn-sm {{ $isAllCompanies ? 'btn-outline-info' : 'btn-outline-primary' }} dropdown-toggle px-3 py-1 my-0 fw-medium" type="button"
                            data-bs-toggle="dropdown" aria-label="Switch Company">
                            <i class="fas {{ $isAllCompanies ? 'fa-globe' : 'fa-building' }} me-1"></i>
                            <span class="d-none d-md-inline">{{ \Illuminate\Support\Str::limit($currentCompanyName, 12) }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow border-0" style="max-height: 300px; overflow-y: auto;">
                            <li>
                                <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">
                                    Switch Company
                                </h6>
                            </li>
                            {{-- All Companies option --}}
                            <li wire:key="company-all">
                                <a class="dropdown-item border-radius-md d-flex align-items-center {{ $isAllCompanies ? 'bg-info-light text-info fw-bold' : '' }}"
                                    href="#"
                                    wire:click.prevent="switchCompany(0)">
                                    <i class="fas fa-globe me-2 {{ $isAllCompanies ? 'text-info' : 'opacity-6' }}"></i>
                                    <span>All Companies</span>
                                    @if ($isAllCompanies)
                                        <i class="fas fa-check ms-auto text-info"></i>
                                    @endif
                                </a>
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            @foreach ($companies as $company)
                                <li wire:key="company-{{ $company->id }}">
                                    <a class="dropdown-item border-radius-md d-flex align-items-center {{ $currentCompanyId === $company->id ? 'bg-light text-primary fw-bold' : '' }}"
                                        href="#"
                                        wire:click.prevent="switchCompany({{ $company->id }})">
                                        <i class="fas fa-building me-2 {{ $currentCompanyId === $company->id ? 'text-primary' : 'opacity-6' }}"></i>
                                        <span>{{ $company->name }}</span>
                                        @if ($currentCompanyId === $company->id)
                                            <i class="fas fa-check ms-auto text-primary"></i>
                                        @endif
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                {{-- Background Jobs --}}
                <a href="#" class=" px-2 py-1 my-0 me-1 border-start-lg border-end-lg px-3" 
                    wire:click.prevent="openBackgroundJobsDrawer" title="Background Jobs">
                    <i class="fas fa-history"></i>
                </a>

                                

                {{-- Locale switcher --}}
                <div class="dropdown me-1" id="language-switcher">
                    <a href="#" class=" dropdown-toggle px-2 py-1 my-0"
                        data-bs-toggle="dropdown" aria-label="Select Locale" title="{{ strtoupper(app()->getLocale()) }}">
                        <i class="fas fa-globe"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                        <li><a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}"
                                href="#">English</a></li>
                        <li><a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}"
                                href="#">Français</a></li>
                        <li><a class="dropdown-item {{ app()->getLocale() === 'es' ? 'active' : '' }}"
                                href="#">Español</a></li>
                    </ul>
                </div>


                {{-- Profile / logout --}}
                <div class="dropdown ms-1 ps-2" wire:ignore id="user-profile-menu">
                    <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#"
                        data-bs-toggle="dropdown" title="{{ auth()->user()?->name ?: 'Account' }}">
                        @if (auth()->user()?->avatar_url)
                            <img src="{{ auth()->user()->avatar_url }}" alt="Profile"
                                class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                        @else
                            <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center"
                                style="width: 32px; height: 32px;">
                                <i class="fas fa-user"></i>
                            </div>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2">
                        <li>
                            <span class="dropdown-item-text text-dark fw-medium border-bottom mb-1 pb-2">
                                {{ ucwords(auth()->user()?->name) ?: 'Account' }}
                            </span>
                        </li>
                        <li>
                            @auth
                                <a class="dropdown-item border-radius-md mb-1" href="/hr/my-profile">
                                    <i class="fas fa-user-cog me-2 opacity-6 text-sm"></i> My Profile
                                </a>
                            @endauth
                        </li>

                        <li>
                            @auth
                                <a class="dropdown-item border-radius-md mb-1" href="/hr/my-preferences" wire:navigate>
                                    <i class="fas fa-cog me-2 opacity-6 text-sm"></i> My Preferences
                                </a>
                            @endauth
                        </li>

                        <li>
                            @auth
                                <a class="dropdown-item border-radius-md mb-1" href="/hr/my-account">
                                    <i class="fas fa-user-edit me-2 opacity-6 text-sm"></i> Edit My User Account
                                </a>
                            @endauth
                        </li>

                        {{-- Responsive "Take Tour" Link: Hidden on mobile, visible on desktop --}}
                        <li class="d-none d-md-block">
                            <a class="dropdown-item border-radius-md mb-1" href="{{ route('tour.restart') }}">
                                <i class="fas fa-play-circle me-2 opacity-6 text-sm text-primary"></i> Take the Tour
                            </a>
                        </li>

                        <li>
                            <hr class="dropdown-divider">
                        </li>

                        <li>
                            <button class="dropdown-item text-danger border-radius-md" type="button"
                                wire:click="logout">
                                <i class="fas fa-sign-out-alt me-1 text-sm"></i> Logout
                            </button>
                        </li>
                    </ul>


                </div>



            </div>

        </div>
    </div>



    <ul class="navbar-nav ms-auto mb-2 mb-lg-0 d-none d-md-flex">
        @foreach ($rightShared as $item)
            @include('qf::livewire.navs.partials.top-nav-item', ['item' => $item])
        @endforeach
    </ul>
</nav>
