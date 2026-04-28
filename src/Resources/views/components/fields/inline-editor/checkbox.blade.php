<input type="checkbox"
    wire:model.live="{{ $wireModel }}"
    value="1"
    class="form-check-input"
    @foreach($customAttributes ?? [] as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
>