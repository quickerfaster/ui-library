@props(['field', 'value', 'name', 'label', 'options' => [], 'multiple' => false, 'placeholder' => '-- Select --', 'customAttributes' => []])
<!-- VALUE: {{ json_encode($value) }} -->

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <select {{ $attributes->merge($customAttributes)->merge([
        'class' => 'form-select ' . ($errors->has($name) ? 'is-invalid' : ''),
        'id' => $name,
        'name' => $name . ($multiple ? '[]' : ''),
        'wire:model' => "fields.{$name}", 
        $multiple ? 'multiple' : '',
    ]) }}>
        @if(!$multiple && $placeholder)
            <option value="">{{ $placeholder }}</option>
        @endif
        @foreach($options as $key => $option)
            <option value="{{ $key }}" {{ (is_array($value) && in_array($key, $value)) || $value == $key ? 'selected' : '' }}>
                {{ $option }}
            </option>
        @endforeach
    </select>
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
    @error($name . '.*')
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>