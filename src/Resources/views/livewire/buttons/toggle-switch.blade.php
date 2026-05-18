@props([
    'name' => '',
    'checked' => false,
    'label' => '',
    'onColor' => 'success',
    'offColor' => 'secondary',
    'icon' => null,
    'iconBg' => 'light',
    'iconColor' => 'dark',
])

<div class="d-flex align-items-center justify-content-between">
    @if($icon)
        <div class="d-flex align-items-center gap-2">
            <div class="icon icon-shape bg-gradient-{{ $iconBg }} shadow text-center border-radius-md" style="width: 32px; height: 32px;">
                <i class="{{ $icon }} text-{{ $iconColor }}" style="font-size: 0.9rem;"></i>
            </div>
            <span class="text-sm font-weight-bold">{{ $label }}</span>
        </div>
    @else
        <span class="text-sm font-weight-bold">{{ $label }}</span>
    @endif

    <div class="form-check form-switch">
        <input type="checkbox"
               name="{{ $name }}"
               wire:model.live="permissions.{{ $name }}"
               class="form-check-input bg-{{ $checked ? $onColor : $offColor }}"
               id="toggle-{{ $name }}">
    </div>
</div>