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
    <link id="pagestyle" href="{{ asset('bootstrap/assets/css/soft-ui-dashboard.css?v=1.0.3') }}" rel="stylesheet" />
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css" />
    @livewireStyles
    <style>
        [x-cloak] { display: none !important; }
        .sidebar-container { transition: width 0.3s ease; }
        .main-content-wrapper { min-height: calc(100vh - 60px); }
        .sidebar-full { width: 220px; }
        .sidebar-icon { width: 60px; }
    </style>
</head>
<body>
    <br /><br />

    {{-- Top Bar --}}
    <livewire:qf.top-nav
        :items="$contextGroups"
        :activeContext="$activeContext"
        :moduleName="$moduleName"
        :leftShared="$sharedTopLeft"
        :rightShared="$sharedTopRight"
        wire:key="top-nav-{{ $moduleName }}"
    />

    {{-- Desktop context area – Alpine toggles layout structure --}}
{{-- Desktop context area – Alpine toggles between two Livewire components --}}
{{-- Desktop context area --}}
{{-- Desktop context area --}}
{{-- Desktop context area --}}
<div class="d-none d-md-block">
    @php
        $currentMenuType = session('context_menu_type', $contextMenuType);
    @endphp

    @if ($currentMenuType === 'horizontal')
        {{-- Horizontal mode: menu above content --}}
        <livewire:qf.horizontal-context-menu
            :items="$contextItems[$activeContext] ?? []"
            :position="$contextMenuPosition"
            :allowTypeSwitch="$allowMenuTypeSwitch"
            wire:key="horizontal-menu-{{ $moduleName }}-{{ $activeContext }}"
        />
        <main class="p-4 overflow-auto">
            {{ $slot }}
        </main>
    @else
        {{-- Sidebar mode: side‑by‑side --}}
        <div class="d-flex align-items-start main-content-wrapper">
            <livewire:qf.sidebar
                :items="$contextItems[$activeContext] ?? []"
                :state="$sidebarState"
                :headerItems="$sharedHeaderItems"
                :footerItems="$sharedFooterItems"
                :allowTypeSwitch="$allowMenuTypeSwitch"
                wire:key="sidebar-menu-{{ $moduleName }}-{{ $activeContext }}"
            />
            <main class="flex-grow-1 p-4 overflow-auto">
                {{ $slot }}
            </main>
        </div>
    @endif
</div>

    {{-- Bottom Bar (mobile) --}}
    <livewire:qf.bottom-bar
        :items="$contextItems[$activeContext] ?? []"
        :activeContext="$activeContext"
        wire:key="bottom-bar-{{ $moduleName }}"
    />

    {{-- Global modals --}}
    <livewire:qf.alert-modal :configKey="$configKey ?? ''" />
    <livewire:qf.detail-modal :configKey="$configKey ?? ''" />
    <livewire:qf.form-modal :configKey="$configKey ?? ''" />
    <livewire:qf.import-modal :configKey="$configKey ?? ''" />
    <livewire:qf.export-modal :configKey="$configKey ?? ''" />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
    window.dispatchEvent(new CustomEvent('menu-type-changed', { detail: type }));
});


Livewire.on('doReload', () => {
    window.location.reload();
});

    </script>
    @stack('scripts')
</body>
</html>