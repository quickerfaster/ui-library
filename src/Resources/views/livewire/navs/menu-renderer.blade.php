<div>
    @if ($menuType === 'horizontal')
        <livewire:qf.horizontal-context-menu
            :items="$contextItems[$activeContext] ?? []"
            :position="$contextMenuPosition"
            :allowTypeSwitch="$allowMenuTypeSwitch"
            wire:key="horizontal-menu-{{ $counter }}"
        />
    @else
        <livewire:qf.sidebar
            :items="$contextItems[$activeContext] ?? []"
            :state="$sidebarState"
            :headerItems="$sharedHeaderItems"
            :footerItems="$sharedFooterItems"
            :allowTypeSwitch="$allowMenuTypeSwitch"
            wire:key="sidebar-menu-{{ $counter }}"
        />
    @endif
</div>