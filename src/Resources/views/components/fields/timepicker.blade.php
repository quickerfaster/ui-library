@props(['field', 'value', 'name', 'label', 'customAttributes' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input type="time"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
               'id' => $name,
               'name' => $name,
               'wire:model' => "fields.$name",
               'value' => old($name, $value instanceof \Carbon\Carbon ? $value->format('H:i') : $value)
           ]) }}
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
