<div>
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Export Progress</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($showModal && $configKey)
                        <livewire:qf.export-progress 
                            :config-key="$configKey" 
                            :export-params="$exportParams" 
                            :key="'export-progress-' . $configKey" 
                            wire:key="export-progress-{{ $configKey }}"
                        />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>