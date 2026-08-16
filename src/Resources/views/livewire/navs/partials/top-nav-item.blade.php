@php
    $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
    $url = $isNamedRoute ? route($item['route']) : url($item['url'] ?? Str::kebab($item['key'] ?? $item['label']));

    // Use explicit permission from config if available,
    // otherwise derive from URL with Str::singular() fallback
    $hasPermission = true;
    if (!empty($item['permission'])) {
        $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
    } elseif (!empty($url)) {
        $segments = explode('/', $url);
        $viewName = last($segments);
        $viewName = str_replace('dashboard-', '', $viewName);
        $permission = 'view_' . \Illuminate\Support\Str::singular(str_replace('-', '_', $viewName));
        $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission);
    }

       
@endphp
@if ($hasPermission)
    <li class="nav-item" wire:key="nav-item-{{ $item['key'] ?? $item['label'] }}"
        @if ($loop->first) data-tour="main-menu-start" @endif {{-- Hook for the first item --}}>

        <a href="{{ $url }}" class="nav-link {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
            @if (!empty($item['icon']))
                <i class="fa {{ $item['icon'] }} me-1"></i>
            @endif
            <span>{{ $item['label'] }}</span>
        </a>
    </li>
@endif
