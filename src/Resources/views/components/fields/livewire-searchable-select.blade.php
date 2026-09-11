@props(['field', 'value' => null, 'name', 'label', 'multiple' => false, 'placeholder' => 'Search...'])

@php
    $fieldName = $field->getName();
    $selectedLabels = $selectedLabels ?? [];
    $searchQuery = $searchQuery ?? '';
    $results = $results ?? [];
@endphp

<div class="mb-3" x-data="{ search: '' }">
    <label class="form-label">{{ $label }}</label>

    {{-- Selected badges --}}
    <div class="selected-items mb-2">
        @foreach ($selectedLabels as $id => $labelText)
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
        x-model="search"
        x-on:input.debounce.300ms="$wire.set('searches.{{ $fieldName }}', search)" />

    {{-- Dropdown results --}}
    @if (!empty($searchQuery) && !empty($results))
        <ul wire:key="search-results-{{ $fieldName }}" class="list-group mt-1"
            style="max-height: 200px; overflow-y: auto;">
            @foreach ($results as $id => $resultLabel)
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
            !empty($searchQuery) &&
            empty($results) &&
            strlen($searchQuery) >= 2)
        <div class="mt-1">
            <button type="button" class="btn btn-sm btn-link text-primary p-0"
                wire:click="createAndSelectOption('{{ $fieldName }}', '{{ $searchQuery }}')">
                + Create "{{ $searchQuery }}"
            </button>
        </div>
    @endif

    {{-- Validation error --}}
    @error($fieldName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>
