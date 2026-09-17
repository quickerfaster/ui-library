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

    /** @var string|null */
    public ?string $contextLabel = '';

    /** @var string|null */
    public ?string $contextIcon = '';

    /** @var array */
    public array $items = [];

    /** @var bool */
    public bool $isOpen = false;

    /** @var int Bump to force Livewire snapshot regeneration. */
    public int $renderVersion = 3;

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

    /**
     * Determine if a sub-item is the currently active page.
     * Mirrors sidebar-item.blade.php logic — uses full URL comparison
     * for robustness across Livewire re-renders and sub-requests.
     */
    public function isItemActive(array $item): bool
    {
        // 1. Try route/URL matching (same approach as sidebar-item.blade.php)
        if (!empty($item['route'])) {
            // Named route (no slashes, e.g. 'hr.dashboard')
            if (!str_contains($item['route'], '/')) {
                return request()->routeIs($item['route']);
            }
            // URL path — compare full URLs for robustness
            $routePath = parse_url($item['route'], PHP_URL_PATH) ?? $item['route'];
            return request()->url() === url($routePath);
        }

        if (!empty($item['url'])) {
            $urlPath = parse_url($item['url'], PHP_URL_PATH) ?? $item['url'];
            return request()->url() === url($urlPath);
        }

        return false;
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