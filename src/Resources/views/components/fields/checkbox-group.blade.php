@props(['field', 'value', 'name', 'label', 'options' => [], 'customAttributes' => []])

<div class="mb-3">
    <label class="form-label">{{ $label }}</label>
    @foreach($options as $key => $option)
        <div class="form-check">
            <input type="checkbox"
                   {{ $attributes->merge($customAttributes)->class('form-check-input') }}
                   id="{{ $name }}_{{ $loop->index }}"
                   wire:model="fields.{{ $name }}"
                   value="{{ $key }}"
            >
            <label class="form-check-label" for="{{ $name }}_{{ $loop->index }}">
                {{ $option }}
            </label>
        </div>
    @endforeach
    @error($name)
        <div class="text-danger small">{{ $message }}</div>
    @enderror
    @error($name . '.*')
        <div class="text-danger small">{{ $message }}</div>
    @enderror
</div>