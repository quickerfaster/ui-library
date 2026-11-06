@props(['name', 'wireModel', 'customAttributes' => [], 'rowId', 'fieldName'])

<div class="position-relative d-inline-block w-100">
    <input type="password"
           {{ $attributes->merge($customAttributes)->merge([
               'class' => 'form-control form-control-sm pe-4',
               'wire:model' => $wireModel,
               'autocomplete' => 'new-password',
               'id' => "inline_{$rowId}_{$fieldName}",
           ]) }}
    >
    <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent px-1"
            onclick="
                const input = this.parentElement.querySelector('input');
                const icon = this.querySelector('i');
                input.type = input.type === 'password' ? 'text' : 'password';
                icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
            " tabindex="-1">
        <i class="fas fa-eye text-muted small"></i>
    </button>
</div>
