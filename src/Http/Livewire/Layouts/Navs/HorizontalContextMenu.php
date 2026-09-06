<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;

class HorizontalContextMenu extends Component
{
    public array $items = [];
    public string $position = 'left';
    public bool $allowTypeSwitch = false;
    public ?string $currentModelName = null;
    public int $maxVisibleItems = 7;

    // ------------------------------------------------------------------
    //  Phase 2: Cross-Context Dropdowns
    // ------------------------------------------------------------------

    /** @var array All context groups (key => group config), used when $showAllContexts is true. */
    public array $contextGroups = [];

    /** @var array All context items grouped by context key (key => [items]), used when $showAllContexts is true. */
    public array $contextItems = [];

    /** @var string|null The currently active context group key. */
    public ?string $activeContext = null;

    /** @var bool When true, render all context groups as dropdown triggers instead of a flat item list. */
    public bool $showAllContexts = false;

    // ------------------------------------------------------------------

    public function mount(
        array $items,
        string $position = 'left',
        bool $allowTypeSwitch = false,
        ?string $currentModelName = null,
        ?int $maxVisibleItems = null,
        array $contextGroups = [],
        array $contextItems = [],
        ?string $activeContext = null,
        bool $showAllContexts = false,
    ) {
        $this->items = $items;
        $this->position = $position;
        $this->allowTypeSwitch = $allowTypeSwitch;
        $this->currentModelName = $currentModelName;

        // Resolve maxVisibleItems: explicit prop > per-module layout config > global config > default
        $this->maxVisibleItems = $maxVisibleItems
            ?? (int) config('ui-library.navigation.context_menu.max_visible_items', 7);

        // A value of 0 or negative means "no overflow" — show all items
        if ($this->maxVisibleItems <= 0) {
            $this->maxVisibleItems = PHP_INT_MAX;
        }

        // Phase 2
        $this->contextGroups = $contextGroups;
        $this->contextItems = $contextItems;
        $this->activeContext = $activeContext;
        $this->showAllContexts = $showAllContexts;
    }

    // ------------------------------------------------------------------
    //  Visible / overflow split  (with active-item promotion)
    // ------------------------------------------------------------------

    /**
     * Items shown inline in the horizontal bar.
     *
     * Normally the first $maxVisibleItems items, BUT if the active item
     * falls into the overflow we "promote" it — keeping the first
     * maxVisibleItems-1 items and appending the active item.
     */
    public function getVisibleItemsProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxVisibleItems) {
            return $items;
        }

        $activeKey = $this->findActiveItemKey();

        if ($activeKey !== null) {
            $keys = $items->keys()->values();
            $activeIndex = $keys->search($activeKey);

            if ($activeIndex !== false && $activeIndex >= $this->maxVisibleItems) {
                // The active item is in the overflow — promote it.
                // Keep the first (maxVisibleItems - 1) items plus the active item.
                $visible = $items->take($this->maxVisibleItems - 1);
                $activeItem = $items->only([$activeKey]);

                return $visible->merge($activeItem);
            }
        }

        return $items->take($this->maxVisibleItems);
    }

    /**
     * Overflow items — everything NOT in getVisibleItemsProperty().
     */
    public function getOverflowItemsProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxVisibleItems) {
            return collect();
        }

        $visibleKeys = $this->getVisibleItemsProperty()->keys()->toArray();

        return $items->reject(fn($item, $key) => in_array($key, $visibleKeys, true));
    }

    // ------------------------------------------------------------------
    //  Active-item detection
    // ------------------------------------------------------------------

    /**
     * Determine whether the given item is the currently active/current page.
     * Extracted from the Blade template to avoid duplication between visible
     * and overflow rendering.
     */
    public function isItemActive(array $item): bool
    {
        $isActive = false;

        if (isset($item['route'])) {
            if (!str_contains($item['route'], '/')) {
                $isActive = request()->routeIs($item['route']);
            } else {
                $isActive = request()->url() === url($item['route']);
            }
        } elseif (isset($item['url'])) {
            $isActive = request()->url() === url($item['url']);
        }

        if (!$isActive && !empty($this->currentModelName)) {
            $itemKey = $item['key'] ?? '';
            $itemLabel = $item['label'] ?? '';
            $normalizedModel = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $this->currentModelName));
            $isActive = ($itemKey === $normalizedModel) || (strtolower($itemLabel) === strtolower($this->currentModelName));
        }

        return $isActive;
    }

    /**
     * Resolve the URL for a navigation item.
     */
    public function resolveItemUrl(array $item): string
    {
        $isNamedRoute = isset($item['route']) && !str_contains($item['route'], '/');

        if ($isNamedRoute) {
            return route($item['route']);
        }

        if (isset($item['route'])) {
            return url($item['route']);
        }

        if (isset($item['url'])) {
            return url($item['url']);
        }

        return '#';
    }

    // ------------------------------------------------------------------
    //  Phase 2: Per-context-group visible/overflow splitting
    // ------------------------------------------------------------------

    /**
     * Get visible items for a specific context group (applying Phase 1 overflow logic).
     *
     * @param string $contextKey
     * @return Collection
     */
    public function getVisibleItemsForContext(string $contextKey): Collection
    {
        $items = collect($this->contextItems[$contextKey] ?? []);

        if ($items->count() <= $this->maxVisibleItems) {
            return $items;
        }

        $activeKey = $this->findActiveItemKeyInContext($contextKey);

        if ($activeKey !== null) {
            $keys = $items->keys()->values();
            $activeIndex = $keys->search($activeKey);

            if ($activeIndex !== false && $activeIndex >= $this->maxVisibleItems) {
                // The active item is in the overflow — promote it.
                $visible = $items->take($this->maxVisibleItems - 1);
                $activeItem = $items->only([$activeKey]);

                return $visible->merge($activeItem);
            }
        }

        return $items->take($this->maxVisibleItems);
    }

    /**
     * Get overflow items for a specific context group.
     *
     * @param string $contextKey
     * @return Collection
     */
    public function getOverflowItemsForContext(string $contextKey): Collection
    {
        $items = collect($this->contextItems[$contextKey] ?? []);

        if ($items->count() <= $this->maxVisibleItems) {
            return collect();
        }

        $visibleKeys = $this->getVisibleItemsForContext($contextKey)->keys()->toArray();

        return $items->reject(fn($item, $key) => in_array($key, $visibleKeys, true));
    }

    /**
     * Find the array key of the currently active item within a specific context group,
     * or null if none matches.
     */
    private function findActiveItemKeyInContext(string $contextKey): ?string
    {
        $items = $this->contextItems[$contextKey] ?? [];

        foreach ($items as $key => $item) {
            if ($this->isItemActive($item)) {
                return (string) $key;
            }
        }

        return null;
    }

    // ------------------------------------------------------------------
    //  Helpers
    // ------------------------------------------------------------------

    /**
     * Find the array key of the currently active item, or null if none matches.
     */
    private function findActiveItemKey(): ?string
    {
        foreach ($this->items as $key => $item) {
            if ($this->isItemActive($item)) {
                return (string) $key;
            }
        }

        return null;
    }

    public function switchToSidebar(): void
    {
        session(['context_menu_type' => 'sidebar']);
        $this->dispatch('doReload');
    }

    public function render()
    {
        return view('qf::livewire.navs.horizontal-context-menu');
    }
}