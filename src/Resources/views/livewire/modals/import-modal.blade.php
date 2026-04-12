<div wire:ignore.self>
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Import Data</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($showModal && $configKey)
                        <livewire:qf.import-form
                            :configKey="$configKey"
                            :modalId="$modalId"
                            wire:key="import-{{ $configKey }}-{{ time() }}"
                        />
                    @endif
                </div>
                
            </div>
        </div>
    </div>
</div>