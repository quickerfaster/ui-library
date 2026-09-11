@props(['field', 'value' => null, 'name', 'label', 'multiple' => false, 'placeholder' => 'Search...'])

@php
    $fieldName = $field->getName();
    $selectedLabels = $selectedLabels ?? [];
    $searchQuery = $searchQuery ?? '';
    $results = $results ?? [];
@endphp

<div class="mb-3">
    <label class="form-label">{{ $label }}</label>

    {{-- Selected badges --}}
    <div class="selected-items mb-2">
        @foreach ($selectedLabels as $id => $labelText)
            <span class="badge bg-primary me-1">
                {{ $labelText }}
                <button type="button" class="btn-close btn-close-white ms-1"
                    data-remove-value="{{ $id }}"
                    data-remove-field="{{ $fieldName }}"
                    style="font-size: 0.5rem;"></button>
            </span>
        @endforeach
    </div>

    {{-- Search input --}}
    <div class="position-relative">
        <input type="text"
            class="form-control @error($fieldName) is-invalid @enderror"
            id="search-input-{{ $fieldName }}"
            placeholder="{{ $placeholder }}"
            data-search-field="{{ $fieldName }}"
            value="{{ $searchQuery }}"
        />

        {{-- Dropdown results --}}
        <ul id="search-results-{{ $fieldName }}"
            class="list-group mt-1 position-absolute w-100"
            style="max-height: 200px; overflow-y: auto; z-index: 1000; display: none;">
            @if (!empty($searchQuery) && !empty($results))
                @foreach ($results as $id => $resultLabel)
                    <li class="list-group-item list-group-item-action"
                        data-select-value="{{ $id }}"
                        data-select-label="{{ $resultLabel }}"
                        data-select-field="{{ $fieldName }}"
                        style="cursor: pointer;">
                        {{ $resultLabel }}
                    </li>
                @endforeach
            @endif
        </ul>
    </div>

    {{-- CREATE NEW OPTION BUTTON --}}
    @if (
        $field->canInlineAdd() &&
            !empty($searchQuery) &&
            empty($results) &&
            strlen($searchQuery) >= 2)
        <div class="mt-1">
            <button type="button" class="btn btn-sm btn-link text-primary p-0"
                data-create-value="{{ $searchQuery }}"
                data-create-field="{{ $fieldName }}">
                + Create "{{ $searchQuery }}"
            </button>
        </div>
    @endif

    {{-- Validation error --}}
    @error($fieldName)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>

<script>
(function() {
    var input = document.getElementById('search-input-{{ $fieldName }}');
    if (!input || input.dataset.searchInitialized === '1') return;
    input.dataset.searchInitialized = '1';

    var fieldName = input.dataset.searchField;
    var dropdown = document.getElementById('search-results-{{ $fieldName }}');
    var timeout;

    // Helper: find parent Livewire component
    function getComponent() {
        var componentEl = input.closest('[wire\\:id]');
        if (!componentEl) return null;
        var componentId = componentEl.getAttribute('wire:id');
        if (!componentId) return null;
        return window.Livewire.find(componentId);
    }

    // Search input handler with 300ms debounce
    input.addEventListener('input', function() {
        clearTimeout(timeout);
        var value = input.value;
        timeout = setTimeout(function() {
            var component = getComponent();
            if (component) {
                component.set('searches.' + fieldName, value);
            }
        }, 300);
    });

    // Show dropdown on focus
    input.addEventListener('focus', function() {
        if (dropdown) dropdown.style.display = 'block';
    });

    // Hide dropdown on blur (with delay to allow click)
    input.addEventListener('blur', function() {
        setTimeout(function() {
            if (dropdown) dropdown.style.display = 'none';
        }, 200);
    });

    // Result click handler (event delegation on dropdown)
    if (dropdown && !dropdown.dataset.clickInitialized) {
        dropdown.dataset.clickInitialized = '1';
        dropdown.addEventListener('click', function(e) {
            var item = e.target.closest('[data-select-value]');
            if (!item) return;
            e.preventDefault();
            var value = item.dataset.selectValue;
            var label = item.dataset.selectLabel;
            var component = getComponent();
            if (component) {
                component.call('selectOption', fieldName, value, label);
            }
        });
    }

    // Remove badge click handler (event delegation on parent div)
    var container = input.closest('.mb-3');
    if (container && !container.dataset.removeInitialized) {
        container.dataset.removeInitialized = '1';
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-remove-value]');
            if (!btn) return;
            e.preventDefault();
            var value = btn.dataset.removeValue;
            var component = getComponent();
            if (component) {
                component.call('removeSelected', fieldName, value);
            }
        });
    }

    // Create new option click handler
    if (container && !container.dataset.createInitialized) {
        container.dataset.createInitialized = '1';
        container.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-create-value]');
            if (!btn) return;
            e.preventDefault();
            var value = btn.dataset.createValue;
            var component = getComponent();
            if (component) {
                component.call('createAndSelectOption', fieldName, value);
            }
        });
    }
})();
</script>
