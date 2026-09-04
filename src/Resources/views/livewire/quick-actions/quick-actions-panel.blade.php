<div>
    <div id="quick-actions-panel"
     class="quick-actions-overlay {{ $isOpen ? 'show' : '' }}"
     data-actions="{{ json_encode($actions) }}"
     x-data="{}"
     wire:key="quick-actions-panel"
     style="display: {{ $isOpen ? 'flex' : 'none' }};">

    {{-- Modal Content --}}
    <div class="quick-actions-modal" @click.stop="">
        {{-- Search Input --}}
        <div class="quick-actions-search-wrapper">
            <i class="fas fa-search quick-actions-search-icon"></i>
            <input
                id="quick-actions-search"
                type="text"
                class="quick-actions-search-input"
                wire:model.live.debounce.100ms="query"
                placeholder="{{ $placeholder }}"
                autocomplete="off"
                autofocus
            />
            <span class="quick-actions-shortcut-hint">{{ $shortcutHint }}</span>
        </div>

        {{-- Results List --}}
        <div id="quick-actions-results" class="quick-actions-results">
            @if (empty($filteredActions))
                <div id="quick-actions-no-results" class="quick-actions-no-results">
                    <i class="fas fa-search fa-2x opacity-6 mb-2"></i>
                    <p class="text-sm text-muted mb-0">No actions found</p>
                    <p class="text-xs text-muted">Try a different search term</p>
                </div>
            @else
                @php
                    $grouped = [];
                    foreach ($filteredActions as $action) {
                        $category = ($action['_pinned'] ?? false) ? '⭐ Pinned' : ($action['category'] ?? 'Actions');
                        $grouped[$category][] = $action;
                    }

                    // Ensure Pinned category comes first
                    if (isset($grouped['⭐ Pinned'])) {
                        $pinned = ['⭐ Pinned' => $grouped['⭐ Pinned']];
                        unset($grouped['⭐ Pinned']);
                        $grouped = array_merge($pinned, $grouped);
                    }
                @endphp

                @foreach ($grouped as $category => $categoryActions)
                    <div class="quick-action-category-header">
                        <span>{{ $category }}</span>
                    </div>

                    @foreach ($categoryActions as $index => $action)
                        @php
                            $globalIndex = array_search($action, $filteredActions);
                            $actionId = $action['id'] ?? $action['key'] ?? '';
                            $isFav = in_array($actionId, $favorites, true);
                            $shortcut = $this->getShortcutBadge($globalIndex);
                        @endphp
                        <div class="quick-action-item {{ $selectedIndex === $globalIndex ? 'highlighted' : '' }}"
                             data-label="{{ $action['label'] }}"
                             data-description="{{ $action['description'] ?? '' }}"
                             data-keywords="{{ implode(' ', $action['keywords'] ?? []) }}"
                             data-category="{{ $action['category'] ?? '' }}"
                             data-module="{{ $action['module'] ?? '' }}"
                             data-action-id="{{ $actionId }}"
                             wire:click="selectResult({{ $globalIndex }})"
                             role="button"
                             tabindex="0"
                        >
                            {{-- Star / Pin Toggle --}}
                            <button type="button"
                                    class="quick-action-star {{ $isFav ? 'favorited' : '' }}"
                                    wire:click.stop="toggleFavorite('{{ $actionId }}')"
                                    title="{{ $isFav ? 'Unpin action' : 'Pin action' }}"
                                    aria-label="{{ $isFav ? 'Unpin' : 'Pin' }} {{ $action['label'] }}">
                                <i class="{{ $isFav ? 'fas' : 'far' }} fa-star"></i>
                            </button>

                            <div class="quick-action-item-icon">
                                <i class="{{ $action['icon'] ?? 'fas fa-bolt' }}"></i>
                            </div>
                            <div class="quick-action-item-content">
                                <span class="quick-action-item-label">{{ $action['label'] }}</span>
                                @if (!empty($action['description']))
                                    <span class="quick-action-item-description">{{ $action['description'] }}</span>
                                @endif
                            </div>
                            @if ($shortcut)
                                <span class="quick-action-item-shortcut">{{ $shortcut }}</span>
                            @elseif (!empty($action['shortcut']))
                                <span class="quick-action-item-shortcut">{{ $action['shortcut'] }}</span>
                            @endif
                        </div>
                    @endforeach
                @endforeach
            @endif
        </div>

        {{-- Footer --}}
        <div class="quick-actions-footer">
            <span class="quick-actions-footer-hint">
                <kbd>↑↓</kbd> Navigate
            </span>
            <span class="quick-actions-footer-hint">
                <kbd>↵</kbd> Select
            </span>
            <span class="quick-actions-footer-hint">
                <kbd>Esc</kbd> Close
            </span>
            <span class="quick-actions-footer-hint">
                <kbd>⌘⇧1-9</kbd> Quick Launch
            </span>
        </div>

        {{-- Phase 4: Analytics Section --}}
        @if (!empty($personalStats) && $personalStats['total'] > 0)
        <div class="quick-actions-analytics">
            <div class="quick-actions-analytics-header">
                <i class="fas fa-chart-bar me-1"></i> Your Stats
            </div>
            <div class="quick-actions-analytics-body">
                <div class="qa-stat">
                    <span class="qa-stat-value">{{ $personalStats['total'] }}</span>
                    <span class="qa-stat-label">Total Actions</span>
                </div>
                @if (!empty($personalStats['top_actions']))
                <div class="qa-stat-section">
                    <span class="qa-stat-section-title">Top Actions</span>
                    @foreach (array_slice($personalStats['top_actions'], 0, 3) as $topAction)
                    <div class="qa-stat-row">
                        <span class="qa-stat-row-label">{{ $topAction['label'] }}</span>
                        <span class="qa-stat-row-count">{{ $topAction['count'] }}×</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif

        {{-- Phase 4: Global Analytics (Admin Only) --}}
        @if ($canViewAnalytics && !empty($globalStats))
        <div class="quick-actions-analytics quick-actions-analytics-global">
            <div class="quick-actions-analytics-header">
                <i class="fas fa-globe me-1"></i> Global Stats
            </div>
            <div class="quick-actions-analytics-body">
                <div class="qa-stat">
                    <span class="qa-stat-value">{{ $globalStats['total_actions'] }}</span>
                    <span class="qa-stat-label">Total Actions</span>
                </div>
                <div class="qa-stat">
                    <span class="qa-stat-value">{{ $globalStats['unique_users'] }}</span>
                    <span class="qa-stat-label">Unique Users</span>
                </div>
                @if (!empty($globalStats['top_actions']))
                <div class="qa-stat-section">
                    <span class="qa-stat-section-title">Most Used</span>
                    @foreach (array_slice($globalStats['top_actions'], 0, 5) as $topAction)
                    <div class="qa-stat-row">
                        <span class="qa-stat-row-label">{{ $topAction['label'] }}</span>
                        <span class="qa-stat-row-count">{{ $topAction['count'] }}× ({{ $topAction['unique_users'] }} users)</span>
                    </div>
                    @endforeach
                </div>
                @endif
            </div>
        </div>
        @endif
    </div>
</div>

{{-- Inline styles for the command palette (scoped to avoid conflicts) --}}
<style>
    /* Overlay */
    .quick-actions-overlay {
        position: fixed;
        inset: 0;
        z-index: 9999;
        display: flex;
        align-items: flex-start;
        justify-content: center;
        padding-top: 12vh;
        background-color: rgba(0, 0, 0, 0.5);
        backdrop-filter: blur(4px);
        -webkit-backdrop-filter: blur(4px);
    }

    /* Modal */
    .quick-actions-modal {
        width: 100%;
        max-width: 600px;
        max-height: 70vh;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3), 0 0 0 1px rgba(0, 0, 0, 0.08);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: qa-slide-in 0.15s ease-out;
    }

    @keyframes qa-slide-in {
        from {
            opacity: 0;
            transform: translateY(-8px) scale(0.98);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    /* Search */
    .quick-actions-search-wrapper {
        display: flex;
        align-items: center;
        padding: 12px 16px;
        border-bottom: 1px solid #e9ecef;
        gap: 10px;
    }

    .quick-actions-search-icon {
        color: #adb5bd;
        font-size: 16px;
        flex-shrink: 0;
    }

    .quick-actions-search-input {
        flex: 1;
        border: none;
        outline: none;
        font-size: 16px;
        color: #212529;
        background: transparent;
        padding: 4px 0;
    }

    .quick-actions-search-input::placeholder {
        color: #adb5bd;
    }

    .quick-actions-shortcut-hint {
        font-size: 11px;
        color: #adb5bd;
        background: #f1f3f5;
        padding: 2px 8px;
        border-radius: 4px;
        font-family: monospace;
        white-space: nowrap;
        flex-shrink: 0;
    }

    /* Results */
    .quick-actions-results {
        flex: 1;
        overflow-y: auto;
        padding: 8px;
        max-height: 50vh;
    }

    .quick-actions-results::-webkit-scrollbar {
        width: 6px;
    }

    .quick-actions-results::-webkit-scrollbar-thumb {
        background: #dee2e6;
        border-radius: 3px;
    }

    /* Category Header */
    .quick-action-category-header {
        padding: 8px 12px 4px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #868e96;
    }

    /* Action Item */
    .quick-action-item {
        display: flex;
        align-items: center;
        padding: 10px 12px;
        border-radius: 8px;
        cursor: pointer;
        transition: background-color 0.1s ease;
        gap: 12px;
    }

    .quick-action-item:hover,
    .quick-action-item.highlighted {
        background-color: #f1f3f5;
    }

    .quick-action-item.highlighted {
        background-color: #e7f5ff;
    }

    .quick-action-item-icon {
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
        border-radius: 8px;
        color: #495057;
        font-size: 16px;
        flex-shrink: 0;
    }

    .quick-action-item.highlighted .quick-action-item-icon {
        background: #d0ebff;
        color: #1971c2;
    }

    .quick-action-item-content {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
    }

    .quick-action-item-label {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .quick-action-item-description {
        font-size: 12px;
        color: #868e96;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    .quick-action-item-shortcut {
        font-size: 11px;
        color: #adb5bd;
        background: #f1f3f5;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        flex-shrink: 0;
    }

    /* Phase 4: Star / Pin Toggle */
    .quick-action-star {
        display: flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border: none;
        background: transparent;
        color: #dee2e6;
        cursor: pointer;
        padding: 0;
        font-size: 14px;
        flex-shrink: 0;
        border-radius: 4px;
        transition: color 0.15s ease, background 0.15s ease;
    }

    .quick-action-star:hover {
        color: #f59f00;
        background: #fff3bf;
    }

    .quick-action-star.favorited {
        color: #f59f00;
    }

    .quick-action-star.favorited:hover {
        color: #e67700;
        background: #ffe8cc;
    }

    /* Phase 4: Analytics Section */
    .quick-actions-analytics {
        border-top: 1px solid #e9ecef;
        padding: 12px 16px;
        background: #fafbfc;
    }

    .quick-actions-analytics-global {
        background: #f0f4ff;
    }

    .quick-actions-analytics-header {
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #868e96;
        margin-bottom: 8px;
    }

    .quick-actions-analytics-body {
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
    }

    .qa-stat {
        display: flex;
        flex-direction: column;
        align-items: center;
        padding: 6px 12px;
        background: #fff;
        border-radius: 6px;
        border: 1px solid #e9ecef;
        min-width: 80px;
    }

    .qa-stat-value {
        font-size: 18px;
        font-weight: 700;
        color: #212529;
        line-height: 1.2;
    }

    .qa-stat-label {
        font-size: 10px;
        color: #868e96;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }

    .qa-stat-section {
        flex: 1;
        min-width: 150px;
    }

    .qa-stat-section-title {
        display: block;
        font-size: 10px;
        font-weight: 600;
        color: #868e96;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        margin-bottom: 4px;
    }

    .qa-stat-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 2px 0;
        font-size: 12px;
    }

    .qa-stat-row-label {
        color: #495057;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 180px;
    }

    .qa-stat-row-count {
        color: #868e96;
        font-weight: 500;
        flex-shrink: 0;
        margin-left: 8px;
    }

    /* No Results */
    .quick-actions-no-results {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 40px 20px;
        color: #adb5bd;
    }

    /* Footer */
    .quick-actions-footer {
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 16px;
        padding: 8px 16px;
        border-top: 1px solid #e9ecef;
        background: #f8f9fa;
    }

    .quick-actions-footer-hint {
        font-size: 11px;
        color: #868e96;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .quick-actions-footer-hint kbd {
        display: inline-block;
        padding: 1px 5px;
        font-size: 10px;
        font-family: monospace;
        color: #495057;
        background: #fff;
        border: 1px solid #dee2e6;
        border-radius: 3px;
        line-height: 1.4;
    }

    /* Phase 4: Pulse animation for first-visit ⚡ button */
    .qa-pulse {
        animation: qa-pulse-ring 2s ease-out 1;
    }

    @keyframes qa-pulse-ring {
        0% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.6);
        }
        50% {
            box-shadow: 0 0 0 8px rgba(255, 193, 7, 0);
        }
        100% {
            box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
        }
    }

    /* Body state when panel is open */
    body.quick-actions-open {
        overflow: hidden;
    }
</style>
</div>