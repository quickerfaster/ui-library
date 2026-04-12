@props([
    'fieldName',
    'label',
    'morphMap' => [],
    'displayField' => 'name',
])

<div class="mb-3" wire:key="morph-{{ $fieldName }}-{{ $this->morphSelectedType[$fieldName] ?? 'none' }}-{{ $this->morphSelectedId[$fieldName] ?? 'none' }}">
    <label class="form-label">{{ $label }}</label>

    {{-- Type radio buttons --}}
    <div class="mb-2">
        @foreach($morphMap as $typeKey => $modelClass)
            <div class="form-check form-check-inline">
                <input type="radio"
                       class="form-check-input"
                       wire:model.live="morphSelectedType.{{ $fieldName }}"
                       value="{{ $typeKey }}"
                       id="{{ $fieldName }}_{{ $typeKey }}">
                <label class="form-check-label" for="{{ $fieldName }}_{{ $typeKey }}">
                    {{ ucfirst($typeKey) }}
                </label>
            </div>
        @endforeach
    </div>



    {{-- Entity dropdown --}}
    @if(($this->morphSelectedType[$fieldName] ?? null) && !empty($this->morphEntityOptions[$fieldName] ?? []))
        <select class="form-select"
                wire:model.live="morphSelectedId.{{ $fieldName }}"
                id="{{ $fieldName }}_id">
            <option value="">-- Select {{ ucfirst($this->morphSelectedType[$fieldName]) }} --</option>
            @foreach($this->morphEntityOptions[$fieldName] as $id => $label)
                <option value="{{ $id }}">{{ $label }}</option>
            @endforeach
        </select>
    @elseif($this->morphSelectedType[$fieldName] ?? null)
        <div class="alert alert-info mt-2">No {{ ucfirst($this->morphSelectedType[$fieldName]) }} found.</div>
    @endif
</div>