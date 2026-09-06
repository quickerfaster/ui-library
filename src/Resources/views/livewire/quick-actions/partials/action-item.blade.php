{{-- Reusable quick action item with star toggle + shortcut badge --}}
@props([
    'action',
    'index' => 0,
    'isFavorited' => false,
    'shortcutBadge' => null,
    'showStar' => true,
    'showShortcut' => true,
    'compact' => false,
    'clickHandler' => 'selectResult',
])

@php
    $actionId = $action['id'] ?? $action['key'] ?? '';
    $url = null;
    if (!empty($action['route'])) {
        try { $url = route($action['route']); } catch (\Exception $e) {}
    }
    if (!$url && !empty($action['url'])) {
        $url = url($action['url']);
    }
@endphp

<div class="quick-action-item {{ $compact ? 'quick-action-item-compact' : '' }}"
     data-label="{{ $action['label'] }}"
     data-description="{{ $action['description'] ?? '' }}"
     data-keywords="{{ implode(' ', $action['keywords'] ?? []) }}"
     data-category="{{ $action['category'] ?? '' }}"
     data-module="{{ $action['module'] ?? '' }}"
     data-action-id="{{ $actionId }}"
     @if ($clickHandler === 'selectResult')
     wire:click="selectResult({{ $index }})"
     @elseif ($clickHandler === 'executeActionById')
     wire:click="executeActionById('{{ $actionId }}')"
     @else
     wire:click="{{ $clickHandler }}('{{ $actionId }}')"
     @endif
     role="button"
     tabindex="0"
>
    {{-- Star / Pin Toggle --}}
    @if ($showStar)
    <button type="button"
            class="quick-action-star {{ $isFavorited ? 'favorited' : '' }}"
            wire:click.stop="toggleFavorite('{{ $actionId }}')"
            title="{{ $isFavorited ? 'Unpin action' : 'Pin action' }}"
            aria-label="{{ $isFavorited ? 'Unpin' : 'Pin' }} {{ $action['label'] }}">
        <i class="{{ $isFavorited ? 'fas' : 'far' }} fa-star"></i>
    </button>
    @endif

    {{-- Icon --}}
    <div class="quick-action-item-icon">
        <i class="{{ $action['icon'] ?? 'fas fa-bolt' }}"></i>
    </div>

    {{-- Content --}}
    <div class="quick-action-item-content">
        <span class="quick-action-item-label">{{ $action['label'] }}</span>
        @if (!empty($action['description']))
            <span class="quick-action-item-description">{{ $action['description'] }}</span>
        @endif
    </div>

    {{-- Shortcut Badge --}}
    @if ($showShortcut && $shortcutBadge)
        <span class="quick-action-item-shortcut">{{ $shortcutBadge }}</span>
    @elseif ($showShortcut && !empty($action['shortcut']))
        <span class="quick-action-item-shortcut">{{ $action['shortcut'] }}</span>
    @endif
</div>