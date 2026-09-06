<div class="workspace-tabs-container" data-tab-container @if (empty($openTabs)) style="display: none;" @endif>
    <div class="workspace-tab-strip d-flex align-items-center border-bottom" data-tab-strip>
        @foreach ($openTabs as $tab)
            @php
                $isActive = ($tab['id'] ?? null) === $activeTabId;
            @endphp
            <div class="workspace-tab d-flex align-items-center px-3 py-2 {{ $isActive ? 'active' : '' }}"
                data-tab-item
                data-tab-id="{{ $tab['id'] }}"
                data-tab-url="{{ $tab['url'] ?? '' }}"
                data-tab-label="{{ $tab['label'] ?? '' }}"
                title="{{ $tab['label'] ?? '' }}">
                <a href="{{ $tab['url'] ?? '#' }}" class="tab-label d-flex align-items-center text-decoration-none" data-tab-link>
                    @if (! empty($tab['icon']))
                        <i class="{{ $tab['icon'] }} me-2"></i>
                    @endif
                    <span class="tab-label-text">{{ $tab['label'] ?? '' }}</span>
                </a>
                <span class="tab-close" data-tab-close title="{{ __('qf::nav.close_tab') }}">&times;</span>
            </div>
        @endforeach

        <div class="tab-overflow dropdown ms-auto" data-tab-overflow style="display: none;">
            <button class="tab-overflow-toggle btn btn-sm" type="button" data-bs-toggle="dropdown"
                aria-expanded="false" aria-label="{{ __('qf::nav.more_tabs') }}">
                <i class="fas fa-ellipsis-v"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-end tab-overflow-menu" data-tab-overflow-menu></div>
        </div>
    </div>

    <div class="tab-context-menu" data-tab-context-menu>
        <div class="context-menu-item" data-tab-action="close">{{ __('qf::nav.close_tab') }}</div>
        <div class="context-menu-item" data-tab-action="close-others">{{ __('qf::nav.close_others') }}</div>
        <div class="context-menu-item" data-tab-action="close-all-to-right">{{ __('qf::nav.close_all_to_right') }}</div>
        <div class="context-menu-item" data-tab-action="close-all">{{ __('qf::nav.close_all') }}</div>
    </div>
</div>
