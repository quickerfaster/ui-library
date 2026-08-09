<div class="module-switcher d-flex align-items-center gap-1">
    @foreach($modules as $key => $module)
        <button
            type="button"
            wire:click="switchModule('{{ $key }}')"
            @class([
                'btn btn-icon btn-sm rounded-circle',
                'btn-primary' => $activeModule === $key,
                'btn-outline-secondary' => $activeModule !== $key,
            ])
            data-bs-toggle="tooltip"
            data-bs-placement="bottom"
            title="{{ $module['label'] }}"
        >
            <i class="fas {{ $module['icon'] }}"></i>
        </button>
    @endforeach
</div>