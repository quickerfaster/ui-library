@php
    $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
    $url = $isNamedRoute
        ? route($item['route'])
        : url($item['url'] ?? Str::kebab($item['key'] ?? $item['label']));

@endphp
<li class="nav-item" wire:key="nav-item-{{ $item['key'] ?? $item['label'] }}"
    @if ($loop->first) data-tour="main-menu-start" @endif {{-- Hook for the first item --}}>

    <a href="{{ $url }}"  class="nav-link {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
        @if (!empty($item['icon']))
            <i class="{{ $item['icon'] }} me-1"></i>
        @endif
        <span>{{ $item['label'] }}</span>
    </a>
</li>










