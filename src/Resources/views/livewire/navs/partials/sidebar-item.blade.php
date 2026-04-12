@php
    $isActive = false;
    
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
        
        // Normalize model name: "EmployeeWorkPattern" -> "employee_work_pattern"
        $normalizedModel = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $currentModelName));
        
        // Compare with item key (already snake_case) or label (case-insensitive)
        $isActive = ($itemKey === $normalizedModel) || (strtolower($itemLabel) === strtolower($currentModelName));
    }
@endphp

<li class="nav-item text-nowrap" wire:key="sidebar-item-{{ $item['key'] ?? $item['label'] }}">
    <a href="{{ $item['route'] ?? '#' }}" wire:navigate
       class="nav-link d-flex align-items-center {{ $isActive ? 'active fw-bold text-primary' : 'text-dark' }}"
       data-bs-toggle="tooltip"
       data-bs-placement="right"
       title="{{ $item['label'] }}">
        <i class="{{ $item['icon'] ?? 'fas fa-circle' }} me-2"></i>
        @if ($state === 'full')
            <span>{{ $item['label'] }}</span>
        @endif
    </a>
</li>

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