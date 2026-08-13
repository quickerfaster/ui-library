<nav class="navbar navbar-expand navbar-light bg-light shadow-sm mb-3 pt-3 pb-0"
     @if($position === 'right') style="justify-content: flex-end;" @endif>
    
    <div class="container-fluid {{ $position === 'right' ? 'justify-content-end' : '' }}">
        <!-- Left-aligned menu items -->
        <ul class="navbar-nav {{ $position === 'right' ? '' : 'me-auto' }}">

            {{-- ============================================ --}}
            {{-- Phase 2: All Contexts as Dropdowns            --}}
            {{-- ============================================ --}}
            @if ($showAllContexts)
                @foreach ($contextGroups as $contextKey => $group)
                    @php
                        $isActiveContext = ($contextKey === $activeContext);
                        $groupLabel = $group['label'] ?? $contextKey;
                        $groupIcon = $group['icon'] ?? null;
                        $groupItems = $contextItems[$contextKey] ?? [];
                        $visibleForCtx = $this->getVisibleItemsForContext($contextKey);
                        $overflowForCtx = $this->getOverflowItemsForContext($contextKey);
                        $hasOverflowForCtx = $overflowForCtx->isNotEmpty();
                    @endphp

                    <li class="nav-item dropdown"
                        wire:key="ctx-dropdown-{{ $contextKey }}">
                        <a class="nav-link dropdown-toggle {{ $isActiveContext ? 'active fw-bold text-primary' : '' }}"
                           href="#"
                           role="button"
                           data-bs-toggle="dropdown"
                           aria-expanded="false"
                           aria-haspopup="true">
                            @if ($groupIcon)
                                <i class="fa {{ $groupIcon }} me-1"></i>
                            @endif
                            {{ $groupLabel }}
                        </a>
                        <ul class="dropdown-menu {{ $position === 'right' ? 'dropdown-menu-end' : '' }}"
                            role="menu"
                            aria-label="{{ $groupLabel }} navigation items">
                            {{-- Visible items for this context group --}}
                            @foreach ($visibleForCtx as $item)
                                @php
                                    $isActive = $this->isItemActive($item);
                                    $itemUrl = $this->resolveItemUrl($item);
                                @endphp
                                <li wire:key="ctx-{{ $contextKey }}-visible-{{ $item['key'] ?? $loop->index }}"
                                    role="none">
                                    <a href="{{ $itemUrl }}"
                                       class="dropdown-item d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : '' }}"
                                       role="menuitem"
                                       wire:navigate>
                                        @if (!empty($item['icon']))
                                            <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;" aria-hidden="true"></i>
                                        @endif
                                        <span>{{ $item['label'] }}</span>
                                    </a>
                                </li>
                            @endforeach

                            {{-- Overflow "More" sub-dropdown within this context group --}}
                            @if ($hasOverflowForCtx)
                                @php
                                    $ctxActiveInOverflow = false;
                                    foreach ($overflowForCtx as $overflowItem) {
                                        if ($this->isItemActive($overflowItem)) {
                                            $ctxActiveInOverflow = true;
                                            break;
                                        }
                                    }
                                @endphp
                                <li role="separator">
                                    <hr class="dropdown-divider">
                                </li>
                                <li class="dropdown-submenu dropstart"
                                    wire:key="ctx-{{ $contextKey }}-more-submenu">
                                    <a class="dropdown-item dropdown-toggle d-flex align-items-center {{ $ctxActiveInOverflow ? 'active fw-bold text-primary' : '' }}"
                                       href="#"
                                       role="button"
                                       data-bs-toggle="dropdown"
                                       aria-expanded="false"
                                       aria-haspopup="true">
                                        <i class="fas fa-ellipsis me-2" style="width: 20px;" aria-hidden="true"></i>
                                        <span>More</span>
                                        @if ($ctxActiveInOverflow)
                                            <span class="badge rounded-pill bg-primary ms-1" style="font-size: 0.5rem;">●</span>
                                        @endif
                                    </a>
                                    <ul class="dropdown-menu"
                                        role="menu"
                                        aria-label="Additional {{ $groupLabel }} items">
                                        @foreach ($overflowForCtx as $item)
                                            @php
                                                $isActive = $this->isItemActive($item);
                                                $itemUrl = $this->resolveItemUrl($item);
                                            @endphp
                                            <li wire:key="ctx-{{ $contextKey }}-overflow-{{ $item['key'] ?? $loop->index }}"
                                                role="none">
                                                <a href="{{ $itemUrl }}"
                                                   class="dropdown-item d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : '' }}"
                                                   role="menuitem"
                                                   wire:navigate>
                                                    @if (!empty($item['icon']))
                                                        <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;" aria-hidden="true"></i>
                                                    @endif
                                                    <span>{{ $item['label'] }}</span>
                                                </a>
                                            </li>
                                        @endforeach
                                    </ul>
                                </li>
                            @endif
                        </ul>
                    </li>
                @endforeach

            @else
                {{-- ============================================ --}}
                {{-- Phase 1: Flat visible + overflow layout      --}}
                {{-- ============================================ --}}
                {{-- Visible items (inline links) --}}
                @foreach ($this->visibleItems as $item)
                    @php
                        $isActive = $this->isItemActive($item);
                        $itemUrl = $this->resolveItemUrl($item);
                    @endphp
                    
                    <li class="nav-item">
                        <a href="{{ $itemUrl }}"
                           class="nav-link {{ $isActive ? 'active fw-bold text-primary' : '' }}"
                           wire:navigate>
                            @if (!empty($item['icon']))
                                <i class="fa {{ $item['icon'] }} me-1"></i>
                            @endif
                            {{ $item['label'] }}
                        </a>
                    </li>
                @endforeach

                {{-- "More" dropdown (when overflow exists) --}}
                @if ($this->overflowItems->isNotEmpty())
                    <li class="nav-item dropdown" wire:key="context-overflow-dropdown">
                        <a class="nav-link dropdown-toggle"
                           href="#" role="button" data-bs-toggle="dropdown"
                           aria-expanded="false" aria-haspopup="true">
                            More
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end"
                            role="menu"
                            aria-label="Additional navigation items">
                            @php $lastSection = null; @endphp
                            @foreach ($this->overflowItems as $item)
                                @php
                                    $itemSection = $item['section'] ?? null;
                                    $isActive = $this->isItemActive($item);
                                    $itemUrl = $this->resolveItemUrl($item);
                                @endphp

                                {{-- Section header: only render once per section, using a tracking variable --}}
                                @if ($itemSection && $itemSection !== $lastSection)
                                    <li role="presentation">
                                        <h6 class="dropdown-header ps-2 text-uppercase text-xs font-weight-bolder opacity-6"
                                            role="separator">
                                            {{ $itemSection }}
                                        </h6>
                                    </li>
                                    @php $lastSection = $itemSection; @endphp
                                @endif

                                <li wire:key="context-overflow-{{ $item['key'] ?? $loop->index }}"
                                    role="none">
                                    <a href="{{ $itemUrl }}"
                                       class="dropdown-item d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : '' }}"
                                       role="menuitem">
                                        @if (!empty($item['icon']))
                                            <i class="fa {{ $item['icon'] }} me-2" style="width: 20px;" aria-hidden="true"></i>
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

        <!-- Right-aligned switch button -->
        @if ($allowTypeSwitch)
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <button wire:click="switchToSidebar" class="btn btn-sm btn-outline-secondary"
                            title="Switch to sidebar">
                        <i class="fa fa-bars-staggered"></i>
                        <span class="d-none d-md-inline">Sidebar</span>
                    </button>
                </li>
            </ul>
        @endif
    </div>
</nav>
