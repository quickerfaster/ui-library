@props(['field', 'value', 'name', 'label', 'accept' => '*', 'multiple' => false, 'customAttributes' => [], 'isImage' => false])

<div class="mb-3"
     x-data="{
        previewUrl: null,
        existingFileUrl: {{ $value && !is_object($value) ? json_encode(asset('storage/' . $value)) : 'null' }},
        fileName: {{ $value && !is_object($value) ? json_encode(basename($value)) : 'null' }},
        tempFileDataUrl: null,
        tempFileName: null,
        init() {
            if (this.existingFileUrl && {{ $isImage ? 'true' : 'false' }}) {
                this.previewUrl = this.existingFileUrl;
            }
        },
        openPreview() {
            // Use temporary data URL if available (new file), otherwise existing URL
            const url = this.tempFileDataUrl || this.existingFileUrl;
            const name = this.tempFileName || this.fileName;
            if (url) {
                Livewire.dispatch('openDocumentPreview', {
                    payload: {
                        fileUrl: url,
                        fileName: name
                    }
                });
            }
        },
        handleFileSelect(file) {
            if (!file) return;
            this.tempFileName = file.name;
            
            if ({{ $isImage ? 'true' : 'false' }} && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (!{{ $isImage ? 'true' : 'false' }}) {
                // For documents, store data URL for preview modal (but don't show inline)
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.tempFileDataUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
     }"
>
    <label class="form-label">{{ $label }}</label>

    {{-- Preview area (only for images) --}}
    <template x-if="previewUrl">
        <div class="mb-2">
            <img :src="previewUrl" class="img-thumbnail" style="max-height: 150px;">
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary" @click="openPreview">
                    <i class="fas fa-eye"></i> Preview
                </button>
            </div>
        </div>
    </template>

    {{-- Document info + preview button (existing or newly selected) --}}
    <template x-if="(existingFileUrl || tempFileDataUrl) && !previewUrl">
        <div class="mb-2 d-flex align-items-center gap-2">
            <i class="fas fa-file-alt text-secondary"></i>
            <span class="small" x-text="tempFileName || fileName"></span>
            <button type="button" class="btn btn-sm btn-outline-secondary" @click="openPreview">
                <i class="fas fa-eye"></i> Preview
            </button>
        </div>
    </template>

    {{-- File input --}}
    <input type="file"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control',
               'id' => $name,
               'wire:model' => 'fields.' . $name,
               'accept' => $accept,
               $multiple ? 'multiple' : '',
           ]) }}
           x-on:change="handleFileSelect($el.files[0])"
    >
</div>