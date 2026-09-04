@props(['field', 'value', 'name', 'label', 'customAttributes' => [], 'calendarConfig' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <input type="text"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control datepicker ' . ($errors->has($name) ? 'is-invalid' : ''),
               'id' => $name,
               'name' => $name,
               'wire:model' => "fields.$name",
               'value' => old($name, $value instanceof \Carbon\Carbon ? $value->format('Y-m-d') : $value),
               'data-datepicker' => '',
               'data-calendar-config' => !empty($calendarConfig) ? json_encode($calendarConfig) : '',
           ]) }}
    >
    @error($name)
        <div class="invalid-feedback">{{ $message }}</div>
    @enderror
</div>
