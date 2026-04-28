<textarea name="{{ $name }}"
    wire:model.blur="{{ $wireModel }}"
    class="form-control form-control-sm"
    rows="2"
    @foreach($customAttributes ?? [] as $key => $value)
        {{ $key }}="{{ $value }}"
    @endforeach
></textarea>