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

    {{-- Desktop context area – server renders the correct menu type --}}
{{-- Desktop context area --}}
<div class="d-none d-md-block"
     x-data="{ layoutType: localStorage.getItem('contextMenuType') || '{{ $contextMenuType }}' }"
     x-on:menu-type-changed.window="layoutType = $event.detail; localStorage.setItem('contextMenuType', $event.detail)">
    
    <div :class="layoutType === 'sidebar' ? 'd-flex' : ''" class="main-content-wrapper">
        {{-- No :menuType prop – the component will read from session --}}
        <livewire:qf.menu-renderer
            :moduleName="$moduleName"
            :activeContext="$activeContext"
            :contextItems="$contextItems"
            :contextMenuPosition="$contextMenuPosition"
            :allowMenuTypeSwitch="$allowMenuTypeSwitch"
            :sidebarState="$sidebarState"
            :sharedHeaderItems="$sharedHeaderItems"
            :sharedFooterItems="$sharedFooterItems"
            wire:key="menu-renderer-{{ $moduleName }}"
        />

        <main :class="layoutType === 'sidebar' ? 'flex-grow-1' : ''" class="p-4 overflow-auto">
            <h1>xxxx</h1>
            {{ $slot }}
        </main>
    </div>
</div>







    {{-- Bottom Bar (mobile) --}}
    <livewire:qf.bottom-bar
        :items="$contextItems[$activeContext] ?? []"
        :activeContext="$activeContext"
        wire:key="bottom-bar-{{ $moduleName }}"
    />

    {{-- Modals --}}
    <livewire:qf.alert-modal :configKey="$configKey ?? ''" />
    <livewire:qf.detail-modal :configKey="$configKey ?? ''" />
    <livewire:qf.form-modal :configKey="$configKey ?? ''" />
    <livewire:qf.import-modal :configKey="$configKey ?? ''" />
    <livewire:qf.export-modal :configKey="$configKey ?? ''" />

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="{{ asset('assets/js/quicker-faster.js') }}"></script>
    @livewireScripts
    @stack('scripts')

    <script>
Livewire.on('saveMenuType', (type) => {
    localStorage.setItem('contextMenuType', type);
});

Livewire.on('saveSidebarState', (state) => {
    localStorage.setItem('sidebarState', state);
});

// Restore sidebar state on page load
document.addEventListener('DOMContentLoaded', () => {
    const savedSidebarState = localStorage.getItem('sidebarState');
    if (savedSidebarState) {
        Livewire.dispatch('sidebarStateChanged', savedSidebarState);
    }
});

Livewire.on('menu-type-changed', (type) => {
    window.dispatchEvent(new CustomEvent('menu-type-changed', { detail: type }));
});

        // (Optional) Read menu type from localStorage on first visit to set session
        // But we already rely on session – you can also sync session with localStorage.
    </script>
</body>
</html>