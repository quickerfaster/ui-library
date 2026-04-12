@props(['field', 'value', 'name', 'label', 'customAttributes' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    
    <div class="input-group">
        <input type="text"
               {{ $attributes->merge($customAttributes)->merge([
                   'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
                   'id' => $name,
                   'name' => $name,
                   'wire:model' => "fields.$name",
                   'value' => old($name, $value)
               ]) }}
               aria-describedby="button-addon-{{ $name }}"
        >

        <button type="button"
                class="btn btn-outline-primary mb-0"
                id="button-addon-{{ $name }}"
                wire:click="generateField('{{ $name }}')"
                title="Generate value">
            <i class="fas fa-magic"></i> Generate
        </button>
    </div>

    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>
