@props(['field', 'value', 'name', 'label'])
<div class="mb-3" wire:key="policy-builder-{{ $name }}-{{ $this->fields['type'] ?? 'none' }}">
    <label class="form-label fw-bold">{{ $label }}</label>
    @php
        $currentType = $this->fields['type'] ?? 'benefit';
    @endphp
    
    <livewire:qf.policy-calculation-builder
        :policyType="$currentType"
        :existingJson="$value"
        wire:key="builder-{{ $currentType }}-{{ md5($value ?? '') }}"
    />
    @error($name)
        <div class="text-danger small mt-1">{{ $message }}</div>
    @enderror
</div>