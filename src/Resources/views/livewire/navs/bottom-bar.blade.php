<nav class="navbar navbar-light bg-white shadow-sm d-md-none fixed-bottom"
     style="z-index: 1030; padding-bottom: env(safe-area-inset-bottom);"
     x-data="bottomBarData({
         activeContext: '{{ $activeContext }}',
         contextGroups: {{ json_encode($this->contextGroups) }},
         visibleGroups: {{ json_encode($this->visibleGroups) }},
         hasOverflow: {{ $this->hasOverflow ? 'true' : 'false' }}
     })">

    {{-- Handle Bar — context label + optional sub-menu trigger --}}
    @php
        $activeGroup = $this->contextGroups[$activeContext] ?? null;
        $activeHasItems = $activeGroup && !empty($activeGroup['items']);
        $activeUrl = $activeGroup ? $this->resolveUrl($activeGroup) : '#';
    @endphp
    @if ($activeGroup)
        <div class="w-100 py-1 border-top border-light"
             style="cursor: pointer; background: rgba(var(--bs-primary-rgb, 13, 110, 253), 0.04); display: block; text-align: center;"
             @if ($activeHasItems)
                 @click="Livewire.dispatch('openContextSheet')"
             @else
                 @click="Livewire.navigate('{{ $activeUrl }}')"
             @endif>
            <span class="text-primary fw-medium" style="font-size: 0.75rem; pointer-events: none;">
                {{ $activeGroup['label'] ?? $activeContext }}
            </span>
            @if ($activeHasItems)
                <i class="fas fa-chevron-up text-primary ms-1" style="font-size: 0.65rem; pointer-events: none;"></i>
            @endif
        </div>
    @endif

    {{-- Tab Bar — icons only --}}
    <div class="d-flex justify-content-around w-100 px-1 pt-1 pb-1" style="border-top: 1px solid rgba(0,0,0,0.04);">
        @foreach ($this->visibleGroups as $key => $group)
            @php
                $isActive = $key === $activeContext;
                $url = $this->resolveUrl($group);
            @endphp
            <a href="{{ $url }}"
               wire:navigate
               class="btn btn-sm d-flex align-items-center justify-content-center flex-shrink-0 border-0
                      {{ $isActive ? 'text-primary' : 'text-muted' }}"
               style="width: 56px; height: 44px;"
               wire:key="bb-tab-{{ $key }}"
               title="{{ $group['label'] ?? $key }}">
                @if (!empty($group['icon']))
                    <i class="{{ $group['icon'] }} fs-5 {{ $isActive ? 'opacity-100' : 'opacity-50' }}"></i>
                @else
                    <span class="fw-bold {{ $isActive ? 'opacity-100' : 'opacity-50' }}" style="font-size: 0.7rem;">
                        {{ \Illuminate\Support\Str::limit($group['label'] ?? $key, 3, '') }}
                    </span>
                @endif
            </a>
        @endforeach

        @if ($this->hasOverflow)
            <button class="btn btn-sm d-flex flex-column align-items-center justify-content-center flex-shrink-0 border-0 text-muted"
                    style="width: 56px; height: 44px; gap: 1px;"
                    @click="overflowOpen = true"
                    wire:key="bb-tab-more">
                <i class="fas fa-ellipsis-h opacity-50" style="font-size: 0.9rem;"></i>
                <span style="font-size: 0.55rem; line-height: 1;">More</span>
            </button>
        @endif
    </div>

    {{-- Overflow Sheet --}}
    @if ($this->hasOverflow)
    <div x-show="overflowOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0"
         x-transition:enter-end="opacity-100"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100"
         x-transition:leave-end="opacity-0"
         class="position-fixed start-0 top-0 w-100 h-100 bg-dark opacity-50"
         style="z-index: 1040;"
         @click="overflowOpen = false"></div>

    <div x-show="overflowOpen"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="translate-y-full"
         x-transition:enter-end="translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="translate-y-0"
         x-transition:leave-end="translate-y-full"
         class="position-fixed bottom-0 start-0 end-0 bg-white shadow-lg"
         style="z-index: 1045; max-height: 70vh; border-radius: 16px 16px 0 0; overflow-y: auto;">
        <div class="d-flex justify-content-center pt-2 pb-1">
            <div class="bg-secondary rounded-pill opacity-50" style="width: 36px; height: 4px;"></div>
        </div>
        <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0 fw-bold flex-grow-1">More Contexts</h6>
            <button type="button" class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center" @click="overflowOpen = false" style="width: 32px; height: 32px; min-width: 32px;">
                <i class="fas fa-times opacity-50"></i>
            </button>
        </div>
        <div class="p-0">
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
                        <div role="button"
                           @click="expanded = !expanded"
                           class="d-flex align-items-center text-decoration-none {{ $isActive ? 'text-primary fw-bold' : 'text-dark' }}"
                           style="cursor: pointer;">
                            @if (!empty($group['icon']))
                                <i class="{{ $group['icon'] }} me-2 {{ $isActive ? 'opacity-100' : 'opacity-50' }}" style="width: 20px;"></i>
                            @endif
                            <span class="flex-grow-1">{{ $group['label'] ?? $key }}</span>
                            @if ($hasItems)
                                <i class="fas fa-chevron-down opacity-50 ms-2"
                                   :class="{ 'fa-chevron-down': !expanded, 'fa-chevron-up': expanded }"
                                   style="pointer-events: none;"></i>
                            @endif
                        </div>
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