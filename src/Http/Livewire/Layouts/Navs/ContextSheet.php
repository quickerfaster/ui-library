<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

/**
 * Mobile context sheet — slide-up panel showing sub-items for the active
 * context group.
 *
 * Triggered by tapping the handle bar above the BottomBar. Mirrors the
 * desktop sidebar behavior: selecting a context group reveals its
 * sub-navigation items in a mobile-friendly slide-up sheet.
 */
class ContextSheet extends Component
{
    /** @var string|null The active context group key. */
    public ?string $contextKey = null;

    /** @var string Context group display label. */
    public string $contextLabel = '';

    /** @var string Context group icon class. */
    public string $contextIcon = '';

    /** @var array Sub-navigation items for the active context group. */
    public array $items = [];

    /** @var bool Whether the sheet is currently open. */
    public bool $isOpen = false;

    protected $listeners = [
        'closeContextSheet' => 'close',
    ];

    /**
     * Open the sheet with context group data.
     *
     * Called directly from Alpine via $wire.call('openSheet', ...).
     * Accepts individual parameters to avoid Livewire container
     * resolution issues with array type hints.
     */
    public function openSheet($key = null, $label = '', $icon = '', $items = []): void
    {
        $this->contextKey = $key;
        $this->contextLabel = $label;
        $this->contextIcon = $icon;
        $this->items = is_array($items) ? $items : [];
        $this->isOpen = true;
    }

    /**
     * Close the sheet.
     */
    public function close(): void
    {
        $this->isOpen = false;
    }

    /**
     * Resolve a URL from a navigation item.
     */
    public function resolveItemUrl(array $item): string
    {
        if (!empty($item['route']) && !str_contains($item['route'], '/')) {
            return route($item['route']);
        }
        if (!empty($item['route'])) {
            return url($item['route']);
        }
        if (!empty($item['url'])) {
            return url($item['url']);
        }
        return '#';
    }

    public function render()
    {
        return view('qf::livewire.navs.context-sheet');
    }
}