<div
    class="sidebar-container bg-light border-end d-flex flex-column align-items-stretch
            @if ($state === 'full') sidebar-full
            @else sidebar-icon @endif"
    x-data="{
        expandedSections: {{ Js::from($expandedSections) }},
        toggle(key) {
            if (this.expandedSections[key]) {
                delete this.expandedSections[key];
            } else {
                this.expandedSections[key] = true;
            }
        },
        isExpanded(key) {
            return this.expandedSections[key] === true;
        }
    }">

    {{-- Phase 4.4: Application/Organization Switcher --}}
    @include('qf::components.application-switcher', [
        'currentOrganization' => $currentOrganization ?? null,
        'userOrganizations' => $userOrganizations ?? collect(),
    ])

    {{-- Phase 5.3: Sidebar fuzzy filter --}}
    <div class="sidebar-filter p-2" data-sidebar-filter-wrap>
        <div class="input-group input-group-sm">
            <span class="input-group-text" data-sidebar-filter-icon>
                <i class="fas fa-search"></i>
            </span>
            <input type="text"
                   class="form-control form-control-sm"
                   placeholder="{{ __('qf::nav.filter_modules') }}"
                   aria-label="{{ __('qf::nav.filter_modules') }}"
                   title="{{ __('qf::nav.filter_modules') }}"
                   data-sidebar-filter>
            <button type="button" class="btn btn-outline-secondary btn-sm"
                    data-sidebar-filter-clear style="display:none;">
                <i class="fas fa-times"></i>
            </button>
        </div>
        <div data-sidebar-filter-no-results class="text-muted small p-2" style="display:none;">
            {{ __('qf::nav.no_results') }}
        </div>
    </div>

    <ul class="nav flex-column mt-3">
        @foreach ($headerItems as $item)
            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
        @endforeach

        {{--
            Priority chain for sidebar rendering (descending):
            1. Context-specific items from NavigationLayout (when $activeContext is set + $items not empty)
            2. Module sections from NavigationManager/buildModuleSections() (Phase 4.3/4.5)
            3. Config-driven sections from SidebarComposer (Phase 4.5)
            4. Flat $items list (backward-compatible fallback)
            5. Debug message (when everything is empty)
        --}}
        @php
            $hasContextItems = !empty($activeContext) && !empty($items);
            $hasModuleSections = !empty($moduleSections);
            $hasSidebarSections = !empty($sidebarSections);
        @endphp

        @if ($hasContextItems)
            {{-- Priority 1: Context-specific items from NavigationLayout --}}
            {{-- Read sidebar rendering options from the context group's 'sidebar' config key. --}}
            @php
                $sidebarConfig = $contextGroupConfig['sidebar'] ?? [];
                $sectionLabel = $sidebarConfig['section_label'] ?? null;

                // If section_label not explicitly set, use the context group label
                if ($sectionLabel === null) {
                    $sectionLabel = $contextGroupLabel ?? $activeContext;
                }
                // false means no section header at all
                $showSectionHeader = $sectionLabel !== false;

                $isCollapsible = $sidebarConfig['collapsible'] ?? true;
                $sectionIcon = $contextGroupIcon ?? 'fa-folder';
                $sectionKey = 'context-' . $activeContext;
            @endphp

            @if ($showSectionHeader && $isCollapsible)
                {{-- Collapsible section: delegate to sidebar-section partial --}}
                @include('qf::livewire.navs.partials.sidebar-section', [
                    'section' => [
                        'key' => $sectionKey,
                        'label' => $sectionLabel,
                        'icon' => $sectionIcon,
                        'items' => $items,
                        'has_active' => true,
                        'collapsible' => true,
                    ],
                    'state' => $state,
                    'currentModelName' => $currentModelName,
                ])
            @elseif ($showSectionHeader && !$isCollapsible)
                {{-- Static label above items, no toggle --}}
                <li class="nav-item mb-1" wire:key="sidebar-section-{{ $sectionKey }}">
                    <div class="sidebar-section-label small fw-semibold text-muted text-uppercase
                                {{ $state === 'icon' ? 'px-0 py-1 d-flex justify-content-center' : 'px-3 py-2' }}"
                         style="font-size: 0.7rem; letter-spacing: 0.05em;"
                         data-filterable
                         data-filter-text="{{ strtolower($sectionLabel ?? '') }} {{ strtolower($sectionKey ?? '') }} {{ strtolower($activeContext ?? '') }}">
                        @if ($state === 'full')
                            <i class="fa {{ $sectionIcon }} me-2 text-muted" style="font-size: 0.85rem;" aria-hidden="true"></i>
                            {{ $sectionLabel }}
                        @else
                            <i class="fa {{ $sectionIcon }} text-muted" style="font-size: 0.85rem;"
                               data-bs-toggle="tooltip" data-bs-placement="right" title="{{ $sectionLabel }}"
                               aria-hidden="true"></i>
                        @endif
                    </div>
                    <ul class="nav flex-column">
                        @foreach ($items as $item)
                            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
                        @endforeach
                    </ul>
                </li>
            @else
                {{-- No section header: render items directly --}}
                @foreach ($items as $item)
                    @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
                @endforeach
            @endif
        @elseif ($hasModuleSections)
            {{-- Priority 2: Module sections (Phase 4.3 collapsible) --}}
            @foreach ($moduleSections as $section)
                @include('qf::livewire.navs.partials.sidebar-section', [
                    'section' => $section,
                    'state' => $state,
                    'currentModelName' => $currentModelName,
                ])
            @endforeach
        @elseif ($hasSidebarSections)
            {{-- Priority 3: Config-driven sections from SidebarComposer (Phase 4.5) --}}
            @foreach ($sidebarSections as $section)
                @include('qf::livewire.navs.partials.sidebar-section', [
                    'section' => $section,
                    'state' => $state,
                    'currentModelName' => $currentModelName,
                ])
            @endforeach
        @elseif (!empty($items))
            {{-- Priority 4: Flat $items list (backward-compatible fallback) --}}
            @foreach ($items as $item)
                @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
            @endforeach
        @else
            {{-- Priority 5: Everything empty — show debug message in debug mode --}}
            @if (config('app.debug'))
                <li class="nav-item px-3 py-2">
                    <span class="text-muted small fst-italic">
                        <i class="fas fa-info-circle me-1"></i> No navigation items available
                    </span>
                </li>
            @endif
        @endif

        @foreach ($footerItems as $item)
            @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
        @endforeach
    </ul>

    <div class="mt-auto">
        {{-- Settings Link — sits directly above the bottom toolbar --}}
        @if ($settingsContext)
            <ul class="nav flex-column mb-0 mb-3" style="background: inherit;">
                <li>
                    <a href="#" wire:click.prevent="openSettings"
                        class="nav-link d-flex align-items-center py-1 text-muted" data-bs-toggle="tooltip"
                        wire:ignore.self title="{{ $settingsContext }} Settings">
                        <i class="fas fa-cog opacity-6 me-2" aria-hidden="true"></i>
                        <span class="nav-link-text ms-1">{{ ucfirst($settingsContext) }} Settings</span>
                    </a>
                </li>
            </ul>
        @endif

        <div class="p-2 border-top d-flex justify-content-between align-items-center">
            <button wire:click="toggleState" class="btn btn-sm btn-outline-secondary px-3 me-2"
                title="Toggle sidebar width">
                <i
                    class="fa fa-chevron-left toggle-icon @if ($state === 'full') rotated-left @else rotated-right @endif"></i>
            </button>
            @if ($allowTypeSwitch)
                <button wire:click="switchToHorizontal" class="btn btn-sm btn-outline-secondary px-3 ms-2"
                    title="Switch to horizontal menu">
                    <i class="fa fa-arrows-alt-v"></i>
                    @if ($state === 'full')
                        <span>Horizontal</span>
                    @endif
                </button>
            @endif
        </div>
    </div>

    <style>
        /* Sidebar container core styles */
        .sidebar-container {
            flex-shrink: 0;
            /* prevent shrinking in flex layout */
            transition: width 0.3s ease;
            overflow-y: auto;
            overflow-x: hidden;
            position: sticky;
            top: 55px;
            /* height of top bar */
            height: calc(100vh - 60px);
            padding-bottom: 0.1rem;
        }

        /* Width states */
        .sidebar-full {
            width: 220px;
        }

        .sidebar-icon {
            width: 60px;
        }

        /* Hide text labels in icon mode */
        .sidebar-icon .nav-link span {
            display: none;
        }

        /* Toggle icon rotation */
        .toggle-icon {
            transition: transform 0.3s ease;
        }

        .rotated-left {
            transform: rotate(0deg);
        }

        .rotated-right {
            transform: rotate(180deg);
        }

        /* Phase 4.3: Section header styles */
        .sidebar-section-header {
            cursor: pointer;
            user-select: none;
            transition: background-color 0.2s ease;
            border-radius: 0.375rem;
            margin: 0 0.5rem;
        }

        .sidebar-section-header:hover {
            background-color: rgba(0, 0, 0, 0.04);
        }

        .sidebar-section-header.active-section {
            background-color: rgba(13, 110, 253, 0.08);
        }

        /* Icon mode: collapse section header to icon-only */
        .sidebar-icon .sidebar-section-header {
            margin: 0 0.25rem;
            border-radius: 50%;
            width: 36px;
            height: 36px;
            padding: 0 !important;
            justify-content: center;
            position: relative;
        }

        /* Expand indicator for icon mode section headers */
        .sidebar-icon .section-expand-indicator {
            position: absolute;
            bottom: 2px;
            right: 2px;
            font-size: 8px;
            opacity: 0.5;
            transition: opacity 0.2s ease, transform 0.2s ease;
        }

        .sidebar-icon .sidebar-section-header:hover .section-expand-indicator {
            opacity: 1;
        }

        .sidebar-icon .sidebar-section-label {
            padding: 0.25rem 0 !important;
        }

        .sidebar-section-chevron {
            transition: transform 0.25s ease;
            font-size: 0.7rem;
        }

        .sidebar-section-chevron.expanded {
            transform: rotate(90deg);
        }

        /* Phase 4.3: Section body transition */
        .sidebar-section-body {
            overflow: hidden;
            transition: max-height 0.3s ease, opacity 0.25s ease;
        }

        /* Phase 4.3: Indent child items slightly */
        .sidebar-section-body .nav-item .nav-link {
            padding-left: 2.5rem;
        }

        .sidebar-icon .sidebar-section-header span {
            display: none !important;
        }

        /* Remove child-item indentation in icon mode */
        .sidebar-icon .sidebar-section-body .nav-item .nav-link {
            padding-left: 0 !important;
            justify-content: center;
        }
    </style>
</div>
