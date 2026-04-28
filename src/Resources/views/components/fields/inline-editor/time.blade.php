<input type="time"
    name="{{ $name }}"
    wire:model.blur="{{ $wireModel }}"
    class="form-control form-control-sm"
    @foreach($customAttributes ?? [] as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
>