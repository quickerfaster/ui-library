<button type="button"
        class="btn btn-sm btn-outline-secondary ms-2"
        wire:click="generateField('{{ $fieldName }}')"
        title="Generate value">
    <i class="fas fa-magic"></i> {{ $label }}
</button>