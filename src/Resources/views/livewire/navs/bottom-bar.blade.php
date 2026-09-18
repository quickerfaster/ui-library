<nav class="navbar navbar-light bg-white shadow-sm d-md-none fixed-bottom"
     style="z-index: 1030; padding-bottom: env(safe-area-inset-bottom);"
     x-data="{}">

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
            @php $activeInOverflow = $this->isActiveInOverflow; @endphp
            <button class="btn btn-sm d-flex flex-column align-items-center justify-content-center flex-shrink-0 border-0
                           {{ $activeInOverflow ? 'text-primary' : 'text-muted' }}"
                    style="width: 56px; height: 44px; gap: 1px;"
                    wire:click="openOverflow"
                    wire:key="bb-tab-more">
                <i class="fas fa-ellipsis-h {{ $activeInOverflow ? 'opacity-100' : 'opacity-50' }}" style="font-size: 0.9rem;"></i>
                <span style="font-size: 0.55rem; line-height: 1;">More</span>
            </button>
        @endif
    </div>

    {{-- Overflow Sheet --}}
    @if ($this->hasOverflow && $overflowOpen)
    {{-- Backdrop --}}
    <div class="position-fixed start-0 top-0 w-100 h-100 bg-dark opacity-50"
         style="z-index: 1040;"
         @click="$wire.closeOverflow()"></div>

    {{-- Sheet --}}
    <div class="position-fixed bottom-0 start-0 end-0 bg-white shadow-lg"
         style="z-index: 1045; max-height: 70vh; border-radius: 16px 16px 0 0; overflow-y: auto;">
        <div class="d-flex justify-content-center pt-2 pb-1">
            <div class="bg-secondary rounded-pill opacity-50" style="width: 36px; height: 4px;"></div>
        </div>
        <div class="d-flex align-items-center px-3 py-2 border-bottom">
            <h6 class="mb-0 fw-bold flex-grow-1">More Contexts</h6>
            <button type="button" class="btn btn-sm btn-light rounded-circle p-0 d-flex align-items-center justify-content-center"
                    @click="$wire.closeOverflow()" style="width: 32px; height: 32px; min-width: 32px;">
                <i class="fas fa-times opacity-50"></i>
            </button>
        </div>
        <div class="p-0">
            @foreach ($this->overflowGroups as $key => $group)
                @php
                    $isActive = $key === $activeContext;
                    $url = $this->resolveUrl($group);
                    $hasItems = !empty($group['items']);
                @endphp
                <div class="px-0 py-0"
                     x-data="{ expanded: {{ $isActive ? 'true' : 'false' }} }"
                     wire:key="bb-overflow-{{ $key }}">
                    {{-- Group header --}}
                    <div role="button"
                       @click="expanded = !expanded"
                       class="d-flex align-items-center text-decoration-none px-3 py-3
                              {{ $isActive ? 'fw-bold' : 'text-dark' }}"
                       @if ($isActive)
                       style="background: rgba(13, 110, 253, 0.25); border-left: 3px solid #0d6efd; color: #212529; cursor: pointer;"
                       @else
                       style="border-left: 3px solid transparent; cursor: pointer;"
                       @endif>
                        @if (!empty($group['icon']))
                            <i class="{{ $group['icon'] }} me-2 {{ $isActive ? 'text-primary opacity-100' : 'text-muted opacity-50' }}" style="width: 20px;"></i>
                        @endif
                        <span class="flex-grow-1">{{ $group['label'] ?? $key }}</span>
                        @if ($hasItems)
                            <i class="fas fa-chevron-down ms-2 {{ $isActive ? 'text-primary' : 'text-muted' }} opacity-50"
                               :class="{ 'fa-chevron-down': !expanded, 'fa-chevron-up': expanded }"
                               style="pointer-events: none;"></i>
                        @endif
                    </div>
                    {{-- Sub-items --}}
                    @if ($hasItems)
                    <div class="ms-4 border-start ps-2" x-show="expanded" x-collapse>
                        @foreach ($group['items'] as $item)
                            @php
                                $itemUrl = $this->resolveItemUrl($item);
                                $itemActive = $this->isItemActive($item);
                            @endphp
                            <a href="{{ $itemUrl }}"
                               wire:navigate
                               @click="$wire.closeOverflow()"
                               class="d-flex align-items-center py-2 pe-3 text-decoration-none
                                      {{ $itemActive ? 'fw-bold' : 'text-muted' }}"
                               @if ($itemActive)
                               style="background: rgba(13, 110, 253, 0.15); border-radius: 6px; color: #212529;"
                               @endif>
                                @if (!empty($item['icon']))
                                    <i class="{{ $item['icon'] }} me-2 {{ $itemActive ? 'text-primary opacity-100' : 'opacity-50' }}" style="width: 16px;"></i>
                                @endif
                                <span class="flex-grow-1">{{ $item['label'] }}</span>
                                @if ($itemActive)
                                    <i class="fas fa-check text-primary" style="font-size: 0.7rem;"></i>
                                @endif
                            </a>
                        @endforeach
                    </div>
                    @endif
                </div>
            @endforeach
        </div>
    </div>
    @endif

</nav>