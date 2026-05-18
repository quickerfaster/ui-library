<div class="form-check form-switch d-inline-block">
    <input type="checkbox"
           class="form-check-input bg-{{ $value ? $onColor : $offColor }}"
           wire:click="toggle"
           @disabled($disabled)
           @checked($value)
           id="toggle-{{ $name }}">
    @if($label)
        <label class="form-check-label ms-1" for="toggle-{{ $name }}">{{ $label }}</label>
    @endif
</div>