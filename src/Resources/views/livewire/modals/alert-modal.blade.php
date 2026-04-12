<div wire:ignore.self>
    <div class="modal fade" id="{{ $modalId }}" tabindex="-1" aria-hidden="true"
         wire:ignore.self>
        <div class="modal-dialog {{ $size ? 'modal-' . $size : '' }} modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    @if($icon)
                        <i class="{{ $icon }} me-2"></i>
                    @endif
                    <h5 class="modal-title">{{ $title }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>{{ $message }}</p>

                    @if($type === 'prompt')
                        <div class="mb-3">
                            @if($inputLabel)
                                <label class="form-label">{{ $inputLabel }}</label>
                            @endif
                            <input type="{{ $inputType }}"
                                   wire:model.live="inputValue"
                                   class="form-control @error('inputValue') is-invalid @enderror"
                                   placeholder="{{ $inputPlaceholder }}">
                            @error('inputValue')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            @if($validationError)
                                <div class="text-danger small mt-1">{{ $validationError }}</div>
                            @endif
                        </div>
                    @endif
                </div>
                <div class="modal-footer">
                    @if($cancelText)
                        <button type="button" class="btn btn-secondary" wire:click="cancel">{{ $cancelText }}</button>
                    @endif
                    <button type="button" class="btn btn-primary"
                            wire:click="confirm"
                            @if($type === 'prompt' && $validationRules && $errors->has('inputValue')) disabled @endif>
                        {{ $confirmText }}
                    </button>
                </div>
            </div>
        </div>
    </div>

    @script
    <script>
        // Auto‑close handling
        Livewire.on('open-bs-modal', (event) => { //*
            const modalId = event[0].modalId;
            const autoClose = event[0].autoClose; 

            if (modalId === '{{ $modalId }}' && autoClose) {
                setTimeout(() => {
                    Livewire.dispatch('closeModal');
                }, {{ $autoCloseDelay }} * 1000);
            }
        });
    </script>
    @endscript
</div>