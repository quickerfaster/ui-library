<input type="date"
    name="{{ $name }}"
    wire:model.blur="{{ $wireModel }}"
    class="form-control form-control-sm"
    @foreach($customAttributes ?? [] as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
>