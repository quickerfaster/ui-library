@props(['field', 'name', 'label', 'customAttributes' => []])

<div class="mb-3">
    <label for="{{ $name }}" class="form-label">{{ $label }}</label>
    <div class="position-relative">
        <input type="password"
               {{ $attributes->merge($customAttributes)->merge([
                   'class' => 'form-control ' . ($errors->has($name) ? 'is-invalid' : ''),
                   'id' => $name,
                   'name' => $name,
                   'wire:model' => "fields.$name",
                   'autocomplete' => 'new-password',
               ]) }}
        >
        <button type="button" class="btn btn-sm position-absolute end-0 top-50 translate-middle-y border-0 bg-transparent px-2"
                onclick="
                    const input = this.parentElement.querySelector('input');
                    const icon = this.querySelector('i');
                    input.type = input.type === 'password' ? 'text' : 'password';
                    icon.className = input.type === 'password' ? 'fas fa-eye' : 'fas fa-eye-slash';
                " tabindex="-1">
            <i class="fas fa-eye text-muted"></i>
        </button>
    </div>
    @error($name)
        <div class="invalid-feedback d-block">{{ $message }}</div>
    @enderror
</div>