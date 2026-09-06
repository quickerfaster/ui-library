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

                @if (!isset($this->items['dashboard']))
                <li class="nav-item " wire:key="nav-item-Policies">
                    <a href="/{{ $currentModule }}/dashboard"
                        class="nav-link {{ 'dashboard' === $activeContext ? 'active fw-bold text-primary' : '' }}">
                        <i class="fas fa-tachometer-alt me-1"></i>
                        <span>Dashboard</span>
                    </a>
                </li>
                @endif


                @php
                    use Illuminate\Support\Str;
                    use QuickerFaster\UILibrary\Services\Notifications\NotificationTypeRegistry;
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

                                        // Permission check — same logic as top-nav-item.blade.php
                                        $hasPermission = true;
                                        if (!empty($item['permission'])) {
                                            $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
                                            // FALLBACK: if permission check fails but item has roles, try role-based access
                                            if (!$hasPermission && !empty($item['roles'])) {
                                                $roles = $item['roles'];
                                                $isWildcard = ($roles === '*' || $roles === ['*']);
                                                $hasPermission = $isWildcard
                                                    || \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::isBypassAllowed(auth()->user())
                                                    || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                            }
                                        } elseif (!empty($item['roles'])) {
                                            $roles = $item['roles'];
                                            $isWildcard = ($roles === '*' || $roles === ['*']);
                                            $hasPermission = $isWildcard || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                        } elseif (!empty($url)) {
                                            $segments = explode('/', $url);
                                            $viewName = last($segments);
                                            $viewName = str_replace('dashboard-', '', $viewName);
                                            $permission = 'view_' . \Illuminate\Support\Str::singular(str_replace('-', '_', $viewName));
                                            $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission);
                                        }
                                    @endphp
                                    @if ($hasPermission)
                                    <li wire:key="overflow-item-{{ $key }}">
                                        <a href="{{ $url }}"
                                            class="dropdown-item d-flex align-items-center {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
                                            @if (!empty($item['icon']))
                                                <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;"></i>
                                            @endif
                                            <span>{{ $item['label'] }}</span>
                                        </a>
                                    </li>
                                    @endif
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

                                // Permission check — same logic as top-nav-item.blade.php
                                $hasPermission = true;
                                if (!empty($item['permission'])) {
                                    $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
                                    // FALLBACK: if permission check fails but item has roles, try role-based access
                                    if (!$hasPermission && !empty($item['roles'])) {
                                        $roles = $item['roles'];
                                        $isWildcard = ($roles === '*' || $roles === ['*']);
                                        $hasPermission = $isWildcard
                                            || \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::isBypassAllowed(auth()->user())
                                            || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                    }
                                } elseif (!empty($item['roles'])) {
                                    $roles = $item['roles'];
                                    $isWildcard = ($roles === '*' || $roles === ['*']);
                                    $hasPermission = $isWildcard || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                } elseif (!empty($url)) {
                                    $segments = explode('/', $url);
                                    $viewName = last($segments);
                                    $viewName = str_replace('dashboard-', '', $viewName);
                                    $permission = 'view_' . \Illuminate\Support\Str::singular(str_replace('-', '_', $viewName));
                                    $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission);
                                }
                            @endphp
                            @if ($hasPermission)
                            <a href="{{ $url }}"
                                class="btn btn-light btn-sm {{ $key === $activeContext ? 'active' : '' }}"
                                wire:key="mobile-item-{{ $key }}">
                                @if (!empty($item['icon']))
                                    <i class="fa {{ $item['icon'] }} me-1"></i>
                                @endif
                                <span>{{ $item['label'] }}</span>
                            </a>
                            @endif
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

                                            // Permission check — same logic as top-nav-item.blade.php
                                            $hasPermission = true;
                                            if (!empty($item['permission'])) {
                                                $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
                                                // FALLBACK: if permission check fails but item has roles, try role-based access
                                                if (!$hasPermission && !empty($item['roles'])) {
                                                    $roles = $item['roles'];
                                                    $isWildcard = ($roles === '*' || $roles === ['*']);
                                                    $hasPermission = $isWildcard
                                                        || \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::isBypassAllowed(auth()->user())
                                                        || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                                }
                                            } elseif (!empty($item['roles'])) {
                                                $roles = $item['roles'];
                                                $isWildcard = ($roles === '*' || $roles === ['*']);
                                                $hasPermission = $isWildcard || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
                                            } elseif (!empty($url)) {
                                                $segments = explode('/', $url);
                                                $viewName = last($segments);
                                                $viewName = str_replace('dashboard-', '', $viewName);
                                                $permission = 'view_' . \Illuminate\Support\Str::singular(str_replace('-', '_', $viewName));
                                                $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission);
                                            }
                                        @endphp
                                        @if ($hasPermission)
                                        <li wire:key="mobile-overflow-item-{{ $key }}">
                                            <a href="{{ $url }}" class="dropdown-item d-flex align-items-center">
                                                @if (!empty($item['icon']))
                                                    <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;"></i>
                                                @endif
                                                <span>{{ $item['label'] }}</span>
                                            </a>
                                        </li>
                                        @endif
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
                <a href="#" class="px-2 py-1 my-0 me-1 position-relative" wire:click.prevent="openNotificationsDrawer" title="{{ $notificationsTitle }}">
                    <i class="{{ $notificationsIcon }}"></i>
                    @if ($this->unreadCount > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                        {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                    </span>
                    @endif
                </a>
                @endif

                {{-- Background Jobs --}}
                @if ($backgroundJobsEnabled)
                <a href="#" class=" px-2 py-1 my-0 me-1 border-start-lg border-end-lg px-3"
                    wire:click.prevent="openBackgroundJobsDrawer" title="{{ $backgroundJobsTitle }}">
                    <i class="{{ $backgroundJobsIcon }}"></i>
                </a>
                @endif

                {{-- Quick Actions (Command Palette) --}}
                @if ($quickActionsEnabled)
                <a href="#" class="px-2 py-1 my-0 me-1"
                    wire:click.prevent="openQuickActions" title="{{ $quickActionsTitle }}">
                    <i class="{{ $quickActionsIcon }}"></i>
                </a>
                @endif

                {{-- Quick Actions ⚡ Button (Top Ranked Actions Dropdown) --}}
                @if ($quickActionsButtonEnabled)
                <div class="dropdown me-1" id="quick-actions-dropdown" wire:key="quick-actions-dropdown">
                    <a href="#" class="px-2 py-1 my-0 position-relative dropdown-toggle {{ $showQuickActionsPulse ? 'qa-pulse' : '' }}"
                        data-bs-toggle="dropdown" aria-label="Quick Actions" aria-expanded="false"
                        title="{{ $quickActionsButtonTitle }}">
                        <i class="{{ $quickActionsButtonIcon }}"></i>
                        @if ($showQuickActionsPulse)
                        <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle" style="width: 8px; height: 8px;">
                            <span class="visually-hidden">New</span>
                        </span>
                        @endif
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2" style="min-width: 300px;">
                        <li>
                            <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">
                                <i class="{{ $quickActionsButtonIcon }} me-1"></i>{{ $quickActionsButtonTitle }}
                            </h6>
                        </li>

                        @forelse ($quickActions as $action)
                            @php
                                $actionId = $action['id'] ?? $action['key'] ?? '';
                                $isFav = in_array($actionId, $quickActionFavorites, true);
                            @endphp
                            <li wire:key="quick-action-{{ $actionId }}">
                                <div class="dropdown-item border-radius-md d-flex align-items-center pe-1">
                                    <a href="#"
                                        class="d-flex align-items-center text-decoration-none flex-grow-1 min-width-0"
                                        wire:click.prevent="executeQuickAction('{{ $actionId }}')">
                                        <span class="icon-shape icon-xs rounded-2 bg-gradient-warning text-white d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; flex-shrink: 0;">
                                            <i class="fa-solid {{ $action['icon'] ?? 'fas fa-bolt' }}"></i>
                                        </span>
                                        <span class="min-width-0">
                                            <span class="d-block text-sm fw-medium text-dark text-truncate">{{ $action['label'] }}</span>
                                            @if (!empty($action['description']))
                                                <span class="d-block text-xs text-muted text-truncate">{{ $action['description'] }}</span>
                                            @endif
                                        </span>
                                    </a>
                                    {{-- Star Toggle --}}
                                    <button type="button"
                                        class="btn btn-sm border-0 p-1 ms-1 {{ $isFav ? 'text-warning' : 'text-muted' }}"
                                        wire:click.stop="toggleQuickActionFavorite('{{ $actionId }}')"
                                        title="{{ $isFav ? 'Unpin' : 'Pin' }}"
                                        style="flex-shrink: 0;">
                                        <i class="{{ $isFav ? 'fas' : 'far' }} fa-star fa-sm"></i>
                                    </button>
                                </div>
                            </li>
                        @empty
                            <li>
                                <span class="dropdown-item-text text-muted text-sm">
                                    No actions available yet.
                                </span>
                            </li>
                        @endforelse

                        <li><hr class="dropdown-divider my-1"></li>
                        <li>
                            <a href="#" class="dropdown-item text-sm text-primary fw-semibold"
                                wire:click.prevent="openQuickActions">
                                More actions… <i class="fas fa-arrow-right ms-1"></i>
                            </a>
                        </li>
                    </ul>
                </div>
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

    {{-- Notifications Drawer (Bootstrap Offcanvas) --}}
    @if ($notificationsEnabled && $showNotificationsDrawer)
    <div class="offcanvas offcanvas-end show" tabindex="-1" id="notificationsDrawer"
         style="visibility: visible; width: 380px; z-index: 1045;"
         wire:key="notifications-drawer">
        <div class="offcanvas-header border-bottom">
            <h5 class="offcanvas-title fw-bold">
                <i class="{{ $notificationsIcon }} me-2"></i>{{ $notificationsTitle }}
                @if ($this->unreadCount > 0)
                <span class="badge rounded-pill bg-danger ms-2">{{ $this->unreadCount }}</span>
                @endif
            </h5>
            <button type="button" class="btn-close" wire:click="closeNotificationsDrawer" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body p-0">
            @php $unreadNotifications = $this->unreadNotifications; @endphp
            @if ($unreadNotifications->isEmpty())
                <div class="d-flex flex-column align-items-center justify-content-center py-5 text-muted">
                    <i class="fas fa-bell-slash fa-3x mb-3 opacity-50"></i>
                    <p class="mb-0">No new notifications</p>
                </div>
            @else
                <div class="list-group list-group-flush" style="max-height: calc(100vh - 120px); overflow-y: auto;">
                    @foreach ($unreadNotifications as $notification)
                        <div class="list-group-item list-group-item-action border-bottom py-3 px-3"
                             wire:key="notification-{{ $notification->id }}"
                             wire:click="navigateToNotification({{ $notification->id }})">
                            <div class="d-flex w-100 justify-content-between align-items-start">
                                <div class="me-2">
                                    <i class="{{ NotificationTypeRegistry::getIcon($notification->type) }} {{ NotificationTypeRegistry::getColor($notification->type) }} fs-5"></i>
                                </div>
                                <div class="flex-grow-1 me-2">
                                    <h6 class="mb-1 fw-semibold text-sm">{{ $notification->subject }}</h6>
                                    <p class="mb-1 text-xs text-muted">{{ \Illuminate\Support\Str::limit($notification->body, 100) }}</p>
                                    <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>

                                    {{-- Inline action buttons --}}
                                    @if (!empty($notification->actions))
                                        <div class="mt-2 d-flex gap-1 flex-wrap">
                                            @foreach ($notification->actions as $action)
                                                @php
                                                    $style = $action['style'] ?? 'primary';
                                                    $btnClass = match ($style) {
                                                        'success' => 'btn-success',
                                                        'danger' => 'btn-danger',
                                                        'warning' => 'btn-warning',
                                                        'info' => 'btn-info',
                                                        'secondary' => 'btn-secondary',
                                                        'dark' => 'btn-dark',
                                                        'light' => 'btn-light',
                                                        default => 'btn-primary',
                                                    };
                                                @endphp
                                                <button class="btn btn-sm {{ $btnClass }}"
                                                        wire:click="handleAction({{ $notification->id }}, '{{ $action['handler'] }}', {{ json_encode($action['data'] ?? []) }})">
                                                    {{ $action['label'] }}
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                                <button class="btn btn-sm btn-link text-primary p-0 ms-2 flex-shrink-0"
                                        wire:click="markAsRead({{ $notification->id }})"
                                        title="Mark as read">
                                    <i class="fas fa-check-circle"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif

            {{-- Drawer footer: view all notifications --}}
            <div class="border-top p-2 text-center bg-light">
                <a href="/notifications" class="text-primary fw-semibold text-sm text-decoration-none">
                    View All Notifications <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>
    </div>
    {{-- Backdrop --}}
    <div class="offcanvas-backdrop fade show" wire:click="closeNotificationsDrawer" style="z-index: 1040;"></div>
    @endif
</nav>
