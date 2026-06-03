@php
    $isNamedRoute = isset($item['route']) && !Str::contains($item['route'], '/');
    $url = $isNamedRoute ? route($item['route']) : url($item['url'] ?? Str::kebab($item['key'] ?? $item['label']));

    $splittedUrl = explode('/', $url);
    $viewName = count($splittedUrl) > 0 ? $splittedUrl[count($splittedUrl) - 1] : '';
    $viewName = str_replace("dashboard-", "", $viewName);
    // $permissionName = 'view_' . str_replace('-', '_', $viewName);
    $hasPermission = app(App\Modules\Admin\Services\AuthorizationService::class)
        ->canAccessView(auth()->user(), $viewName);

        // Overide untill AuthorizationService::canAccessView( auth()->user(), $permissionName); is fixed
    // $hasPermission = auth()->user()->hasPermissionTo($permissionName);

       
@endphp
@if ($hasPermission)
    <li class="nav-item" wire:key="nav-item-{{ $item['key'] ?? $item['label'] }}"
        @if ($loop->first) data-tour="main-menu-start" @endif {{-- Hook for the first item --}}>

        <a href="{{ $url }}" class="nav-link {{ $key === $activeContext ? 'active fw-bold text-primary' : '' }}">
            @if (!empty($item['icon']))
                <i class="{{ $item['icon'] }} me-1"></i>
            @endif
            <span>{{ $item['label'] }}</span>
        </a>
    </li>
@endif
