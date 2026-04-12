@props(['field', 'value', 'name', 'label', 'rows' => 3, 'customAttributes' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <textarea {{ $attributes->merge($customAttributes)->merge([
        'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
        'id' => $name,
        'name' => $name,
        'wire:model' => "fields.$name",
        'rows' => $rows,
    ]) }}>{{ old($name, $value) }}</textarea>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
