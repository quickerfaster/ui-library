@props(['field', 'value', 'name', 'label', 'customAttributes' => []])

<div class="mb-3 form-check">
    <input type="checkbox"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-check-input ' . ($errors->has($name) ? 'is-invalid' : ''),
               'id' => $name,
               'name' => $name,
               'wire:model' => "fields.$name",
               'value' => 1,
           ]) }}
           {{ $value ? 'checked' : '' }}
    >
    <label class="form-check-label" for="{{ $name }}">
        {{ $label }}
    </label>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
