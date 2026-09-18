<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

/**
 * Mobile bottom tab bar — renders context group tabs.
 *
 * Replaces the old sidebar-item-duplicating BottomBar with a proper
 * mobile navigation surface. Context groups from navigation.php
 * appear as labeled icon tabs. The active tab shows a chevron (▲)
 * indicating sub-items are available via the ContextSheet.
 *
 * Overflow groups (beyond $maxVisible) are accessible via a "More"
 * tab that opens the OverflowSheet. Overflow visibility is controlled
 * via Livewire-native @if (\$overflowOpen) — no Alpine x-show, eliminating
 * FOUC flicker and DOM-morphing interference.
 */
class BottomBar extends Component
{
    /** @var array Context group definitions keyed by group slug. */
    public array $contextGroups = [];

    /** @var string|null The currently active context group key. */
    public ?string $activeContext = null;

    /** @var int Maximum visible tabs before overflow. */
    public int $maxVisible = 4;

    /** @var string Current module name (for wire:key scoping). */
    public string $moduleName = '';

    /** @var bool Whether the overflow "More" sheet is open. */
    public bool $overflowOpen = false;

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

    /**
     * Context groups that fit in the visible tab bar.
     */
    public function getVisibleGroupsProperty(): array
    {
        return array_slice($this->contextGroups, 0, $this->maxVisible, true);
    }

    /**
     * Context groups that overflow into the "More" sheet.
     */
    public function getOverflowGroupsProperty(): array
    {
        if (count($this->contextGroups) <= $this->maxVisible) {
            return [];
        }
        return array_slice($this->contextGroups, $this->maxVisible, null, true);
    }

    /**
     * Whether the overflow "More" tab should be shown.
     */
    public function getHasOverflowProperty(): bool
    {
        return count($this->contextGroups) > $this->maxVisible;
    }

    /**
     * Resolve a URL from a context group definition.
     */
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

    /**
     * Determine if a sub-item is the currently active page.
     */
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

    /**
     * Resolve a sub-item URL (for overflow sub-items).
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

    /**
     * Whether the active context is in the overflow group.
     */
    public function getIsActiveInOverflowProperty(): bool
    {
        return isset($this->overflowGroups[$this->activeContext]);
    }

    /**
     * Open the overflow "More" sheet.
     */
    public function openOverflow(): void
    {
        $this->overflowOpen = true;
    }

    /**
     * Close the overflow "More" sheet.
     */
    public function closeOverflow(): void
    {
        $this->overflowOpen = false;
    }

    /**
     * Dispatch the openContextSheet event for the handle bar.
     */
    public function openContext(): void
    {
        $this->dispatch('openContextSheet');
    }

    /**
     * Navigate to a URL via SPA (used by handle bar when context has no items).
     */
    public function goTo(string $url): void
    {
        $this->redirect($url, navigate: true);
    }

    public function render()
    {
        return view('qf::livewire.navs.bottom-bar');
    }
}