@php
    $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
    $url = $isNamedRoute ? route($item['route']) : url($item['url'] ?? Str::kebab($item['key'] ?? $item['label']));

    // Permission resolution priority:
    // 1. Explicit 'permission' key in config → check via AuthorizationService
    // 2. Explicit 'roles' key in config → check user has any of the roles
    // 3. Derive permission from URL with Str::singular() fallback
    $hasPermission = true;
    if (!empty($item['permission'])) {
        $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
    } elseif (!empty($item['roles'])) {
        $roles = $item['roles'];
        $isWildcard = ($roles === '*' || $roles === ['*']);
        $hasPermission = $isWildcard || (auth()->check() && auth()->user()->hasAnyRole((array) $roles));
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
