@props(['moduleName', 'configKey' => null, 'activeContext' => null])

@php
    $contextMenuType = $contextMenuType ?? 'sidebar';
    $contextMenuPosition = $contextMenuPosition ?? 'left';
    $allowMenuTypeSwitch = $allowMenuTypeSwitch ?? false;
@endphp

<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>{{ $pageTitle ?? config('app.name') }}@include('qf::components.layouts.partials.company-title-suffix')</title>


    {{-- Your CSS assets (from config) --}}
    <link id="pagestyle" href="{{ config('ui-library.theme.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />




    <link id="pagestyle" href="{{ asset('vendor/ui-library/bootstrap/assets/css/soft-ui-dashboard.css?v=1.0.3') }}" rel="stylesheet" />
    {{--  }}<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous"> --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css"
        integrity="sha512-2SwdPD6INVrV/lHTZbO2nodKhrnDdJK9/kg2XD1r9uGqPo1cUbujc+IYdlYdEErWNu69gVcYgdxlmVmzTWnetw=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />


    <!-- Cropper.js CSS & JS from CDN -->
    <link href="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/cropperjs@1.6.2/dist/cropper.min.js"></script>




    <style>
        .modal-backdrop {
            z-index: 1040 !important;
        }

        .modal {
            z-index: 1050 !important;
        }
    </style>

    @livewireStyles
    <style>
        [x-cloak] {
            display: none !important;
        }

        .sidebar-container {
            transition: width 0.3s ease;
        }

        .main-content-wrapper {
            min-height: calc(100vh - 60px);
        }

        .sidebar-full {
            width: 220px;
        }

        .sidebar-icon {
            width: 60px;
        }
    </style>


    <style>
        /* Fix offcanvas for Soft UI */
        .offcanvas {
            position: fixed;
            bottom: 0;
            flex-direction: column;
            max-width: 100%;
            visibility: hidden;
            background-color: #fff;
            background-clip: padding-box;
            outline: 0;
            transition: transform .3s ease-in-out, visibility .3s ease-in-out;
        }

        .offcanvas.show {
            visibility: visible;
        }

        .offcanvas-end {
            top: 0;
            right: 0;
            width: 400px;
            transform: translateX(100%);
            border-left: 1px solid rgba(0, 0, 0, .2);
        }

        .offcanvas-end.show {
            transform: translateX(0);
        }

        .offcanvas-backdrop {
            position: fixed;
            top: 0;
            left: 0;
            z-index: 1040;
            width: 100vw;
            height: 100vh;
            background-color: #000;
        }

        .offcanvas-backdrop.show {
            opacity: 0.2;
        }
    </style>
</head>

<body>

    <body>




        {{-- Top Bar --}}
        @if ($layoutConfig['top_bar']['enabled'] ?? true)
            <livewire:qf.top-nav :items="$contextGroups" :activeContext="$activeContext" :moduleName="$moduleName" :leftShared="$sharedTopLeft"
                :rightShared="$sharedTopRight" :hideTopnavContexts="$hideTopnavContexts ?? false" wire:key="top-nav-{{ $moduleName }}" />
        @endif

        {{-- Workspace Tabs (browser-style tab strip) --}}
        @if (config('ui-library.layout.workspace_tabs.enabled', true))
            <livewire:qf.workspace-tabs />
        @endif

        {{-- Desktop context area --}}
        <div class="d-none d-md-block mt-5">
            @php
                $currentMenuType = session('context_menu_type', $contextMenuType);
                $showContextMenu = $layoutConfig['context_menu']['enabled'] ?? true;
            @endphp

            @if ($currentMenuType === 'horizontal')

                {{-- Horizontal mode: menu above content --}}
                @if ($showContextMenu)
                    <livewire:qf.horizontal-context-menu
                        :currentModelName="$currentModelName"
                        :items="$contextItems[$activeContext] ?? []"
                        :position="$contextMenuPosition"
                        :allowTypeSwitch="$allowMenuTypeSwitch"
                        :maxVisibleItems="$maxVisibleItems"
                        :contextGroups="$contextGroups"
                        :contextItems="$contextItems"
                        :activeContext="$activeContext"
                        :showAllContexts="$showAllContexts ?? false"
                        wire:key="horizontal-menu-{{ $moduleName }}-{{ $activeContext }}" />
                @endif

                <main class="px-4" style="min-width: 0;">
                    {{-- ========== HEADER SECTION ========== --}}
                    @include('qf::components.layouts.partials.page-header')
                    {{ $slot }}
                </main>
            @else
                {{-- Sidebar mode: side‑by‑side --}}
                @php
                    $showSidebar = $layoutConfig['sidebar']['enabled'] ?? true;
                @endphp
                <div class="d-flex align-items-start main-content-wrapper">

                    @if ($showContextMenu && $showSidebar)
                        @php
                            $activeCtxGroup = $contextGroups[$activeContext] ?? [];
                        @endphp
                        <livewire:qf.sidebar :items="$contextItems[$activeContext] ?? []" :state="$sidebarState" :headerItems="$sharedHeaderItems" :footerItems="$sharedFooterItems"
                            :currentModelName="$currentModelName" :allowTypeSwitch="$allowMenuTypeSwitch"
                            :settingsContext="$settingsContext" :moduleName="$moduleName"
                            :activeContext="$activeContext ?? null"
                            :contextGroupLabel="$activeCtxGroup['label'] ?? $activeContext"
                            :contextGroupIcon="$activeCtxGroup['icon'] ?? 'fa-folder'"
                            :contextGroupConfig="$activeCtxGroup"
                            wire:key="sidebar-menu-{{ $moduleName }}-{{ $activeContext }}" />
                    @endif

                    <main class="flex-grow-1 px-4" style="min-width: 0;">
                        {{-- ========== HEADER SECTION ========== --}}
                        @include('qf::components.layouts.partials.page-header')
                        {{ $slot }}
                    </main>
                </div>
            @endif
        </div>


        {{-- Bottom Bar (mobile) --}}
        @if ($layoutConfig['bottom_bar']['enabled'] ?? true)
            <livewire:qf.bottom-bar :items="$contextItems[$activeContext] ?? []" :activeContext="$activeContext" wire:key="bottom-bar-{{ $moduleName }}" />
        @endif








        {{-- Global modals --}}
        <livewire:qf.alert-modal :configKey="$configKey ?? ''" />
        <livewire:qf.detail-modal :configKey="$configKey ?? ''" />
        <livewire:qf.form-modal :configKey="$configKey ?? ''" />
        <livewire:qf.import-modal :configKey="$configKey ?? ''" />
        <livewire:qf.export-modal :configKey="$configKey ?? ''" />

        <livewire:qf.document-preview-modal />
        <livewire:qf.crop-image-modal />

        {{-- <livewire:qf:drawer :configKey="$configKey" /> --}}
        <livewire:qf.drawer />

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>
        <script src="{{ asset('vendor/ui-library/assets/js/quicker-faster.js') }}"></script>


        @livewireScripts
        <script>
            Livewire.on('saveMenuType', (type) => {
                localStorage.setItem('contextMenuType', type);
            });
            Livewire.on('saveSidebarState', (state) => {
                localStorage.setItem('sidebarState', state);
            });
            document.addEventListener('DOMContentLoaded', () => {
                const savedSidebarState = localStorage.getItem('sidebarState');
                if (savedSidebarState) Livewire.dispatch('sidebarStateChanged', savedSidebarState);
            });

            Livewire.on('menu-type-changed', (type) => {
                window.dispatchEvent(new CustomEvent('menu-type-changed', {
                    detail: type
                }));
            });


            Livewire.on('doReload', () => {
                window.location.reload();
            });
        </script>




        <script>
            window.addEventListener('open-url-new-tab', event => {
                window.open(event.detail[0], '_blank');
            });
        </script>


        @stack('scripts')




        <!-- CDN loading required js libraries -->
        <script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.5/dist/docx-preview.js"></script>
        <!-- For XLS/XLSX preview (SheetJS) -->
        <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>










        <script>
            function initDrawer() {
                const drawerElement = document.getElementById('globalDrawer');
                if (!drawerElement) {
                    console.warn('Drawer element not found, will retry on next navigation');
                    return;
                }

                // Create a single Offcanvas instance and keep it
                let bsDrawer = new bootstrap.Offcanvas(drawerElement, {
                    backdrop: true,
                    keyboard: true,
                    scroll: false
                });

                // When the offcanvas is fully hidden (after close animation)
                drawerElement.addEventListener('hidden.bs.offcanvas', function() {
                    // Tell Livewire that the drawer is closed (sync state)
                    Livewire.dispatch('closeDrawer');
                });

                // When the offcanvas is shown, do nothing special – just ensure Livewire knows it's open
                drawerElement.addEventListener('shown.bs.offcanvas', function() {
                    // Optionally dispatch an event if needed
                    Livewire.dispatch('drawerOpened');
                });

                // Listen for Livewire events to show/hide the drawer
                Livewire.on('drawerOpened', () => {
                    bsDrawer.show();
                });

                // Also listen for a custom close event in case Livewire calls closeDrawer
                Livewire.on('closeDrawer', () => {
                    bsDrawer.hide();
                });
            }

            // Initialize on page load
            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', initDrawer);
            } else {
                initDrawer();
            }

            // Re-initialize after Livewire navigations (in case the drawer element is replaced)
            document.addEventListener('livewire:navigated', function() {
                // Wait a tick for DOM to settle
                setTimeout(() => {
                    // Destroy old instance if exists? Actually we just re-run initDrawer, but we must avoid duplicates.
                    // Better: remove existing listener and re-init. For simplicity, we re-run initDrawer but check if already initialized.
                    // We'll add a guard: if a global flag exists, skip.
                    if (!window.drawerInitialized) {
                        window.drawerInitialized = true;
                        initDrawer();
                    } else {
                        // Re-attach Livewire listeners (they persist, but offcanvas instance might be stale)
                        // Force re-create the Offcanvas instance
                        const drawerEl = document.getElementById('globalDrawer');
                        if (drawerEl) {
                            let existing = bootstrap.Offcanvas.getInstance(drawerEl);
                            if (existing) existing.dispose();
                            window.drawerInitialized = false;
                            initDrawer();
                        }
                    }
                }, 100);
            });
        </script>







    </body>

</html>
