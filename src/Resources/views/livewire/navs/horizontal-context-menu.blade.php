<nav class="navbar navbar-expand navbar-light bg-light shadow-sm mb-3 pt-3 pb-0"
     @if($position === 'right') style="justify-content: flex-end;" @endif>
     
    <div class="container-fluid {{ $position === 'right' ? 'justify-content-end' : '' }}">
        <!-- Left-aligned menu items -->
        <ul class="navbar-nav {{ $position === 'right' ? '' : 'me-auto' }}">
            @foreach ($items as $item)
                @php
                    $isActive = false;
                    
                    if (isset($item['route'])) {
                        if (!str_contains($item['route'], '/')) {
                            $isActive = request()->routeIs($item['route']);
                        } else {
                            $isActive = request()->url() === url($item['route']);
                        }
                    } elseif (isset($item['url'])) {
                        $isActive = request()->url() === url($item['url']);
                    }
                    
                    if (!$isActive && !empty($currentModelName)) {
                        $itemKey = $item['key'] ?? '';
                        $itemLabel = $item['label'] ?? '';
                        $normalizedModel = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $currentModelName));
                        $isActive = ($itemKey === $normalizedModel) || (strtolower($itemLabel) === strtolower($currentModelName));
                    }
                @endphp
                
                <li class="nav-item">
                    <a href="{{ $item['route'] ?? '#' }}" 
                       class="nav-link {{ $isActive ? 'active fw-bold text-primary' : '' }}"
                       wire:navigate>
                        @if (!empty($item['icon']))
                            <i class="{{ $item['icon'] }} me-1"></i>
                        @endif
                        {{ $item['label'] }}
                    </a>
                </li>
            @endforeach
        </ul>

        <!-- Right-aligned switch button -->
        @if ($allowTypeSwitch)
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <button wire:click="switchToSidebar" class="btn btn-sm btn-outline-secondary"
                            title="Switch to sidebar">
                        <i class="fa fa-bars-staggered"></i>
                        <span class="d-none d-md-inline">Sidebar</span>
                    </button>
                </li>
            </ul>
        @endif
    </div>
</nav>