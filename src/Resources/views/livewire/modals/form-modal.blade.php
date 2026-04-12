<div wire:ignore.self>
    <!-- Bootstrap Modal -->
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-labelledby="formModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="formModalLabel">
                        {{ $recordId ? 'Edit' : 'Add' }} Record
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    @if($showModal && $configKey)
                        <livewire:qf.data-table-form
                            :configKey="$configKey"
                            :recordId="$recordId"
                            :inline="false"
                            :modalId="$modalId"
                            :prefilledData="$prefilledData"
                            wire:key="form-{{ $configKey }}-{{ $recordId ?? 'new' }}"
                            :allowedGroups="$allowedGroups"
                        />
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>


