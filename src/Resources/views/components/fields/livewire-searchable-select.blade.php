@props([
    'field',
    'value' => null,
    'name',
    'label',
    'multiple' => false,
    'placeholder' => 'Search...',
])

@php
    $fieldName = $field->getName();
@endphp

<div wire:ignore.self class="mb-3">
    <label class="form-label">{{ $label }}</label>

    {{-- Selected badges --}}
    <div class="selected-items mb-2">
        @foreach($this->selectedLabels[$fieldName] ?? [] as $id => $labelText)
            <span class="badge bg-primary me-1">
                {{ $labelText }}
                <button type="button" class="btn-close btn-close-white ms-1" wire:click="removeSelected('{{ $fieldName }}', '{{ $id }}')" style="font-size: 0.5rem;"></button>
            </span>
        @endforeach
    </div>

    {{-- Search input --}}
    <input
        type="text"
        class="form-control @error($fieldName) is-invalid @enderror"
        placeholder="{{ $placeholder }}"
        wire:model.live.debounce.300ms="searches.{{ $fieldName }}"
    />

    {{-- Dropdown results --}}
    @if(!empty($this->searches[$fieldName]) && !empty($this->searchResults[$fieldName]))
   
        <ul class="list-group mt-1" style="max-height: 200px; overflow-y: auto;">
            @foreach($this->searchResults[$fieldName] as $id => $resultLabel)
                <li class="list-group-item list-group-item-action" wire:click="selectOption('{{ $fieldName }}', '{{ $id }}', '{{ $resultLabel }}')" style="cursor: pointer;">
                    {{ $resultLabel }}
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Validation error --}}
    @error($fieldName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>