<div class="modal fade" id="{{ $modalId }}" tabindex="-1" wire:ignore.self data-bs-backdrop="static">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fas fa-file-alt me-2"></i> Preview: {{ $fileName }}
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="min-height: 500px;">
                @if($showModal && $fileUrl)
                    <livewire:qf.document-preview :fileUrl="$fileUrl" :key="$fileUrl" />
                @endif
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="{{ $fileUrl }}" class="btn btn-primary" download>
                    <i class="fas fa-download me-1"></i> Download
                </a>
            </div>
        </div>
    </div>
</div>