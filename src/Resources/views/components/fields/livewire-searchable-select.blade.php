@props(['field', 'value' => null, 'name', 'label', 'multiple' => false, 'placeholder' => 'Search...'])

@php
    $fieldName = $field->getName();
    $isPreset = method_exists($this, 'isPresetField') && $this->isPresetField($fieldName);
@endphp

<div wire:ignore.self class="mb-3">
    <label class="form-label">{{ $label }}</label>

    @if($isPreset)
        {{-- Read-only preset display: badge without remove button, no search input --}}
        <div class="selected-items mb-2">
            @foreach ($this->selectedLabels[$fieldName] ?? [] as $id => $labelText)
                <span class="badge bg-secondary me-1" style="font-size: 0.9em; padding: 0.5em 0.75em;">
                    {{ $labelText }}
                </span>
            @endforeach
        </div>
        <input type="hidden" name="{{ $fieldName }}" wire:model="fields.{{ $fieldName }}" />
    @else
        {{-- Selected badges --}}
        <div class="selected-items mb-2">
            @foreach ($this->selectedLabels[$fieldName] ?? [] as $id => $labelText)
                <span class="badge bg-primary me-1">
                    {{ $labelText }}
                    <button type="button" class="btn-close btn-close-white ms-1"
                        wire:click="removeSelected('{{ $fieldName }}', '{{ $id }}')"
                        style="font-size: 0.5rem;"></button>
                </span>
            @endforeach
        </div>

        {{-- Search input --}}
        <input type="text" class="form-control @error($fieldName) is-invalid @enderror" placeholder="{{ $placeholder }}"
            wire:model.live.debounce.300ms="searches.{{ $fieldName }}" />

        {{-- Dropdown results --}}
        @if (!empty($this->searches[$fieldName]) && !empty($this->searchResults[$fieldName]))
            <ul class="list-group mt-1" style="max-height: 200px; overflow-y: auto;">
                @foreach ($this->searchResults[$fieldName] as $id => $resultLabel)
                    <li class="list-group-item list-group-item-action"
                        wire:click="selectOption('{{ $fieldName }}', '{{ $id }}', '{{ $resultLabel }}')"
                        style="cursor: pointer;">
                        {{ $resultLabel }}
                    </li>
                @endforeach
            </ul>
        @endif


        {{-- CREATE NEW OPTION BUTTON --}}
        @if (
            $field->canInlineAdd() &&
                !empty($this->searches[$fieldName]) &&
                empty($this->searchResults[$fieldName]) &&
                strlen($this->searches[$fieldName]) >= 2)
            <div class="mt-1">
                <button type="button" class="btn btn-sm btn-link text-primary p-0"
                    wire:click="createAndSelectOption('{{ $fieldName }}', '{{ $this->searches[$fieldName] }}')">
                    + Create "{{ $this->searches[$fieldName] }}"
                </button>
            </div>
        @endif
    @endif

    {{-- Validation error --}}
    @error($fieldName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
