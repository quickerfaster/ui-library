<nav class="navbar navbar-light bg-white shadow-sm d-md-none fixed-bottom"
     style="z-index: 1030; padding-bottom: env(safe-area-inset-bottom);"
     x-data="bottomBarData({
         activeContext: '{{ $activeContext }}',
         contextGroups: {{ json_encode($this->contextGroups) }},
         visibleGroups: {{ json_encode($this->visibleGroups) }},
         hasOverflow: {{ $this->hasOverflow ? 'true' : 'false' }}
     })">

    {{-- Handle Bar — sits above the tab bar, opens Context Sheet --}}
    @php
        $activeGroup = $this->contextGroups[$activeContext] ?? null;
        $activeHasItems = $activeGroup && !empty($activeGroup['items']);
    @endphp
    @if ($activeHasItems)
        <div class="w-100 py-1 border-top border-light"
             style="cursor: pointer; background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.04); display: block; text-align: center;"
             @click="$wire.call('openSheet', '{{ $activeContext }}', '{{ addslashes($activeGroup['label'] ?? $activeContext) }}', '{{ addslashes($activeGroup['icon'] ?? '') }}', {{ json_encode($activeGroup['items']) }})">
            <span class="text-primary fw-medium" style="font-size: 0.75rem; pointer-events: none;">
                {{ \Illuminate\Support\Str::limit($activeGroup['label'] ?? $activeContext, 20) }}
            </span>
            <i class="fas fa-chevron-up text-primary ms-1" style="font-size: 0.65rem; pointer-events: none;"></i>
        </div>
    @endif

    {{-- Tab Bar --}}
    <div class="d-flex justify-content-around w-100 px-1 pb-1">
        @foreach ($this->visibleGroups as $key => $group)
            @php
                $isActive = $key === $activeContext;
                $url = $this->resolveUrl($group);
            @endphp
            <a href="{{ $url }}"
               wire:navigate
               class="btn btn-sm d-flex flex-column align-items-center justify-content-center flex-shrink-0 border-0
                      {{ $isActive ? 'text-primary fw-bold' : 'text-muted' }}"
               style="min-width: 56px; max-width: 80px; gap: 2px;"
               wire:key="bb-tab-{{ $key }}">
                @if (!empty($group['icon']))
                    <i class="{{ $group['icon'] }} fs-5 {{ $isActive ? 'opacity-100' : 'opacity-50' }}"></i>
                @endif
                <span class="text-truncate" style="font-size: 0.65rem; max-width: 100%; line-height: 1.1;">
                    {{ \Illuminate\Support\Str::limit($group['label'] ?? $key, 10) }}
                </span>
            </a>
        @endforeach

        @if ($this->hasOverflow)
            <button class="btn btn-sm d-flex flex-column align-items-center justify-content-center flex-shrink-0 border-0 text-muted"
                    style="min-width: 56px; max-width: 80px; gap: 2px;"
                    @click="overflowOpen = true"
                    wire:key="bb-tab-more">
                <i class="fas fa-ellipsis-h fs-5 opacity-50"></i>
                <span class="text-truncate" style="font-size: 0.65rem; max-width: 100%; line-height: 1.1;">More</span>
            </button>
        @endif
    </div>

    {{-- Overflow Sheet --}}
    @if ($this->hasOverflow)
    <div class="offcanvas offcanvas-bottom h-auto"
         tabindex="-1"
         id="bottomBarOverflowSheet"
         x-show="overflowOpen"
         x-transition
         style="max-height: 70vh; border-radius: 16px 16px 0 0; display: none;"
         x-effect="if (overflowOpen) { bootstrap.Offcanvas.getOrCreateInstance($el).show() } else { bootstrap.Offcanvas.getInstance($el)?.hide() }"
         @hidden.bs.offcanvas="overflowOpen = false">
        <div class="offcanvas-header border-bottom">
            <h6 class="offcanvas-title fw-bold">More Contexts</h6>
            <button type="button" class="btn-close" @click="overflowOpen = false"></button>
        </div>
        <div class="offcanvas-body p-0">
            <div class="list-group list-group-flush">
                @foreach ($this->overflowGroups as $key => $group)
                    @php
                        $isActive = $key === $activeContext;
                        $url = $this->resolveUrl($group);
                        $hasItems = !empty($group['items']);
                    @endphp
                    <div class="list-group-item border-0 px-3 py-2"
                         x-data="{ expanded: {{ $isActive ? 'true' : 'false' }} }"
                         wire:key="bb-overflow-{{ $key }}">
                        <a href="{{ $url }}"
                           wire:navigate
                           @click="expanded = true"
                           class="d-flex align-items-center text-decoration-none {{ $isActive ? 'text-primary fw-bold' : 'text-dark' }}">
                            @if (!empty($group['icon']))
                                <i class="{{ $group['icon'] }} me-2 {{ $isActive ? 'opacity-100' : 'opacity-50' }}" style="width: 20px;"></i>
                            @endif
                            <span class="flex-grow-1">{{ $group['label'] ?? $key }}</span>
                            @if ($hasItems)
                                <i class="fas fa-chevron-down opacity-50 ms-2"
                                   :class="{ 'fa-chevron-down': !expanded, 'fa-chevron-up': expanded }"
                                   @click.prevent="expanded = !expanded"></i>
                            @endif
                        </a>
                        @if ($hasItems)
                        <div class="ms-4 mt-1 border-start ps-2" x-show="expanded" x-collapse>
                            @foreach ($group['items'] as $item)
                                @php
                                    $itemUrl = !empty($item['route']) && !str_contains($item['route'], '/')
                                        ? route($item['route'])
                                        : (isset($item['route']) ? url($item['route']) : (isset($item['url']) ? url($item['url']) : '#'));
                                @endphp
                                <a href="{{ $itemUrl }}"
                                   wire:navigate
                                   @click="overflowOpen = false"
                                   class="d-flex align-items-center py-1 text-decoration-none text-muted small">
                                    @if (!empty($item['icon']))
                                        <i class="{{ $item['icon'] }} me-2 opacity-50" style="width: 16px;"></i>
                                    @endif
                                    <span>{{ $item['label'] }}</span>
                                </a>
                            @endforeach
                        </div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
    </div>
    @endif

</nav>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('bottomBarData', (config) => ({
        overflowOpen: false,
    }));
});
</script>