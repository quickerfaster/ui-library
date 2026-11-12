<div>
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" data-bs-backdrop="static"
         wire:ignore.self wire:key="crop-modal-{{ $modalId }}">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-light">
                    <h5 class="modal-title">
                        <i class="fas fa-crop-alt me-2"></i> Crop Image
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" wire:click="closeModal"></button>
                </div>
                <div class="modal-body">
                    <div class="img-container" style="max-height: 70vh; overflow: hidden;"
                         data-field-name="{{ $fieldName }}" data-aspect-ratio="{{ $aspectRatio }}">
                        <img id="crop-image" src="{{ $imageUrl }}" alt="Crop Image" style="max-width: 100%;">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" wire:click="closeModal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="save-crop-btn">Save Crop</button>
                </div>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:init', function () {
        let cropper = null;

        function getModal() {
            return document.getElementById('{{ $modalId }}');
        }

        function getContainer() {
            var modal = getModal();
            return modal ? modal.querySelector('.img-container') : null;
        }

        function initCropper() {
            var container = getContainer();
            var image = document.getElementById('crop-image');
            if (!container || !image) return;

            if (cropper) cropper.destroy();

            var aspectRatio = container.getAttribute('data-aspect-ratio') || '1/1';
            aspectRatio = parseFloat(aspectRatio);
            if (isNaN(aspectRatio) || aspectRatio <= 0) aspectRatio = NaN;

            cropper = new Cropper(image, {
                aspectRatio: aspectRatio,
                viewMode: 1,
                autoCropArea: 0.8,
                responsive: true,
            });
        }

        function destroyCropper() {
            if (cropper) {
                cropper.destroy();
                cropper = null;
            }
        }

        // Delegated listeners survive Livewire DOM morphs.
        document.addEventListener('shown.bs.modal', function (e) {
            if (e.target && e.target.id === '{{ $modalId }}') {
                initCropper();
            }
        });

        document.addEventListener('hidden.bs.modal', function (e) {
            if (e.target && e.target.id === '{{ $modalId }}') {
                destroyCropper();
                // Defensive cleanup so a stale backdrop / scroll lock never
                // lingers after the crop modal closes.
                document.querySelectorAll('.modal-backdrop').forEach(function (el) { el.remove(); });
                document.body.classList.remove('modal-open');
                document.body.style.overflow = '';
            }
        });

        // Re-initialize cropper after Livewire updates the modal body while open.
        Livewire.hook('morph.updated', function () {
            var modal = getModal();
            if (modal && modal.classList.contains('show')) {
                initCropper();
            }
        });

        // Delegated save-crop handler.
        document.addEventListener('click', function (e) {
            if (!e.target.closest('#save-crop-btn')) return;
            if (!cropper) return;

            var container = getContainer();
            var modal = getModal();
            if (!container || !modal) return;

            var canvas = cropper.getCroppedCanvas();
            var croppedImageData = canvas.toDataURL('image/jpeg', 0.9);
            var fieldName = container.getAttribute('data-field-name');

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

            var bsModal = bootstrap.Modal.getInstance(modal);
            if (bsModal) bsModal.hide();
        });
    });
</script>
@endpush
