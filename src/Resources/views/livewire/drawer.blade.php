<div wire:ignore.self>
    <div class="offcanvas offcanvas-end" tabindex="-1" id="globalDrawer" wire:ignore.self>
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ $title }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body">
            @if($isOpen && $component)
                @livewire($component, $componentParams, key($component . '-' . md5(json_encode($componentParams))))
            @endif
        </div>
    </div>
</div>