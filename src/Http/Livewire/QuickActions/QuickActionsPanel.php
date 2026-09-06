<?php

namespace QuickerFaster\UILibrary\Http\Livewire\QuickActions;

use Illuminate\Support\Facades\Auth;
use Livewire\Component;
use QuickerFaster\UILibrary\Models\UserActionHistory;
use QuickerFaster\UILibrary\Models\UserFavoriteAction;
use QuickerFaster\UILibrary\Services\QuickActions\ActionRegistry;
use QuickerFaster\UILibrary\Services\QuickActions\ActionTracker;
use QuickerFaster\UILibrary\Services\QuickActions\RankingEngine;

/**
 * QuickActionsPanel — Command Palette Livewire Component
 *
 * Renders a modal overlay with a search input that filters registered
 * quick actions client-side. Triggered by Cmd+K / Ctrl+K or by clicking
 * the search icon button in the top navigation bar.
 *
 * Phase 2: Actions are ranked by personalized usage (recency + frequency)
 * via RankingEngine, and each execution is recorded via ActionTracker.
 *
 * Phase 4: Favorites/pinning, keyboard shortcut badges (⌘1–⌘9),
 * analytics data, and first-visit tracking.
 */
class QuickActionsPanel extends Component
{
    /** @var bool Whether the command palette modal is visible. */
    public bool $isOpen = false;

    /** @var string Current search query. */
    public string $query = '';

    /** @var array All registered actions loaded from the registry. */
    public array $actions = [];

    /** @var int Currently highlighted result index (keyboard navigation). */
    public int $selectedIndex = -1;

    /** @var string Placeholder text for the search input. */
    public string $placeholder;

    /** @var string Keyboard shortcut hint displayed in the UI. */
    public string $shortcutHint;

    /** @var bool Whether the quick actions feature is enabled. */
    public bool $enabled = true;

    // ------------------------------------------------------------------
    //  Phase 4: Favorites / Pinning
    // ------------------------------------------------------------------

    /** @var array<string> Set of favorited action IDs for the current user. */
    public array $favorites = [];

    // ------------------------------------------------------------------
    //  Phase 4: Keyboard Shortcuts
    // ------------------------------------------------------------------

    /** @var array<int, string> Map of rank position (0-based) → action ID for Cmd+1..9. */
    public array $shortcutMap = [];

    // ------------------------------------------------------------------
    //  Phase 4: Analytics
    // ------------------------------------------------------------------

    /** @var array Personal usage stats for the current user. */
    public array $personalStats = [];

    /** @var array Global usage stats (admin only). */
    public array $globalStats = [];

    /** @var bool Whether the current user can see global analytics. */
    public bool $canViewAnalytics = false;

    /**
     * Listen for Livewire events.
     *
     * @return array<string, string>
     */
    protected function getListeners(): array
    {
        return [
            'openQuickActions' => 'open',
            'closeQuickActions' => 'close',
            'toggleFavorite' => 'toggleFavorite',
            'executeActionById' => 'executeActionById',
        ];
    }

    /**
     * Initialize the component.
     *
     * @return void
     */
    public function mount(): void
    {
        $config = config('ui-library.quick_actions', []);

        $this->enabled = $config['enabled'] ?? true;

        if (!$this->enabled) {
            return;
        }

        $paletteConfig = $config['command_palette'] ?? [];
        $this->placeholder = $paletteConfig['placeholder'] ?? 'Search actions, records, pages...';
        $this->shortcutHint = $paletteConfig['shortcut'] ?? 'Cmd+K';

        $this->loadActions();
        $this->loadFavorites();
        $this->buildShortcutMap();
        $this->loadAnalytics();
    }

    /**
     * Load all registered actions from the registry.
     *
     * @return void
     */
    protected function loadActions(): void
    {
        /** @var ActionRegistry $registry */
        $registry = app(ActionRegistry::class);
        $actions = $registry->authorizedFor();

        if (Auth::check()) {
            /** @var RankingEngine $ranking */
            $ranking = app(RankingEngine::class);
            $actions = $ranking->score($actions, Auth::id());
        }

        // Phase 4: Interleave favorites at the top of the list.
        $actions = $this->interleaveFavorites($actions);

        $this->actions = $actions;
    }

    /**
     * Load the current user's favorited action IDs.
     *
     * @return void
     */
    protected function loadFavorites(): void
    {
        $this->favorites = [];

        if (!Auth::check()) {
            return;
        }

        $this->favorites = UserFavoriteAction::query()
            ->where('user_id', Auth::id())
            ->pluck('action_id')
            ->toArray();
    }

    /**
     * Interleave favorited actions at the top of the list.
     *
     * Pinned actions appear first (in the order they were favorited),
     * followed by the remaining ranked actions.
     *
     * @param  array<int, array> $actions
     * @return array<int, array>
     */
    protected function interleaveFavorites(array $actions): array
    {
        if (empty($this->favorites)) {
            return $actions;
        }

        $favoriteSet = array_flip($this->favorites);

        $pinned = [];
        $rest = [];

        foreach ($actions as $action) {
            $id = $action['id'] ?? $action['key'] ?? null;
            if ($id && isset($favoriteSet[$id])) {
                $action['_pinned'] = true;
                $pinned[] = $action;
            } else {
                $action['_pinned'] = false;
                $rest[] = $action;
            }
        }

        // Sort pinned by the order they were favorited (preserve DB order).
        usort($pinned, function (array $a, array $b) {
            $posA = array_search($a['id'] ?? $a['key'] ?? '', $this->favorites);
            $posB = array_search($b['id'] ?? $b['key'] ?? '', $this->favorites);
            return ($posA === false ? PHP_INT_MAX : $posA) <=> ($posB === false ? PHP_INT_MAX : $posB);
        });

        return array_merge($pinned, $rest);
    }

    /**
     * Toggle a favorite/pin for the given action ID.
     *
     * @param  string $actionId
     * @return void
     */
    public function toggleFavorite(string $actionId): void
    {
        if (!Auth::check()) {
            return;
        }

        $existing = UserFavoriteAction::query()
            ->where('user_id', Auth::id())
            ->where('action_id', $actionId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            UserFavoriteAction::create([
                'user_id'   => Auth::id(),
                'action_id' => $actionId,
            ]);
        }

        $this->loadFavorites();
        $this->loadActions();
        $this->buildShortcutMap();
    }

    /**
     * Check whether the given action ID is favorited by the current user.
     *
     * @param  string $actionId
     * @return bool
     */
    public function isFavorited(string $actionId): bool
    {
        return in_array($actionId, $this->favorites, true);
    }

    // ------------------------------------------------------------------
    //  Phase 4: Keyboard Shortcuts
    // ------------------------------------------------------------------

    /**
     * Build the shortcut map assigning ⌘1–⌘9 to the top 9 actions.
     *
     * @return void
     */
    protected function buildShortcutMap(): void
    {
        $this->shortcutMap = [];

        $top = array_slice($this->actions, 0, 9);

        foreach ($top as $index => $action) {
            $id = $action['id'] ?? $action['key'] ?? null;
            if ($id) {
                $this->shortcutMap[$index] = $id;
            }
        }
    }

    /**
     * Get the shortcut badge label for an action at the given rank position.
     *
     * @param  int         $rank  0-based position in the full action list.
     * @return string|null        e.g. '⌘1', or null if beyond position 8.
     */
    public function getShortcutBadge(int $rank): ?string
    {
        if ($rank < 0 || $rank > 8) {
            return null;
        }

        $isMac = isset($_SERVER['HTTP_USER_AGENT']) && stripos($_SERVER['HTTP_USER_AGENT'], 'Mac') !== false;
        $modifier = $isMac ? '⌘⇧' : 'Ctrl+Shift+';

        return $modifier . ($rank + 1);
    }

    // ------------------------------------------------------------------
    //  Phase 4: Analytics
    // ------------------------------------------------------------------

    /**
     * Load personal and global analytics data.
     *
     * @return void
     */
    protected function loadAnalytics(): void
    {
        if (!Auth::check()) {
            return;
        }

        $this->loadPersonalStats();
        $this->loadGlobalStats();
    }

    /**
     * Load personal usage stats: actions per day, top actions.
     *
     * @return void
     */
    protected function loadPersonalStats(): void
    {
        $userId = Auth::id();

        // Total actions executed
        $total = UserActionHistory::query()
            ->where('user_id', $userId)
            ->count();

        // Actions per day (last 30 days)
        $perDay = UserActionHistory::query()
            ->where('user_id', $userId)
            ->where('executed_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(executed_at) as date, COUNT(*) as count')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get()
            ->toArray();

        // Top 5 actions
        $topActions = UserActionHistory::query()
            ->where('user_id', $userId)
            ->selectRaw('action_id, COUNT(*) as count')
            ->groupBy('action_id')
            ->orderByDesc('count')
            ->limit(5)
            ->get()
            ->map(function ($row) {
                /** @var ActionRegistry $registry */
                $registry = app(ActionRegistry::class);
                $action = $registry->findByKey($row->action_id);
                return [
                    'action_id' => $row->action_id,
                    'label'     => $action['label'] ?? $row->action_id,
                    'count'     => $row->count,
                ];
            })
            ->toArray();

        $this->personalStats = [
            'total'       => $total,
            'per_day'     => $perDay,
            'top_actions' => $topActions,
        ];
    }

    /**
     * Load global usage stats (admin only).
     *
     * @return void
     */
    protected function loadGlobalStats(): void
    {
        $user = Auth::user();

        // Check if user can view analytics (has admin role or explicit permission).
        $this->canViewAnalytics = false;

        if (method_exists($user, 'hasRole') && $user->hasRole('admin')) {
            $this->canViewAnalytics = true;
        } elseif (method_exists($user, 'can') && $user->can('view_quick_actions_analytics')) {
            $this->canViewAnalytics = true;
        }

        if (!$this->canViewAnalytics) {
            $this->globalStats = [];
            return;
        }

        // Most-used actions across all users
        $topGlobal = UserActionHistory::query()
            ->selectRaw('action_id, COUNT(*) as count, COUNT(DISTINCT user_id) as unique_users')
            ->groupBy('action_id')
            ->orderByDesc('count')
            ->limit(10)
            ->get()
            ->map(function ($row) {
                /** @var ActionRegistry $registry */
                $registry = app(ActionRegistry::class);
                $action = $registry->findByKey($row->action_id);
                return [
                    'action_id'    => $row->action_id,
                    'label'        => $action['label'] ?? $row->action_id,
                    'count'        => $row->count,
                    'unique_users' => $row->unique_users,
                ];
            })
            ->toArray();

        // Total actions across all users
        $totalGlobal = UserActionHistory::query()->count();

        // Total unique users
        $uniqueUsers = UserActionHistory::query()
            ->distinct('user_id')
            ->count('user_id');

        $this->globalStats = [
            'total_actions'  => $totalGlobal,
            'unique_users'   => $uniqueUsers,
            'top_actions'    => $topGlobal,
        ];
    }

    /**
     * Open the command palette.
     *
     * @return void
     */
    public function open(): void
    {
        if (!$this->enabled) {
            return;
        }

        $this->isOpen = true;
        $this->query = '';
        $this->selectedIndex = -1;

        // Reload actions in case config changed
        $this->loadActions();
        $this->loadFavorites();
        $this->buildShortcutMap();
        $this->loadAnalytics();
    }

    /**
     * Close the command palette.
     *
     * @return void
     */
    public function close(): void
    {
        $this->isOpen = false;
        $this->query = '';
        $this->selectedIndex = -1;
    }

    /**
     * Handle search query updates (debounced by Livewire).
     *
     * @param  string $value
     * @return void
     */
    public function updatedQuery(string $value): void
    {
        $this->selectedIndex = -1;
    }

    /**
     * Execute the action at the given index in the filtered results.
     *
     * @param  int    $index
     * @return void
     */
    public function selectResult(int $index): void
    {
        $filtered = $this->getFilteredActions();

        if (!isset($filtered[$index])) {
            return;
        }

        $this->executeAction($filtered[$index]);
    }

    /**
     * Execute an action by its ID.
     *
     * @param  string $id
     * @return void
     */
    public function executeActionById(string $id): void
    {
        /** @var ActionRegistry $registry */
        $registry = app(ActionRegistry::class);
        $action = $registry->findByKey($id);

        if (!$action) {
            return;
        }

        $this->executeAction($action);
    }

    /**
     * Execute the given action.
     *
     * @param  array  $action
     * @return void
     */
    protected function executeAction(array $action): void
    {
        $this->close();

        // Record this execution for personalized ranking (Phase 2).
        $actionId = $action['id'] ?? $action['key'] ?? null;
        if ($actionId) {
            /** @var ActionTracker $tracker */
            $tracker = app(ActionTracker::class);
            $tracker->record((string) $actionId);
        }

        $type = $action['action'] ?? 'navigate';

        switch ($type) {
            case 'navigate':
                $url = $this->resolveActionUrl($action);
                if ($url) {
                    $this->dispatch('navigate', url: $url);
                }
                break;

            case 'event':
                $event = $action['livewire_event'] ?? null;
                if ($event) {
                    $this->dispatch($event);
                }
                break;

            case 'drawer':
                $event = $action['livewire_event'] ?? 'openDrawer';
                $this->dispatch($event);
                break;

            default:
                // Fallback: try navigate
                $url = $this->resolveActionUrl($action);
                if ($url) {
                    $this->dispatch('navigate', url: $url);
                }
                break;
        }
    }

    /**
     * Resolve the URL for a navigation action.
     *
     * @param  array       $action
     * @return string|null
     */
    protected function resolveActionUrl(array $action): ?string
    {
        // Prefer named route
        if (!empty($action['route'])) {
            try {
                return route($action['route']);
            } catch (\Exception $e) {
                // Route not found as a named route; treat as a URL path
                if (str_starts_with($action['route'], '/')) {
                    return url($action['route']);
                }
            }
        }

        // Fall back to direct URL
        if (!empty($action['url'])) {
            return url($action['url']);
        }

        return null;
    }

    /**
     * Get actions filtered by the current search query.
     * This runs server-side for the initial render; client-side JS
     * handles subsequent filtering for responsiveness.
     *
     * @return array<int, array>
     */
    public function getFilteredActions(): array
    {
        if (empty(trim($this->query))) {
            return $this->actions;
        }

        $query = mb_strtolower(trim($this->query));
        $maxResults = (int) config('ui-library.quick_actions.command_palette.max_action_results', 20);

        $filtered = [];

        foreach ($this->actions as $action) {
            if (count($filtered) >= $maxResults) {
                break;
            }

            // Search in label
            if (str_contains(mb_strtolower($action['label']), $query)) {
                $filtered[] = $action;
                continue;
            }

            // Search in description
            if (!empty($action['description']) && str_contains(mb_strtolower($action['description']), $query)) {
                $filtered[] = $action;
                continue;
            }

            // Search in keywords
            if (!empty($action['keywords'])) {
                foreach ($action['keywords'] as $keyword) {
                    if (str_contains(mb_strtolower($keyword), $query)) {
                        $filtered[] = $action;
                        continue 2;
                    }
                }
            }

            // Search in category
            if (!empty($action['category']) && str_contains(mb_strtolower($action['category']), $query)) {
                $filtered[] = $action;
                continue;
            }

            // Search in module
            if (!empty($action['module']) && str_contains(mb_strtolower($action['module']), $query)) {
                $filtered[] = $action;
            }
        }

        return $filtered;
    }

    /**
     * Render the component.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('qf::livewire.quick-actions.quick-actions-panel', [
            'filteredActions' => $this->getFilteredActions(),
        ]);
    }
}