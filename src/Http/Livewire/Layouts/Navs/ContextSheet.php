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
        'openContextSheet' => 'open',
        'closeContextSheet' => 'close',
    ];

    /**
     * Open the sheet with context group data from a Livewire event.
     *
     * Livewire 3 passes $dispatch('openContextSheet', {...}) data
     * as the first argument to the listener method.
     */
    public function open($payload = null): void
    {
        if (is_array($payload)) {
            $this->contextKey = $payload['key'] ?? null;
            $this->contextLabel = $payload['label'] ?? '';
            $this->contextIcon = $payload['icon'] ?? '';
            $this->items = $payload['items'] ?? [];
        }
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