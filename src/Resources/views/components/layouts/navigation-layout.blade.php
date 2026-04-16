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
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $pageTitle ?? config('app.name') }}</title>


    {{-- Your CSS assets (from config) --}}
    <link id="pagestyle" href="{{ config('ui-library.theme.css') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />




    <link id="pagestyle" href="{{ asset('bootstrap/assets/css/soft-ui-dashboard.css?v=1.0.3') }}" rel="stylesheet" />
    {{--  }}<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">--}}
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
    .offcanvas { position: fixed; bottom: 0; flex-direction: column; max-width: 100%; visibility: hidden; background-color: #fff; background-clip: padding-box; outline: 0; transition: transform .3s ease-in-out, visibility .3s ease-in-out; }
    .offcanvas.show { visibility: visible; }
    .offcanvas-end { top: 0; right: 0; width: 400px; transform: translateX(100%); border-left: 1px solid rgba(0,0,0,.2); }
    .offcanvas-end.show { transform: translateX(0); }
    .offcanvas-backdrop { position: fixed; top: 0; left: 0; z-index: 1040; width: 100vw; height: 100vh; background-color: #000; }
    .offcanvas-backdrop.show { opacity: 0.2; }
</style>
</head>

<body>

    <body>


        
        
        {{-- Top Bar --}}
        @if ($layoutConfig['top_bar']['enabled'] ?? true)
            <livewire:qf.top-nav :items="$contextGroups" :activeContext="$activeContext" :moduleName="$moduleName" :leftShared="$sharedTopLeft"
                :rightShared="$sharedTopRight" wire:key="top-nav-{{ $moduleName }}" />
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
                    <livewire:qf.horizontal-context-menu :items="$contextItems[$activeContext] ?? []" :position="$contextMenuPosition" :allowTypeSwitch="$allowMenuTypeSwitch"
                        wire:key="horizontal-menu-{{ $moduleName }}-{{ $activeContext }}" />
                @endif

                <main class="px-4" style="min-width: 0;">
                    {{-- ========== HEADER SECTION ========== --}}
                    @include('qf::components.layouts.partials.page-header')
                    {{ $slot }}
                </main>
            @else
                {{-- Sidebar mode: side‑by‑side --}}
                <div class="d-flex align-items-start main-content-wrapper">

                    @if ($showContextMenu)
                        <livewire:qf.sidebar :items="$contextItems[$activeContext] ?? []" :state="$sidebarState" 
                            :headerItems="$sharedHeaderItems" :footerItems="$sharedFooterItems" :currentModelName="$currentModelName"
                            :allowTypeSwitch="$allowMenuTypeSwitch" wire:key="sidebar-menu-{{ $moduleName }}-{{ $activeContext }}" />
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
        <livewire:qf.drawer  />

        <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
            integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
        </script>
        <script src="{{ asset('assets/js/quicker-faster.js') }}"></script>


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


        {{--   Filter Panel Saving modal  ----}}
        @push('scripts')
            <script>
                document.addEventListener('livewire:initialized', () => {

                    Livewire.on('openSaveFilterModal', () => {
                        var modal = new bootstrap.Modal(document.getElementById('saveFilterModal'));
                        modal.show();
                    });
                    Livewire.on('closeSaveFilterModal', () => {
                        var modal = bootstrap.Modal.getInstance(document.getElementById('saveFilterModal'));
                        if (modal) modal.hide();
                    });
                });
            </script>
        @endpush

        @stack('scripts')




        <!-- CDN loading required js libraries -->
        <script src="https://unpkg.com/jszip@3.10.1/dist/jszip.min.js"></script>
        <script src="https://cdn.jsdelivr.net/npm/docx-preview@0.3.5/dist/docx-preview.js"></script>
        <!-- For XLS/XLSX preview (SheetJS) -->
        <script src="https://cdn.sheetjs.com/xlsx-0.20.2/package/dist/xlsx.full.min.js"></script>

    </body>

</html>
