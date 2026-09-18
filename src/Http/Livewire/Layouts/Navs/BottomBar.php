<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

/**
 * Mobile bottom tab bar — renders context group tabs.
 *
 * Alpine-free: all interactivity uses native onclick handlers with
 * window.Livewire.dispatch() or wire:click. No x-data, x-show, or
 * @click directives — eliminates Alpine initialization quirks on
 * Livewire-morphed DOM elements.
 */
class BottomBar extends Component
{
    public array $contextGroups = [];
    public ?string $activeContext = null;
    public int $maxVisible = 4;
    public string $moduleName = '';
    public bool $overflowOpen = false;

    protected $listeners = [
        'bb-close-overflow' => 'closeOverflow',
    ];

    public function mount(
        array $contextGroups = [],
        ?string $activeContext = null,
        int $maxVisible = 4,
        string $moduleName = ''
    ): void {
        $this->contextGroups = $contextGroups;
        $this->activeContext = $activeContext;
        $this->maxVisible = max(1, $maxVisible);
        $this->moduleName = $moduleName;
    }

    public function getVisibleGroupsProperty(): array
    {
        return array_slice($this->contextGroups, 0, $this->maxVisible, true);
    }

    public function getOverflowGroupsProperty(): array
    {
        if (count($this->contextGroups) <= $this->maxVisible) {
            return [];
        }
        return array_slice($this->contextGroups, $this->maxVisible, null, true);
    }

    public function getHasOverflowProperty(): bool
    {
        return count($this->contextGroups) > $this->maxVisible;
    }

    public function resolveUrl(array $group): string
    {
        if (!empty($group['route']) && !str_contains($group['route'], '/')) {
            return route($group['route']);
        }
        if (!empty($group['route'])) {
            return url($group['route']);
        }
        if (!empty($group['url'])) {
            return url($group['url']);
        }
        return '#';
    }

    public function isItemActive(array $item): bool
    {
        if (!empty($item['route'])) {
            if (!str_contains($item['route'], '/')) {
                return request()->routeIs($item['route']);
            }
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

    public function getIsActiveInOverflowProperty(): bool
    {
        return isset($this->overflowGroups[$this->activeContext]);
    }

    public function openOverflow(): void
    {
        $this->overflowOpen = true;
    }

    public function closeOverflow(): void
    {
        $this->overflowOpen = false;
    }

    public function render()
    {
        return view('qf::livewire.navs.bottom-bar');
    }
}