
@php
    $isActive = false;
    
    // Resolve the URL for this item, handling both named routes and URL paths.
    // Named routes (no slashes, e.g. "admin.users") use Laravel's route() helper.
    // URL paths (contain slashes, e.g. "/module/resource" or "module/resource") use url().
    $isNamedRoute = isset($item['route']) && !str_contains($item['route'], '/');
    if ($isNamedRoute) {
        $itemUrl = route($item['route']);
    } elseif (isset($item['route'])) {
        $itemUrl = url($item['route']);
    } elseif (isset($item['url'])) {
        $itemUrl = url($item['url']);
    } else {
        $itemUrl = '#';
    }
    
    // 1. Try route/URL matching
    if (isset($item['route'])) {
        // If route is a named route (no slashes)
        if (!str_contains($item['route'], '/')) {
            $isActive = request()->routeIs($item['route']);
        } else {
            // Direct URL comparison
            $isActive = request()->url() === url($item['route']);
        }
    } elseif (isset($item['url'])) {
        $isActive = request()->url() === url($item['url']);
    }
    
    // 2. If not active yet, try model name matching (fallback for detail pages)
    if (!$isActive && !empty($currentModelName)) {
        $itemKey = $item['key'] ?? '';
        $itemLabel = $item['label'] ?? '';
        
        // Normalize model name: "SomeModelName" -> "some_model_name"
        $normalizedModel = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $currentModelName));
        
        // Compare with item key (already snake_case) or label (case-insensitive)
        $isActive = ($itemKey === $normalizedModel) || (strtolower($itemLabel) === strtolower($currentModelName));
    }


    // Use explicit permission from config if available (config-driven pattern),
    // otherwise derive from URL with Str::singular() fallback
    $hasPermission = true;
    if (!empty($item['permission'])) {
        $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($item['permission']);
    } elseif (isset($item['route'])) {
        $segments = explode('/', $item['route']);
        $viewName = last($segments);
        $viewName = str_replace('dashboard-', '', $viewName);
        $permission = 'view_' . \Illuminate\Support\Str::singular(str_replace('-', '_', $viewName));
        $hasPermission = \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission);
    }
        
@endphp

@if ($hasPermission)
    <li class="nav-item text-nowrap" wire:key="sidebar-item-{{ $item['key'] ?? $item['label'] }}"
        data-filterable
        data-filter-text="{{ strtolower($item['label'] ?? '') }} {{ strtolower($item['key'] ?? '') }} {{ strtolower($activeContext ?? '') }}">
        <a href="{{ $itemUrl }}"
        class="nav-link d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : 'text-dark' }}"
        data-bs-toggle="tooltip"
        data-bs-placement="right"
        title="{{ $item['label'] }}"
        @if(config('ui-library.navigation.open_in_tabs', false))
        data-workspace-tab
        data-tab-label="{{ $item['label'] ?? '' }}"
        data-tab-url="{{ $itemUrl }}"
        data-tab-icon="{{ $item['icon'] ?? '' }}"
        data-tab-context="{{ $activeContext ?? '' }}"
        @endif>
            <i class="fa {{ $item['icon'] ?? 'fa-circle' }} me-2"></i>
            @if ($state === 'full')
                <span>{{ $item['label'] }}</span>
            @endif
        </a>
    </li>
@endif


{{-- Optional: Add CSS for the active class  
<style>
    .nav-link.active {
        background-color: rgba(13, 110, 253, 0.1);
        border-radius: 0.375rem;
        font-weight: 600;
        color: #0d6efd !important;
    }
</style>
--}}