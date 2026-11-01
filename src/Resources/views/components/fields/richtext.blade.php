@props(['field', 'value', 'name', 'label', 'customAttributes' => []])

<div class="mb-3" wire:ignore
    x-data="{
        fieldName: '{{ $name }}',
        fullscreen: false,
        init() {
            const trixEditor = document.getElementById('{{ $name }}_trix');
            const hiddenInput = document.getElementById('{{ $name }}');

            if (!trixEditor) return;

            if (hiddenInput && hiddenInput.value) {
                trixEditor.editor.loadHTML(hiddenInput.value);
            }

            trixEditor.addEventListener('trix-change', () => {
                if (hiddenInput) hiddenInput.value = trixEditor.value;
                $wire.set('fields.' + this.fieldName, trixEditor.value);
            });

            document.addEventListener('keydown', (e) => {
                if (e.key === 'Escape' && this.fullscreen) {
                    this.closeFullscreen();
                }
            });
        },
        openFullscreen() {
            this.fullscreen = true;
        },
        closeFullscreen() {
            this.fullscreen = false;
            // Re-focus the editor after returning to drawer
            this.$nextTick(() => {
                const editor = document.getElementById('{{ $name }}_trix');
                if (editor) editor.focus();
            });
        }
    }">

    <div class="d-flex justify-content-between align-items-center mb-1">
        <label for="{{ $name }}_trix" class="form-label mb-0">{{ $label }}</label>
        <button type="button" class="btn btn-sm btn-outline-secondary" x-on:click="openFullscreen()"
            title="Open in fullscreen editor">
            <i class="fas fa-expand me-1"></i> Fullscreen
        </button>
    </div>

    <input id="{{ $name }}" name="{{ $name }}" type="hidden" value="{{ old($name, $value) }}">

    {{-- Trix editor — expands to fullscreen via CSS when fullscreen is true --}}
    <div x-bind:class="fullscreen
        ? 'position-fixed top-0 start-0 w-100 h-100 p-4 bg-white'
        : ''"
        x-bind:style="fullscreen ? 'z-index: 99999; overflow-y: auto;' : ''">

        {{-- Toolbar — only visible in fullscreen --}}
        <template x-if="fullscreen">
            <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
                <h5 class="mb-0">
                    <i class="fas fa-edit me-2 text-primary"></i>{{ $label }}
                </h5>
                <button type="button" class="btn btn-primary btn-sm" x-on:click="closeFullscreen()">
                    <i class="fas fa-check me-1"></i> Done
                </button>
            </div>
        </template>

        <trix-editor
            id="{{ $name }}_trix"
            input="{{ $name }}"
            {{ $attributes->merge($customAttributes)->merge([
                'class' => 'trix-content ' . ($errors->has($name) ? 'is-invalid' : ''),
            ]) }}
            x-bind:style="fullscreen ? 'min-height: calc(100vh - 120px);' : 'min-height: 200px;'"
        ></trix-editor>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>