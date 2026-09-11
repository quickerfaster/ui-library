{{--
    Searchable select field.

    This partial is rendered with @include() from the parent Livewire component
    blade so that every wire:model / wire:click directive below is compiled as
    part of the component's own render cycle. Rendering it through
    View::make()->render() (i.e. renderForm()) produces raw HTML after Blade
    compilation, which Livewire cannot attach directives to.

    Supported variables:
        $fieldName       (string)  field key, e.g. "initiator_users"
        $label           (string)  human label
        $placeholder     (string)  input placeholder
        $multiple        (bool)
        $selectedLabels  (array)   [id => label]
        $searchQuery     (string)
        $results         (array)   [value => label]
        $canInlineAdd    (bool)    render "+ Create ..." button

    Backwards compatibility: when rendered by renderForm(), $field / $name are
    available instead of $fieldName.
--}}
@php
    $fieldName = $fieldName ?? ($name ?? (isset($field) ? $field->getName() : ''));
    $label = $label ?? (isset($field) ? $field->getLabel() : ucfirst(str_replace('_', ' ', $fieldName)));
    $placeholder = $placeholder ?? 'Search...';
    $multiple = $multiple ?? false;
    $selectedLabels = $selectedLabels ?? [];
    $searchQuery = $searchQuery ?? '';
    $results = $results ?? [];
    $canInlineAdd = $canInlineAdd ?? (isset($field) && method_exists($field, 'canInlineAdd') ? $field->canInlineAdd() : false);
@endphp

<div class="mb-3 searchable-select-wrapper position-relative">
    <label class="form-label">{{ $label }}</label>

    {{-- Selected badges --}}
    @if (!empty($selectedLabels))
        <div class="selected-items mb-2 d-flex flex-wrap gap-1">
            @foreach ($selectedLabels as $id => $labelText)
                <span class="badge bg-primary">
                    {{ $labelText }}
                    <button type="button" class="btn-close btn-close-white ms-1"
                        style="font-size: 0.5rem;"
                        wire:click="removeSelected(@js($fieldName), @js($id))"></button>
                </span>
            @endforeach
        </div>
    @endif

    {{-- Search input: bound straight to the Livewire component --}}
    <input type="text"
        class="form-control @error($fieldName) is-invalid @enderror"
        placeholder="{{ $placeholder }}"
        autocomplete="off"
        wire:model.live.debounce.300ms="searches.{{ $fieldName }}" />

    {{-- Results dropdown: only rendered when the server returned matches --}}
    @if (!empty($results))
        <ul wire:key="search-results-{{ $fieldName }}"
            class="list-group mt-1 position-absolute w-100 searchable-select-results"
            style="max-height: 200px; overflow-y: auto; z-index: 1000; background: #fff; box-shadow: 0 4px 12px rgba(0,0,0,0.15);">
            @foreach ($results as $value => $resultLabel)
                <li class="list-group-item list-group-item-action" tabindex="0"
                    style="cursor: pointer;"
                    wire:click="selectOption(@js($fieldName), @js($value), @js($resultLabel))">
                    {{ $resultLabel }}
                </li>
            @endforeach
        </ul>
    @endif

    {{-- Inline create option (only when the host component supports it) --}}
    @if ($canInlineAdd && !empty($searchQuery) && empty($results) && strlen($searchQuery) >= 2)
        <div class="mt-1">
            <button type="button" class="btn btn-sm btn-link text-primary p-0"
                wire:click="createAndSelectOption(@js($fieldName), @js($searchQuery))">
                + Create "{{ $searchQuery }}"
            </button>
        </div>
    @endif

    {{-- Validation error --}}
    @error($fieldName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

@once
    <style>
        /* CSS-only dropdown toggle: shown while focus is anywhere inside the
           wrapper (input or a result item), hidden otherwise. */
        .searchable-select-wrapper .searchable-select-results {
            display: none;
        }

        .searchable-select-wrapper:focus-within .searchable-select-results,
        .searchable-select-wrapper .searchable-select-results:hover {
            display: block;
        }
    </style>
@endonce
