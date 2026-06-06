@props(['field', 'value', 'name', 'label'])

<div class="mb-3">
    <label class="form-label fw-bold">{{ $label }}</label>
    @php
        $currentType = $this->fields['type'] ?? 'benefit';
        // Use a stable key: policy type + record ID (if exists) or a static string
        $recordId = $this->recordId ?? 'new';
    @endphp
    <livewire:qf.policy-calculation-builder
        :policyType="$currentType"
        :existingJson="$value"
        wire:key="policy-builder-{{ $currentType }}-{{ $recordId }}"
    />
    @error($name)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>