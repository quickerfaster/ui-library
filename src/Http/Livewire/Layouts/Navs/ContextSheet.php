<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

/**
 * Mobile context sheet — slide-up panel showing sub-items for the active
 * context group.
 *
 * Items are received as props from NavigationLayout (same data source
 * as the desktop sidebar). The sheet simply toggles open/close.
 */
class ContextSheet extends Component
{
    /** @var string|null */
    public ?string $contextKey = null;

    /** @var string */
    public string $contextLabel = '';

    /** @var string */
    public string $contextIcon = '';

    /** @var array */
    public array $items = [];

    /** @var bool */
    public bool $isOpen = false;

    protected $listeners = [
        'openContextSheet' => 'open',
        'closeContextSheet' => 'close',
    ];

    public function mount(
        ?string $contextKey = null,
        string $contextLabel = '',
        string $contextIcon = '',
        array $items = []
    ): void {
        $this->contextKey = $contextKey;
        $this->contextLabel = $contextLabel;
        $this->contextIcon = $contextIcon;
        $this->items = $items;
    }

    public function open(): void
    {
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
    }

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