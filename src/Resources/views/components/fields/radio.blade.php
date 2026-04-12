@props(['field', 'value', 'name', 'label', 'options' => [], 'customAttributes' => []])

<div class="mb-3">
    <label class="form-label">{{ $label }}</label>
    @foreach($options as $key => $option)
        <div class="form-check">
            <input type="radio"
                   {{ $attributes->merge($customAttributes)->merge([
                       'class' => 'form-check-input ' . ($errors->has($name) ? 'is-invalid' : ''),
                       'id' => $name . '_' . $loop->index,
                       'name' => $name,
                       'wire:model' => "fields.$name",
                       'value' => $key,
                   ]) }}
                   {{ $value == $key ? 'checked' : '' }}
            >
            <label class="form-check-label" for="{{ $name . '_' . $loop->index }}">
                {{ $option }}
            </label>
        </div>
    @endforeach
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
