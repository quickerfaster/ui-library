@props(['field', 'value', 'name', 'label', 'accept' => 'image/*', 'multiple' => false, 'customAttributes' => []])

<div class="mb-3"
     wire:ignore
     x-data="{
        previewUrl: {{ $value && !is_object($value) ? json_encode(asset('storage/' . $value)) : 'null' }},
        
        init() {
            // Listen specifically for this field's update
            window.addEventListener('cropped-image-updated', (event) => {
                let data = event.detail;
                
                // Livewire 3 sometimes wraps data in an array
                if (Array.isArray(data)) { data = data[0]; }

                if (data && data.fieldName === '{{ $name }}') {
                    this.previewUrl = data.imageDataUrl;
                    console.log('Preview updated for: {{ $name }}');
                }
            });
        }
     }"
     wire:key="field-container-{{ $name }}">
    
    <label class="form-label">{{ $label }}</label>

    <!-- Preview Area -->
    <template x-if="previewUrl">
        <div class="mb-2">
            <img :src="previewUrl" class="img-thumbnail" style="max-height: 150px; display: block;">
            <div class="mt-2">
                <button type="button" class="btn btn-sm btn-outline-secondary"
                    @click="$dispatch('openCropModal', { 
                        payload: {
                            imageUrl: previewUrl, 
                            fieldName: '{{ $name }}',
                            aspectRatio: '{{ $field->definition['aspect_ratio'] ?? '1/1' }}'
                        }
                    })">
                    <i class="fas fa-crop-alt"></i> Crop Again
                </button>
            </div>
        </div>
    </template>

    <input type="file"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control',
               'id' => $name,
               'wire:model' => 'fields.' . $name,
               'accept' => $accept,
           ]) }}
           x-on:change="
                const file = $el.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = (e) => { previewUrl = e.target.result; };
                    reader.readAsDataURL(file);
                }
           "
    >
</div>
