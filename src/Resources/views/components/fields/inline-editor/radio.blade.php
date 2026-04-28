@foreach($options as $key => $label)
    <div class="form-check form-check-inline">
        <input type="radio"
            wire:model.live="{{ $wireModel }}"
            value="{{ $key }}"
            class="form-check-input"
            @foreach($customAttributes ?? [] as $attrKey => $attrValue)
                {{ $attrKey }}="{{ $attrValue }}"
            @endforeach
        >
        <label class="form-check-label">{{ $label }}</label>
    </div>
@endforeach