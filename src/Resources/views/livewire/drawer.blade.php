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

@push('scripts')
<script>
    function initDrawer() {
        const drawerElement = document.getElementById('globalDrawer');
        if (!drawerElement) {
            console.warn('Drawer element not found, will retry on next navigation');
            return;
        }

        let bsDrawer = null;

        function getDrawer() {
            if (bsDrawer) {
                try { bsDrawer.dispose(); } catch(e) {}
            }
            bsDrawer = new bootstrap.Offcanvas(drawerElement, {
                backdrop: true,
                keyboard: true,
                scroll: false
            });
            return bsDrawer;
        }

        // Remove old listener to avoid duplicates
        drawerElement.removeEventListener('hidden.bs.offcanvas', handleHidden);
        drawerElement.addEventListener('hidden.bs.offcanvas', handleHidden);
        function handleHidden() {
            Livewire.dispatch('closeDrawer');
        }

        // Listen for drawerOpened event
        Livewire.on('drawerOpened', () => {
            console.log('drawerOpened received, showing offcanvas');
            getDrawer().show();
        });
    }

    // Initialize on page load
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initDrawer);
    } else {
        initDrawer();
    }
    // Re-initialize after Livewire navigations
    document.addEventListener('livewire:navigated', initDrawer);
</script>
@endpush