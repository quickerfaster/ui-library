<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Attributes\On;
use Livewire\Component;

class WorkspaceTabs extends Component
{
    public array $openTabs = [];

    public ?string $activeTabId = null;

    public array $recentlyClosed = [];

    public int $maxTabs = 15;

    public function mount(): void
    {
        $this->openTabs = session('workspace_tabs', []);
        $this->activeTabId = session('workspace_active_tab');
        $this->recentlyClosed = session('workspace_recently_closed', []);
    }

    public function render()
    {
        return view('qf::livewire.navs.workspace-tabs');
    }

    #[On('switch-tab')]
    public function switchTab($tabId): void
    {
        $this->activeTabId = $tabId;
        $this->persist();
    }

    #[On('close-tab')]
    public function closeTab($tabId): void
    {
        $index = $this->findTabIndex($tabId);

        if ($index === null) {
            return;
        }

        $closed = $this->openTabs[$index];

        array_splice($this->openTabs, $index, 1);

        array_unshift($this->recentlyClosed, $closed);
        $this->recentlyClosed = array_slice($this->recentlyClosed, 0, 10);

        if ($this->activeTabId === $tabId) {
            $this->activateAdjacentTab($index);
        }

        $this->persist();
    }

    #[On('close-active-tab')]
    public function closeActiveTab(): void
    {
        if ($this->activeTabId !== null) {
            $this->closeTab($this->activeTabId);
        }
    }

    #[On('reopen-last-closed-tab')]
    public function reopenLastClosed(): void
    {
        if (empty($this->recentlyClosed)) {
            return;
        }

        $tab = array_shift($this->recentlyClosed);

        // If the same URL is already open, just focus it again.
        foreach ($this->openTabs as $existing) {
            if (($existing['url'] ?? null) === ($tab['url'] ?? null)) {
                $this->activeTabId = $existing['id'];
                $this->persist();

                return;
            }
        }

        if (count($this->openTabs) >= $this->maxTabs) {
            $this->evictLeastRecentlyUsed();
        }

        $this->openTabs[] = $tab;
        $this->activeTabId = $tab['id'];
        $this->persist();
    }

    #[On('openWorkspaceTab')]
    public function openTab($label, $url, $icon = null, $context = null): void
    {
        // Deduplicate by URL — focus the existing tab instead of opening a new one.
        foreach ($this->openTabs as $existing) {
            if (($existing['url'] ?? null) === $url) {
                $this->activeTabId = $existing['id'];
                $this->persist();

                return;
            }
        }

        if (count($this->openTabs) >= $this->maxTabs) {
            $this->evictLeastRecentlyUsed();
        }

        $tabId = uniqid('tab_');

        $this->openTabs[] = [
            'id' => $tabId,
            'label' => $label,
            'url' => $url,
            'icon' => $icon,
            'context' => $context,
        ];

        $this->activeTabId = $tabId;
        $this->persist();
    }

    #[On('close-others')]
    public function closeOthers($keepTabId): void
    {
        if ($this->findTabIndex($keepTabId) === null) {
            return;
        }

        $this->openTabs = array_values(array_filter($this->openTabs, function ($tab) use ($keepTabId) {
            return ($tab['id'] ?? null) === $keepTabId;
        }));

        $this->activeTabId = $keepTabId;
        $this->persist();
    }

    #[On('close-all-to-right')]
    public function closeAllToRight($tabId): void
    {
        $index = $this->findTabIndex($tabId);

        if ($index === null) {
            return;
        }

        $this->openTabs = array_slice($this->openTabs, 0, $index + 1);

        if ($this->findTabIndex($this->activeTabId) === null) {
            $this->activeTabId = $tabId;
        }

        $this->persist();
    }

    #[On('close-all')]
    public function closeAll(): void
    {
        $this->openTabs = [];
        $this->activeTabId = null;
        $this->persist();
    }

    protected function persist(): void
    {
        session([
            'workspace_tabs' => $this->openTabs,
            'workspace_active_tab' => $this->activeTabId,
            'workspace_recently_closed' => $this->recentlyClosed,
        ]);
    }

    protected function findTabIndex($tabId): ?int
    {
        foreach ($this->openTabs as $index => $tab) {
            if (($tab['id'] ?? null) === $tabId) {
                return $index;
            }
        }

        return null;
    }

    protected function activateAdjacentTab(int $closedIndex): void
    {
        if (empty($this->openTabs)) {
            $this->activeTabId = null;

            return;
        }

        // Prefer the tab that slid into the closed position (right neighbour),
        // otherwise fall back to the last tab (left neighbour).
        $adjacent = $this->openTabs[$closedIndex] ?? $this->openTabs[count($this->openTabs) - 1];

        $this->activeTabId = $adjacent['id'];
    }

    protected function evictLeastRecentlyUsed(): void
    {
        foreach ($this->openTabs as $index => $tab) {
            if (($tab['id'] ?? null) !== $this->activeTabId) {
                array_splice($this->openTabs, $index, 1);

                return;
            }
        }

        // Defensive fallback: if every tab is somehow active, evict the first.
        if (! empty($this->openTabs)) {
            array_shift($this->openTabs);
        }
    }
}
