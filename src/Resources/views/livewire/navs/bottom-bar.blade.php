<nav class="navbar navbar-light bg-white shadow-sm d-md-none fixed-bottom"
     style="z-index: 1030; padding-bottom: env(safe-area-inset-bottom);"
     x-data="{ overflowOpen: false }"
     @open-overflow-sheet.window="overflowOpen = true">

    <div class="d-flex justify-content-around w-100 px-1 py-1">

        {{-- Visible context group tabs --}}
        @foreach ($this->visibleGroups as $key => $group)
            @php
                $isActive = $key === $activeContext;
                $url = $this->resolveUrl($group);
                $hasItems = !empty($group['items']);
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
                @if ($isActive && $hasItems)
                    <i class="fas fa-chevron-up opacity-50" style="font-size: 0.5rem;"></i>
                @endif
            </a>
        @endforeach

        {{-- "More" overflow tab --}}
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

    {{-- Overflow Sheet (Bootstrap Offcanvas) --}}
    @if ($this->hasOverflow)
    <div class="offcanvas offcanvas-bottom h-auto"
         tabindex="-1"
         id="bottomBarOverflowSheet"
         x-bind:class="{ 'show': overflowOpen }"
         x-show="overflowOpen"
         x-transition
         style="max-height: 70vh; border-radius: 16px 16px 0 0;"
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
                        {{-- Group header --}}
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

                        {{-- Sub-items (expandable) --}}
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