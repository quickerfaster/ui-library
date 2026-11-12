@props(['field', 'value', 'name', 'label', 'accept' => '*', 'multiple' => false, 'customAttributes' => [], 'isImage' => false])

<div class="mb-3"
     x-data="{
        previewUrl: null,
        existingFileUrl: {{ $value && !is_object($value) ? json_encode(asset('storage/' . $value)) : 'null' }},
        fileName: {{ $value && !is_object($value) ? json_encode(basename($value)) : 'null' }},
        tempFileDataUrl: null,
        tempFileName: null,
        tempFileType: null,
        tempFile: null,
        init() {
            if (this.existingFileUrl && {{ $isImage ? 'true' : 'false' }}) {
                this.previewUrl = this.existingFileUrl;
            }
        },
        openPreview() {
            // For a freshly selected document, regenerate the blob URL on each
            // open. Some browsers cache a blob URL in the PDF viewer, so
            // reusing the same URL leaves a blank/gray frame on reopen.
            if (this.tempFile && !{{ $isImage ? 'true' : 'false' }}) {
                if (this.tempFileDataUrl && this.tempFileDataUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(this.tempFileDataUrl);
                }
                this.tempFileDataUrl = URL.createObjectURL(this.tempFile);
            }

            // Prefer inline image data URL, then temporary document blob URL,
            // then an already-saved file URL.
            const url = this.previewUrl || this.tempFileDataUrl || this.existingFileUrl;
            const name = this.tempFileName || this.fileName;
            if (url) {
                Livewire.dispatch('openDocumentPreview', {
                    payload: {
                        fileUrl: url,
                        fileName: name,
                        mimeType: this.tempFileType || null,
                    }
                });
            }
        },
        handleFileSelect(file) {
            if (!file) return;
            this.tempFile = file;
            this.tempFileName = file.name;
            this.tempFileType = file.type || null;

            if ({{ $isImage ? 'true' : 'false' }} && file.type.startsWith('image/')) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    this.previewUrl = e.target.result;
                };
                reader.readAsDataURL(file);
            } else if (!{{ $isImage ? 'true' : 'false' }}) {
                // Revoke the previous blob URL to avoid leaking memory.
                if (this.tempFileDataUrl && this.tempFileDataUrl.startsWith('blob:')) {
                    URL.revokeObjectURL(this.tempFileDataUrl);
                }
                // Use a blob URL instead of a base64 data URL so large files
                // (e.g. PDFs) stay under Livewire's payload size limit.
                this.tempFileDataUrl = URL.createObjectURL(file);
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
               'wire:model' => 'fileUploads.' . $name,
               'accept' => $accept,
               $multiple ? 'multiple' : '',
           ]) }}
           x-on:change="handleFileSelect($el.files[0])"
    >


    @error($name)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror

</div>