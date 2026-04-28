@php
    $errorKey = "row_$rowId"; // you need to pass $rowId to the view
    $hasError = isset($this->fieldErrors[$errorKey][$fieldName]);
    $errorMessage = $hasError ? $this->fieldErrors[$errorKey][$fieldName] : '';
@endphp

<div style="width: 150px">
    <input type="text"
        name="{{ $name }}"
        wire:model.live="{{ $wireModel }}"
        class="form-control form-control-sm {{ $hasError ? 'is-invalid' : '' }}"
        @foreach($customAttributes ?? [] as $key => $val)
            {{ $key }}="{{ $val }}"
        @endforeach
    >
    @if($hasError)
        <div class="invalid-feedback d-block" style="font-size: 0.75rem;">
            {{ $errorMessage }}
        </div>
    @endif
</div>