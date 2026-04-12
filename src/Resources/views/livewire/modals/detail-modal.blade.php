<div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true" wire:ignore.self>
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header border-0 pb-0">
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"
                    wire:click="closeModal"></button>
            </div>
            <div class="modal-body pt-0">
                @if ($showModal && $configKey && $recordId)

                    @if ($customComponent)
                        @livewire($customComponent, [
                            'configKey' => $configKey,
                            'recordId' => $recordId,
                            'recordIds' => $recordIds,
                            'currentIndex' => $currentIndex,
                        ])
                    @else
                        @livewire('qf.data-table-detail', ['configKey' => $configKey, 'recordId' => $recordId, 'inline' => true])
                    @endif

                @endif
            </div>
        </div>
    </div>
</div>
