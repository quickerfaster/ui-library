@props(['field', 'name', 'label', 'customAttributes' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input type="password"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
               'id' => $name,
               'name' => $name,
               'wire:model' => "fields.$name",
               'autocomplete' => 'new-password',
           ]) }}
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>