<select name="{{ $name }}"
    wire:model.live="{{ $wireModel }}"
    class="form-select form-select-sm"
    @foreach($customAttributes ?? [] as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
>
    <option value="">{{ $placeholder ?? '-- Select --' }}</option>
    @foreach($options as $key => $label)
        <option value="{{ $key }}">{{ $label }}</option>
    @endforeach
</select>