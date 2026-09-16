<nav id="main-nav" class="navbar navbar-expand-md navbar-light bg-white shadow-sm fixed-top" style="z-index: 1030;">

    <style>
        #main-nav .dropdown-menu {
            z-index: 1050 !important;
        }
        #main-nav .context-tabs {
            list-style: none;
            padding: 0;
            margin: 0;
        }
    </style>

    <div class="d-flex align-items-center w-100 px-2">

        {{-- Left: Module Switcher → NavigationHub --}}
        @if ($moduleSwitcherEnabled && !empty($this->modules))
        <button class="btn btn-sm btn-outline-primary px-3 py-1 my-0 fw-medium me-2 flex-shrink-0" type="button"
            @click="Livewire.dispatch('openNavigationHub', { scrollTo: 'module' })"
            aria-label="Switch Module">
            <i class="fas fa-th-large me-1"></i>
            <span class="d-none d-md-inline">{{ $this->currentModuleLabel }}</span>
        </button>
        @endif

        {{-- Desktop: Context group tabs --}}
        <div class="d-none d-md-flex align-items-center flex-grow-1 overflow-hidden" style="gap: 0.25rem;">
            @php
                $currentModule = strtolower($this->moduleName);
                use Illuminate\Support\Str;
            @endphp

            {{-- Cross-module links --}}
            @if (!empty($crossModuleLinks['admin']))
                <a href="{{ $crossModuleLinks['admin']['url'] ?? '/admin/dashboard' }}"
                    class="btn btn-sm px-3 py-1 rounded-pill btn-outline-primary flex-shrink-0">
                    <i class="{{ $crossModuleLinks['admin']['icon'] ?? 'fas fa-cog' }} me-1"></i>
                    {{ $crossModuleLinks['admin']['label'] ?? 'Admin Panel' }}
                </a>
            @endif

            @if ($currentModule === 'admin' && !empty($crossModuleLinks['back']))
                <a href="{{ $crossModuleLinks['back']['url'] ?? '/' }}"
                    class="btn btn-sm px-3 py-1 rounded-pill btn-outline-primary flex-shrink-0">
                    <i class="{{ $crossModuleLinks['back']['icon'] ?? 'fas fa-reply' }} me-1"></i>
                    {{ $crossModuleLinks['back']['label'] ?? 'Back' }}
                </a>
            @endif

            {{-- Left shared items --}}
            @foreach ($leftShared as $item)
                @include('qf::livewire.navs.partials.top-nav-item', ['item' => $item])
            @endforeach

            {{-- Context group tabs --}}
            @if (!$hideTopnavContexts)
                <ul class="navbar-nav context-tabs">
                @foreach ($this->visibleDesktop as $key => $item)
                    @include('qf::livewire.navs.partials.top-nav-item', ['item' => $item, 'key' => $key])
                @endforeach

                @if ($this->overflowDesktop->isNotEmpty())
                    @php $isOverflowActive = $this->overflowDesktop->has($activeContext); @endphp
                    <div class="dropdown" wire:key="overflow-dropdown">
                        <a class="btn btn-sm px-3 py-1 nav-link dropdown-toggle {{ $isOverflowActive ? 'active fw-bold text-primary' : '' }}"
                            href="#" data-bs-toggle="dropdown" data-bs-display="static">
                            {{ __('qf::nav.more') }}
                        </a>
                        <ul class="dropdown-menu">
                            @foreach ($this->overflowDesktop as $key => $item)
                                @php
                                    $url = isset($item['route']) && !Str::contains($item['route'], '/')
                                        ? route($item['route']) : url($item['url'] ?? Str::kebab($key));
                                @endphp
                                <li>
                                    <a href="{{ $url }}" class="dropdown-item d-flex align-items-center {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
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
                </ul>
            @endif
        </div>

        {{-- Right: Actions --}}
        <div class="d-flex align-items-center ms-auto flex-shrink-0" style="gap: 0.125rem;">

            {{-- Company Switcher → NavigationHub (desktop + mobile unified) --}}
            @if ($companies && $companies->isNotEmpty())
                @php $isAllCompanies = $currentCompanyId === 0; @endphp
                <button class="btn btn-sm {{ $isAllCompanies ? 'btn-outline-info' : 'btn-outline-primary' }} px-2 py-1 my-0 fw-medium"
                        @click="Livewire.dispatch('openNavigationHub', { scrollTo: 'company' })"
                        aria-label="Switch Company">
                    <i class="fas {{ $isAllCompanies ? 'fa-globe' : 'fa-building' }} me-1"></i>
                    <span class="d-none d-md-inline">{{ \Illuminate\Support\Str::limit($currentCompanyName, 12) }}</span>
                </button>
            @endif

            {{-- Notifications (always visible) --}}
            @if ($notificationsEnabled)
            <a href="#" class="px-2 py-1 my-0 position-relative" wire:click.prevent="openNotificationsDrawer" title="{{ $notificationsTitle }}">
                <i class="{{ $notificationsIcon }}"></i>
                @if ($this->unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 0.6rem;">
                    {{ $this->unreadCount > 99 ? '99+' : $this->unreadCount }}
                </span>
                @endif
            </a>
            @endif

            {{-- Quick Actions Cmd+K (always visible) --}}
            @if ($quickActionsEnabled)
            <a href="#" class="px-2 py-1 my-0" wire:click.prevent="openQuickActions" title="{{ $quickActionsTitle }}">
                <i class="{{ $quickActionsIcon }}"></i>
            </a>
            @endif

            {{-- Mobile overflow: Background Jobs + Quick Actions ⚡ --}}
            @if ($backgroundJobsEnabled || $quickActionsButtonEnabled)
            <div class="dropdown d-md-none" wire:key="mobile-actions-overflow">
                <a href="#" class="px-2 py-1 my-0 dropdown-toggle" data-bs-toggle="dropdown" title="More actions">
                    <i class="fas fa-ellipsis-h"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    @if ($backgroundJobsEnabled)
                    <li>
                        <a href="#" class="dropdown-item" wire:click.prevent="openBackgroundJobsDrawer">
                            <i class="{{ $backgroundJobsIcon }} me-2"></i> {{ $backgroundJobsTitle }}
                        </a>
                    </li>
                    @endif
                    @if ($quickActionsButtonEnabled)
                    <li>
                        <a href="#" class="dropdown-item" wire:click.prevent="openQuickActions">
                            <i class="{{ $quickActionsButtonIcon }} me-2"></i> {{ $quickActionsButtonTitle }}
                        </a>
                    </li>
                    @endif
                </ul>
            </div>
            @endif

            {{-- Desktop: Background Jobs + Quick Actions ⚡ (full visibility) --}}
            @if ($backgroundJobsEnabled)
            <a href="#" class="d-none d-md-inline px-2 py-1 my-0" wire:click.prevent="openBackgroundJobsDrawer" title="{{ $backgroundJobsTitle }}">
                <i class="{{ $backgroundJobsIcon }}"></i>
            </a>
            @endif

            @if ($quickActionsButtonEnabled)
            <div class="dropdown d-none d-md-block" id="quick-actions-dropdown" wire:key="quick-actions-dropdown">
                <a href="#" class="px-2 py-1 my-0 position-relative dropdown-toggle {{ $showQuickActionsPulse ? 'qa-pulse' : '' }}"
                    data-bs-toggle="dropdown" aria-label="Quick Actions" title="{{ $quickActionsButtonTitle }}">
                    <i class="{{ $quickActionsButtonIcon }}"></i>
                    @if ($showQuickActionsPulse)
                    <span class="position-absolute top-0 start-100 translate-middle p-1 bg-warning border border-light rounded-circle" style="width: 8px; height: 8px;">
                        <span class="visually-hidden">New</span>
                    </span>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2" style="min-width: 300px;">
                    <li><h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6">
                        <i class="{{ $quickActionsButtonIcon }} me-1"></i>{{ $quickActionsButtonTitle }}</h6></li>
                    @forelse ($quickActions as $action)
                        @php $actionId = $action['id'] ?? $action['key'] ?? ''; $isFav = in_array($actionId, $quickActionFavorites, true); @endphp
                        <li wire:key="quick-action-{{ $actionId }}">
                            <div class="dropdown-item border-radius-md d-flex align-items-center pe-1">
                                <a href="#" class="d-flex align-items-center text-decoration-none flex-grow-1 min-width-0"
                                    wire:click.prevent="executeQuickAction('{{ $actionId }}')">
                                    <span class="icon-shape icon-xs rounded-2 bg-gradient-warning text-white d-inline-flex align-items-center justify-content-center me-2" style="width: 28px; height: 28px; flex-shrink: 0;">
                                        <i class="fa-solid {{ $action['icon'] ?? 'fas fa-bolt' }}"></i></span>
                                    <span class="min-width-0">
                                        <span class="d-block text-sm fw-medium text-dark text-truncate">{{ $action['label'] }}</span>
                                        @if (!empty($action['description']))
                                            <span class="d-block text-xs text-muted text-truncate">{{ $action['description'] }}</span>
                                        @endif
                                    </span>
                                </a>
                                <button type="button" class="btn btn-sm border-0 p-1 ms-1 {{ $isFav ? 'text-warning' : 'text-muted' }}"
                                    wire:click.stop="toggleQuickActionFavorite('{{ $actionId }}')" title="{{ $isFav ? 'Unpin' : 'Pin' }}" style="flex-shrink: 0;">
                                    <i class="{{ $isFav ? 'fas' : 'far' }} fa-star fa-sm"></i>
                                </button>
                            </div>
                        </li>
                    @empty
                        <li><span class="dropdown-item-text text-muted text-sm">No actions available yet.</span></li>
                    @endforelse
                    <li><hr class="dropdown-divider my-1"></li>
                    <li><a href="#" class="dropdown-item text-sm text-primary fw-semibold" wire:click.prevent="openQuickActions">
                        More actions… <i class="fas fa-arrow-right ms-1"></i></a></li>
                </ul>
            </div>
            @endif

            {{-- Locale switcher --}}
            <div class="dropdown" id="language-switcher">
                <a href="#" class="dropdown-toggle px-2 py-1 my-0" data-bs-toggle="dropdown" title="{{ strtoupper(app()->getLocale()) }}">
                    <i class="fas fa-globe"></i>
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0">
                    <li><a class="dropdown-item {{ app()->getLocale() === 'en' ? 'active' : '' }}" href="#">English</a></li>
                    <li><a class="dropdown-item {{ app()->getLocale() === 'fr' ? 'active' : '' }}" href="#">Français</a></li>
                    <li><a class="dropdown-item {{ app()->getLocale() === 'es' ? 'active' : '' }}" href="#">Español</a></li>
                </ul>
            </div>

            {{-- Profile --}}
            <div class="dropdown ms-1 ps-2" wire:ignore id="user-profile-menu">
                <a class="d-flex align-items-center text-decoration-none dropdown-toggle" href="#"
                    data-bs-toggle="dropdown" title="{{ auth()->user()?->name ?: 'Account' }}">
                    @if (auth()->user()?->avatar_url)
                        <img src="{{ auth()->user()->avatar_url }}" alt="Profile" class="rounded-circle" style="width: 32px; height: 32px; object-fit: cover;">
                    @else
                        <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center" style="width: 32px; height: 32px;">
                            <i class="fas fa-user"></i>
                        </div>
                    @endif
                </a>
                <ul class="dropdown-menu dropdown-menu-end shadow border-0 p-2 mt-2">
                    <li><span class="dropdown-item-text text-dark fw-medium border-bottom mb-1 pb-2">{{ ucwords(auth()->user()?->name) ?: 'Account' }}</span></li>
                    @php
                        $userMenu = config('ui-library.user_menu');
                        $userMenuEnabled = $userMenu['enabled'] ?? true;
                        $userMenuLinks = $userMenu['links'] ?? [];
                        $visibleLinks = array_filter($userMenuLinks, fn($link) => !empty($link['url']) || !empty($link['route']));
                    @endphp
                    @if ($userMenuEnabled)
                        @auth
                            @foreach ($visibleLinks as $link)
                                @php $linkUrl = !empty($link['url']) ? url($link['url']) : (!empty($link['route']) ? route($link['route']) : '#'); @endphp
                                <li><a class="dropdown-item border-radius-md mb-1" href="{{ $linkUrl }}">
                                    <i class="{{ $link['icon'] ?? 'fas fa-link' }} me-2 opacity-6 text-sm"></i>{{ $link['label'] ?? 'Link' }}</a></li>
                            @endforeach
                        @endauth
                    @endif
                    <li class="d-none d-md-block"><a class="dropdown-item border-radius-md mb-1" href="{{ route('tour.restart') }}">
                        <i class="fas fa-play-circle me-2 opacity-6 text-sm text-primary"></i> Take the Tour</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li><button class="dropdown-item text-danger border-radius-md" type="button" wire:click="logout">
                        <i class="fas fa-sign-out-alt me-1 text-sm"></i> Logout</button></li>
                </ul>
            </div>

        </div>
    </div>

</nav>
