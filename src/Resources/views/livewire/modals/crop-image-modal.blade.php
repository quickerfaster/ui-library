<div class="modal fade" id="{{ $modalId }}" tabindex="-1" data-bs-backdrop="static"
     data-field-name="{{ $fieldName }}" data-aspect-ratio="{{ $aspectRatio }}"
     wire:key="crop-modal-{{ $modalId }}">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-light">
                <h5 class="modal-title">
                    <i class="fas fa-crop-alt me-2"></i> Crop Image
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="img-container" style="max-height: 70vh; overflow: hidden;">
                    <img id="crop-image" src="{{ $imageUrl }}" alt="Crop Image" style="max-width: 100%;">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="save-crop-btn">Save Crop</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        let cropper = null;
        let modalElement = document.getElementById('{{ $modalId }}');
        
        // Function to initialize or reinitialize cropper
        function initCropper() {
            const image = document.getElementById('crop-image');
            if (!image) return;
            if (cropper) cropper.destroy();
            let aspectRatio = modalElement.getAttribute('data-aspect-ratio') || '1/1';
            aspectRatio = parseFloat(aspectRatio);
            cropper = new Cropper(image, {
                aspectRatio: aspectRatio,
                viewMode: 1,
                autoCropArea: 0.8,
                responsive: true,
            });
        }
        
        // Listen for modal show event
        modalElement.addEventListener('shown.bs.modal', function () {
            initCropper();
        });
        
        modalElement.addEventListener('hidden.bs.modal', function () {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        });
        
        // Re-initialize cropper after Livewire updates (e.g., when fieldName changes)
        Livewire.hook('morph.updated', () => {
            if (modalElement && modalElement.classList.contains('show')) {
                initCropper();
            }
        });
        
        document.getElementById('save-crop-btn').addEventListener('click', function () {
            if (cropper) {
                const canvas = cropper.getCroppedCanvas();
                const croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
                const fieldName = modalElement.getAttribute('data-field-name');
                
                if (!fieldName) {
                    console.error('No field name found');
                    return;
                }
                
                Livewire.dispatch('cropCompleted', { 
                    payload: {
                        croppedImageData: croppedImageData, 
                        fieldName: fieldName 
                    }
                });
                
                const bsModal = bootstrap.Modal.getInstance(modalElement);
                if (bsModal) bsModal.hide();
            }
        });
    });
</script>
@endpush