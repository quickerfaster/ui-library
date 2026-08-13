{{--
    Phase 4.3: Sidebar Section Partial
    Renders a collapsible or static module section with header and child nav links.

    Available variables: $section, $state, $currentModelName

    $section may contain:
      - key: string           Section identity for toggle / wire:key
      - label: string         Display label
      - icon: string          Font Awesome icon class (default: fa-cube)
      - items: array          Child navigation items
      - has_active: bool      Whether section contains the active item
      - collapsible: bool     true = expandable/collapsible (default), false = static label
--}}
@php
    $sectionKey = $section['key'];
    $sectionLabel = $section['label'];
    $sectionIcon = $section['icon'] ?? 'fa-cube';
    $sectionItems = $section['items'] ?? [];
    $hasActive = $section['has_active'] ?? false;
    $isCollapsible = $section['collapsible'] ?? true;
@endphp

<li class="nav-item mb-1" wire:key="sidebar-section-{{ $sectionKey }}">
    @if ($isCollapsible)
        {{-- Section Header — clickable with chevron toggle --}}
        <a href="#"
            class="nav-link d-flex align-items-center sidebar-section-header
                   {{ $state === 'icon' ? 'justify-content-center py-1' : 'py-2' }}
                   {{ $hasActive ? 'active-section' : '' }}"
            x-on:click.prevent="toggle('{{ $sectionKey }}')"
            data-bs-toggle="tooltip"
            data-bs-placement="right"
            title="{{ $sectionLabel }}"
            aria-expanded="false"
            :aria-expanded="isExpanded('{{ $sectionKey }}')">
            <i class="fa {{ $sectionIcon }} {{ $state === 'icon' ? '' : 'me-2' }} {{ $hasActive ? 'text-primary' : 'text-muted' }}"
               style="font-size: 0.85rem;" aria-hidden="true"></i>
            @if ($state === 'icon')
                <i class="fa fa-chevron-down section-expand-indicator text-muted"
                   aria-hidden="true"></i>
            @endif
            @if ($state === 'full')
                <span class="flex-grow-1 small fw-semibold {{ $hasActive ? 'text-primary' : 'text-muted' }}">
                    {{ $sectionLabel }}
                </span>
                <i class="fas fa-chevron-right sidebar-section-chevron text-muted"
                   :class="{ 'expanded': isExpanded('{{ $sectionKey }}') }"
                   aria-hidden="true"></i>
            @endif
        </a>

        {{-- Section Body — collapsible --}}
        <ul class="nav flex-column sidebar-section-body"
            x-show="isExpanded('{{ $sectionKey }}')"
            x-transition:enter="transition ease-out duration-250"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            x-cloak
            role="region"
            :aria-labelledby="'sidebar-section-{{ $sectionKey }}'">
            @foreach ($sectionItems as $item)
                @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
            @endforeach
        </ul>
    @else
        {{-- Static label — no chevron, no click handler --}}
        <div class="sidebar-section-label small fw-semibold text-muted text-uppercase
                    {{ $state === 'icon' ? 'px-0 py-1 d-flex justify-content-center' : 'px-3 py-2' }}"
             style="font-size: 0.7rem; letter-spacing: 0.05em;">
            @if ($state === 'full')
                <i class="fa {{ $sectionIcon }} me-2 text-muted" style="font-size: 0.85rem;" aria-hidden="true"></i>
                {{ $sectionLabel }}
            @else
                <i class="fa {{ $sectionIcon }} text-muted" style="font-size: 0.85rem;"
                   data-bs-toggle="tooltip" data-bs-placement="right" title="{{ $sectionLabel }}"
                   aria-hidden="true"></i>
            @endif
        </div>
        <ul class="nav flex-column">
            @foreach ($sectionItems as $item)
                @include('qf::livewire.navs.partials.sidebar-item', ['item' => $item])
            @endforeach
        </ul>
    @endif
</li>