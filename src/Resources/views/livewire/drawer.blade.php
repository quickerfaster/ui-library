<div>
    <div class="offcanvas offcanvas-end {{ $drawerConfig['size'] ?? '' === 'lg' ? 'offcanvas-lg' : '' }}"
         tabindex="-1"
         id="globalDrawer"
         wire:ignore.self>
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">
                @if(isset($drawerConfig['icon']))
                    <i class="{{ $drawerConfig['icon'] }} me-2"></i>
                @endif
                {{ $drawerConfig['label'] ?? '' }}
            </h5>
            <button type="button" class="btn-close bg-primary" data-bs-dismiss="offcanvas" aria-label="Close">
                
            </button>
        </div>
        <div class="offcanvas-body">
            @if($isOpen && $currentDrawerKey && isset($drawerConfig['component']))
                @livewire($drawerConfig['component'], $drawerConfig['params'] ?? [], key($currentDrawerKey))
            @endif
        </div>
    </div>
</div>

