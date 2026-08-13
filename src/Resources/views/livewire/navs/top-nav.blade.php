<nav id = "main-nav" class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top" style="z-index: 1030;">




    {{-- Toggler – now inside container, with correct Bootstrap 5 attributes --}}
    <button class="navbar-toggler border-primary bg-light" type="button" data-bs-toggle="collapse"
        data-bs-target="#topNavCollapse" aria-controls="topNavCollapse" aria-expanded="false"
        aria-label="Toggle navigation">
        <i class="fa-solid fa-bars text-dark"></i>
    </button>

    {{-- Brand / Module Switcher --}}
    @php
        $currentModule = strtolower($this->moduleName);
    @endphp

    {{-- Module Switcher --}}
    @if ($moduleSwitcherEnabled && !empty($this->modules))
    <div class="dropdown me-2" id="module-switcher" wire:key="module-switcher">
        <button class="btn btn-sm btn-outline-primary dropdown-toggle px-3 py-1 my-0 fw-medium" type="button"
            data-bs-toggle="dropdown" aria-label="Switch Module" aria-expanded="false">
            <i class="fas fa-th-large me-1"></i>
            <span class="d-none d-md-inline">{{ $this->currentModuleLabel }}</span>
        </button>
        <ul class="dropdown-menu shadow border-0" style="max-height: 300px; overflow-y: auto;">
            <li>
                <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">
                    Switch Module
                </h6>
            </li>
            @foreach ($this->modules as $module)
                <li wire:key="module-{{ $module['key'] }}">
                    <a class="dropdown-item border-radius-md d-flex align-items-center {{ $module['key'] === $this->activeModuleKey ? 'bg-light text-primary fw-bold' : '' }}"
                        href="#"
                        wire:click.prevent="switchModule('{{ $module['key'] }}')">
                        <i class="{{ $module['icon'] ?? 'fas fa-cube' }} me-2 {{ $module['key'] === $this->activeModuleKey ? 'text-primary' : 'opacity-6' }}"></i>
                        <span>{{ $module['label'] }}</span>
                        @if ($module['key'] === $this->activeModuleKey)
                            <i class="fas fa-check ms-auto text-primary"></i>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
    @endif




    <div class="container-fluid">
        <div class="collapse navbar-collapse" id="topNavCollapse" wire:key="navbar-collapse">
            {{-- Left: nav items (desktop) --}}
            <ul class="navbar-nav me-auto mb-2 mb-lg-0 d-none d-md-flex">

                {{-- Configurable cross-module navigation --}}
                @if (!empty($crossModuleLinks['admin']))
                    <li class="nav-item border-end-lg border-start-lg me" wire:key="nav-item-admin-dashboard">
                        <a href="{{ $crossModuleLinks['admin']['url'] ?? '/admin/dashboard' }}" role="link"
                            class="btn btn-sm px-3 py-1 mx-3 mb-0 mt-1 rounded-pill btn-outline-primary">
                            <i class="{{ $crossModuleLinks['admin']['icon'] ?? 'fas fa-cog' }} me-1"></i>
                            {{ $crossModuleLinks['admin']['label'] ?? 'Admin Panel' }}
                        </a>
                    </li>
                @endif

                @if ($currentModule === 'admin' && !empty($crossModuleLinks['back']))
                    <li class="nav-item border-end-lg border-start-lg me" wire:key="nav-item-back-dashboard">
                        <a href="{{ $crossModuleLinks['back']['url'] ?? '/' }}" role="link"
                            class="btn btn-sm px-3 py-1 mx-3 mb-0 mt-1 rounded-pill btn-outline-primary">
                            <i class="{{ $crossModuleLinks['back']['icon'] ?? 'fas fa-reply' }} me-1"></i>
                            {{ $crossModuleLinks['back']['label'] ?? 'Back' }}
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
                @endphp


                {{-- Left shared items --}}
                @foreach ($leftShared as $item)
                    @include('qf::livewire.navs.partials.top-nav-item', ['item' => $item])
                @endforeach

                {{-- Main context groups — hidden when Phase 2 cross-context dropdowns are active --}}
                @if (!$hideTopnavContexts)
                    {{-- Wrap the loop in a span or div with the ID --}}
                    <div id="main-features-nav" class="d-none d-md-flex align-items-center">
                        @foreach ($this->visibleDesktop as $key => $item)
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
                                                <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;"></i>
                                            @endif
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </li>
                    @endif
                @endif

            </ul>

            {{-- Right side: mobile scroll, locale switcher, profile --}}
            <div class="d-flex align-items-center">
                @if (!$hideTopnavContexts)
                <div class="d-md-none mobile-scroll-wrapper me-2">
                    <div class="d-flex overflow-auto" style="gap:.5rem;">
                        @foreach ($this->visibleMobile as $key => $item)
                            @php
                                $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
                                $url = $isNamedRoute
                                    ? route($item['route'])
                                    : url($item['url'] ?? Str::kebab($key));
                            @endphp
                            <a href="{{ $url }}"
                                class="btn btn-light btn-sm {{ $key === $activeContext ? 'active' : '' }}"
                                wire:key="mobile-item-{{ $key }}">
                                @if (!empty($item['icon']))
                                    <i class="fa {{ $item['icon'] }} me-1"></i>
                                @endif
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
                                                : url($item['url'] ?? Str::kebab($key));
                                        @endphp
                                        <li wire:key="mobile-overflow-item-{{ $key }}">
                                            <a href="{{ $url }}" class="dropdown-item d-flex align-items-center">
                                                @if (!empty($item['icon']))
                                                    <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;"></i>
                                                @endif
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                    </div>
                </div>
                @endif

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

                {{-- Notifications --}}
                @if ($notificationsEnabled)
                <a href="#" class="px-2 py-1 my-0 me-1" wire:click.prevent="openNotificationsDrawer" title="{{ $notificationsTitle }}">
                    <i class="{{ $notificationsIcon }}"></i>
                </a>
                @endif

                {{-- Background Jobs --}}
                @if ($backgroundJobsEnabled)
                <a href="#" class=" px-2 py-1 my-0 me-1 border-start-lg border-end-lg px-3"
                    wire:click.prevent="openBackgroundJobsDrawer" title="{{ $backgroundJobsTitle }}">
                    <i class="{{ $backgroundJobsIcon }}"></i>
                </a>
                @endif

                                

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
                        @php
                            $userMenu = config('ui-library.user_menu');
                            $userMenuEnabled = $userMenu['enabled'] ?? true;
                            $userMenuLinks = $userMenu['links'] ?? [];
                            $visibleLinks = array_filter($userMenuLinks, function ($link) {
                                return !empty($link['url']) || !empty($link['route']);
                            });
                        @endphp

                        @if ($userMenuEnabled)
                            @auth
                                @foreach ($visibleLinks as $link)
                                    @php
                                        $linkUrl = !empty($link['url'])
                                            ? url($link['url'])
                                            : (!empty($link['route']) ? route($link['route']) : '#');
                                    @endphp
                                    <li>
                                        <a class="dropdown-item border-radius-md mb-1" href="{{ $linkUrl }}">
                                            <i class="{{ $link['icon'] ?? 'fas fa-link' }} me-2 opacity-6 text-sm"></i>
                                            {{ $link['label'] ?? 'Link' }}
                                        </a>
                                    </li>
                                @endforeach
                            @endauth
                        @endif

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
