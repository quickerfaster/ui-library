<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use QuickerFaster\UILibrary\Contracts\Notifications\Notifiable;
use QuickerFaster\UILibrary\Models\Notification;
use QuickerFaster\UILibrary\Models\UserFavoriteAction;
use QuickerFaster\UILibrary\Services\Notifications\NotificationService;
use QuickerFaster\UILibrary\Events\NavigationBuilding;
use QuickerFaster\UILibrary\Services\QuickActions\ActionRegistry;
use QuickerFaster\UILibrary\Services\QuickActions\ActionTracker;
use QuickerFaster\UILibrary\Services\QuickActions\RankingEngine;
use QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService;

class TopNav extends Component
{
    public array $items = [];
    public ?string $activeContext = null;
    public string $moduleName;
    public int $maxDesktop;
    public int $maxMobile;

    public array $leftShared = [];
    public array $rightShared = [];

    /** @var \Illuminate\Support\Collection|null */
    public $companies = null;

    public ?int $currentCompanyId = null;
    public ?string $currentCompanyName = null;

    public array $modules = [];
    public string $activeModuleKey = 'admin';

    // ------------------------------------------------------------------
    //  Phase 2: Cross-Context Dropdowns
    // ------------------------------------------------------------------

    /** @var bool When true, hide context group tabs in TopNav (used with show_all_contexts). */
    public bool $hideTopnavContexts = false;

    // ------------------------------------------------------------------
    //  Module Switcher Configuration
    // ------------------------------------------------------------------

    /** @var bool Whether the module switcher dropdown is enabled for the current user. */
    public bool $moduleSwitcherEnabled = true;

    /** @var array Cross-module navigation links (e.g. admin panel, back link). */
    public array $crossModuleLinks = [];

    // ------------------------------------------------------------------
    //  Background Jobs Configuration
    // ------------------------------------------------------------------

    /** @var bool Whether the background jobs launcher button is enabled for the current user. */
    public bool $backgroundJobsEnabled = true;

    /** @var string Icon class for the background jobs button. */
    public string $backgroundJobsIcon = 'fas fa-history';

    /** @var string Title attribute for the background jobs button. */
    public string $backgroundJobsTitle = 'Background Jobs';

    // ------------------------------------------------------------------
    //  Notifications Configuration
    // ------------------------------------------------------------------

    /** @var bool Whether the notifications icon button is enabled for the current user. */
    public bool $notificationsEnabled = true;

    /** @var string Icon class for the notifications button. */
    public string $notificationsIcon = 'fas fa-bell';

    /** @var string Title attribute for the notifications button. */
    public string $notificationsTitle = 'Notifications';

    /** @var bool Whether the notifications drawer/offcanvas is open. */
    public bool $showNotificationsDrawer = false;

    // ------------------------------------------------------------------
    //  Quick Actions Configuration
    // ------------------------------------------------------------------

    /** @var bool Whether the quick actions search button is enabled for the current user. */
    public bool $quickActionsEnabled = true;

    /** @var string Icon class for the quick actions button. */
    public string $quickActionsIcon = 'fas fa-search';

    /** @var string Title attribute for the quick actions button. */
    public string $quickActionsTitle = 'Quick Actions (Cmd+K)';

    // ------------------------------------------------------------------
    //  Quick Actions ⚡ Button (Top Nav Dropdown)
    // ------------------------------------------------------------------

    /** @var bool Whether the quick actions ⚡ dropdown button is enabled for the current user. */
    public bool $quickActionsButtonEnabled = true;

    /** @var string Icon class for the quick actions ⚡ button. */
    public string $quickActionsButtonIcon = 'fas fa-bolt';

    /** @var string Title attribute for the quick actions ⚡ button. */
    public string $quickActionsButtonTitle = 'Quick Actions';

    /** @var int Maximum number of ranked actions shown in the ⚡ dropdown. */
    public int $quickActionsMaxItems = 8;

    /** @var array Top-ranked quick actions for the current user. */
    public array $quickActions = [];

    // ------------------------------------------------------------------
    //  Phase 4: Favorites / Pinning
    // ------------------------------------------------------------------

    /** @var array<string> Set of favorited action IDs for the current user. */
    public array $quickActionFavorites = [];

    // ------------------------------------------------------------------
    //  Phase 4: First-Visit Pulse
    // ------------------------------------------------------------------

    /** @var bool Whether to show the first-visit pulse animation on the ⚡ button. */
    public bool $showQuickActionsPulse = false;

    // ------------------------------------------------------------------

    protected CompanyProvider $companyProvider;

    public function mount(
        array $items,
        ?string $activeContext = null,
        string $moduleName,
        array $leftShared = [],
        array $rightShared = [],
        CompanyProvider $companyProvider = null,
        bool $hideTopnavContexts = false,
    ): void {
        $this->items = $items;
        $this->activeContext = $activeContext;
        $this->moduleName = $moduleName;
        $this->leftShared = $leftShared;
        $this->rightShared = $rightShared;

        // Read overflow thresholds from config with sensible defaults.
        // Consuming apps can override via config('ui-library.navigation.top_bar.max_desktop'/max_mobile).
        $this->maxDesktop = (int) config('ui-library.navigation.top_bar.max_desktop', 5);
        $this->maxMobile  = (int) config('ui-library.navigation.top_bar.max_mobile', 3);

        // Phase 2
        $this->hideTopnavContexts = $hideTopnavContexts;

        $this->companyProvider = $companyProvider ?? app(CompanyProvider::class);

        $this->loadModuleSwitcherConfig();
        $this->loadBackgroundJobsConfig();
        $this->loadNotificationsConfig();
        $this->loadQuickActionsConfig();
        $this->loadQuickActionFavorites();
        $this->loadQuickActions();
        $this->loadFirstVisitPulse();
        $this->loadCompanies();
        $this->loadModules();
    }

    /**
     * Load module switcher configuration and resolve role-based access.
     *
     * Mirrors the company switcher pattern in loadCompanies() for consistency:
     * - Reads module_switcher.enabled / module_switcher.roles from config.
     * - Supports '*' wildcard for all authenticated users.
     * - Falls back to the legacy cross_module_links key for backward compatibility.
     */
    protected function loadModuleSwitcherConfig(): void
    {
        $config = config('ui-library.module_switcher', []);

        // 1. Enabled toggle
        $enabled = $config['enabled'] ?? true;
        if (!$enabled) {
            $this->moduleSwitcherEnabled = false;
            $this->crossModuleLinks = [];
            return;
        }

        // 2. Role check (mirrors company switcher pattern)
        if (auth()->check()) {
            $roles = $config['roles'] ?? '*';
            $isWildcard = ($roles === '*' || $roles === ['*']);

            if (!$isWildcard && !auth()->user()->hasAnyRole((array) $roles)) {
                $this->moduleSwitcherEnabled = false;
                $this->crossModuleLinks = [];
                return;
            }
        }

        $this->moduleSwitcherEnabled = true;

        // 3. Cross-module links — prefer new key, fall back to legacy
        $links = $config['links'] ?? [];

        if (empty($links)) {
            // Backward compatibility: read the old cross_module_links key
            $links = config('ui-library.cross_module_links', []);
        }

        // Filter links by per-link roles if specified
        if (auth()->check() && !empty($links)) {
            $user = auth()->user();
            $links = array_filter($links, function ($link) use ($user) {
                if (empty($link['roles'])) {
                    return true; // No per-link role restriction
                }
                $linkRoles = $link['roles'];
                $isWildcard = ($linkRoles === '*' || $linkRoles === ['*']);
                return $isWildcard || $user->hasAnyRole((array) $linkRoles);
            });
        }

        $this->crossModuleLinks = $links;
    }

    /**
     * Load background jobs launcher configuration and resolve role-based access.
     *
     * Mirrors the module switcher and company switcher patterns for consistency:
     * - Reads background_jobs.enabled / background_jobs.roles from config.
     * - Supports '*' wildcard for all authenticated users.
     * - Reads icon and title from config for flexible customization.
     */
    protected function loadBackgroundJobsConfig(): void
    {
        $config = config('ui-library.background_jobs', []);

        // 1. Enabled toggle
        $enabled = $config['enabled'] ?? true;
        if (!$enabled) {
            $this->backgroundJobsEnabled = false;
            return;
        }

        // 2. Role check (mirrors module switcher pattern)
        if (auth()->check()) {
            $roles = $config['roles'] ?? '*';
            $isWildcard = ($roles === '*' || $roles === ['*']);

            if (!$isWildcard && !auth()->user()->hasAnyRole((array) $roles)) {
                $this->backgroundJobsEnabled = false;
                return;
            }
        }

        $this->backgroundJobsEnabled = true;

        // 3. Icon and title from config
        $this->backgroundJobsIcon = $config['icon'] ?? 'fas fa-history';
        $this->backgroundJobsTitle = $config['title'] ?? 'Background Jobs';
    }

    /**
     * Load notifications configuration and resolve role-based access.
     *
     * Mirrors the background jobs, module switcher, and company switcher
     * patterns for consistency:
     * - Reads notifications.enabled / notifications.roles from config.
     * - Supports '*' wildcard for all authenticated users.
     * - Reads icon and title from config for flexible customization.
     */
    protected function loadNotificationsConfig(): void
    {
        $config = config('ui-library.notifications', []);

        // 1. Enabled toggle
        $enabled = $config['enabled'] ?? true;
        if (!$enabled) {
            $this->notificationsEnabled = false;
            return;
        }

        // 2. Role check (mirrors background jobs pattern)
        if (auth()->check()) {
            $roles = $config['roles'] ?? '*';
            $isWildcard = ($roles === '*' || $roles === ['*']);

            if (!$isWildcard && !auth()->user()->hasAnyRole((array) $roles)) {
                $this->notificationsEnabled = false;
                return;
            }
        }

        $this->notificationsEnabled = true;

        // 3. Icon and title from config
        $this->notificationsIcon = $config['icon'] ?? 'fas fa-bell';
        $this->notificationsTitle = $config['title'] ?? 'Notifications';
    }

    /**
     * Load quick actions configuration and resolve role-based access.
     *
     * Mirrors the background jobs, notifications, module switcher, and company
     * switcher patterns for consistency:
     * - Reads quick_actions.enabled / quick_actions.roles from config.
     * - Supports '*' wildcard for all authenticated users.
     * - Reads icon and title from config for flexible customization.
     */
    protected function loadQuickActionsConfig(): void
    {
        $config = config('ui-library.quick_actions', []);

        // 1. Enabled toggle
        $enabled = $config['enabled'] ?? true;
        if (!$enabled) {
            $this->quickActionsEnabled = false;
            return;
        }

        // 2. Role check (mirrors background jobs pattern)
        if (auth()->check()) {
            $roles = $config['roles'] ?? '*';
            $isWildcard = ($roles === '*' || $roles === ['*']);

            if (!$isWildcard && !auth()->user()->hasAnyRole((array) $roles)) {
                $this->quickActionsEnabled = false;
                return;
            }
        }

        $this->quickActionsEnabled = true;

        // 3. Search button icon and title (command_palette sub-key)
        $paletteConfig = $config['command_palette'] ?? [];
        $this->quickActionsIcon = $paletteConfig['button_icon'] ?? 'fas fa-search';
        $this->quickActionsTitle = $paletteConfig['button_title'] ?? 'Search Actions (Cmd+K)';

        // 4. ⚡ dropdown button icon, title, and item limit (top_nav_button sub-key)
        $buttonConfig = $config['top_nav_button'] ?? [];
        $this->quickActionsButtonEnabled = $buttonConfig['enabled'] ?? true;
        $this->quickActionsButtonIcon = $buttonConfig['icon'] ?? 'fas fa-bolt';
        $this->quickActionsButtonTitle = $buttonConfig['title'] ?? 'Quick Actions';
        $this->quickActionsMaxItems = (int) ($buttonConfig['max_items'] ?? 8);
    }

    /**
     * Load the top-ranked quick actions for the ⚡ dropdown.
     *
     * Consumes the Phase 2 ranking signal (RankingEngine::score) and slices
     * to the configured maximum item count. An empty list is returned when
     * the button is disabled or the user is unauthenticated.
     *
     * Phase 4: Favorited actions are interleaved at the top.
     */
    protected function loadQuickActions(): void
    {
        if (!$this->quickActionsButtonEnabled || !auth()->check()) {
            $this->quickActions = [];
            return;
        }

        /** @var ActionRegistry $registry */
        $registry = app(ActionRegistry::class);
        $actions = $registry->authorizedFor();

        /** @var RankingEngine $ranking */
        $ranking = app(RankingEngine::class);
        $actions = $ranking->score($actions, auth()->id());

        // Phase 4: Interleave favorites at the top.
        $actions = $this->interleaveQuickActionFavorites($actions);

        $this->quickActions = array_slice($actions, 0, $this->quickActionsMaxItems);
    }

    /**
     * Load the current user's favorited action IDs.
     *
     * @return void
     */
    protected function loadQuickActionFavorites(): void
    {
        $this->quickActionFavorites = [];

        if (!auth()->check()) {
            return;
        }

        $this->quickActionFavorites = UserFavoriteAction::query()
            ->where('user_id', auth()->id())
            ->pluck('action_id')
            ->toArray();
    }

    /**
     * Interleave favorited actions at the top of the list.
     *
     * @param  array<int, array> $actions
     * @return array<int, array>
     */
    protected function interleaveQuickActionFavorites(array $actions): array
    {
        if (empty($this->quickActionFavorites)) {
            return $actions;
        }

        $favoriteSet = array_flip($this->quickActionFavorites);

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

        usort($pinned, function (array $a, array $b) {
            $posA = array_search($a['id'] ?? $a['key'] ?? '', $this->quickActionFavorites);
            $posB = array_search($b['id'] ?? $b['key'] ?? '', $this->quickActionFavorites);
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
    public function toggleQuickActionFavorite(string $actionId): void
    {
        if (!auth()->check()) {
            return;
        }

        $existing = UserFavoriteAction::query()
            ->where('user_id', auth()->id())
            ->where('action_id', $actionId)
            ->first();

        if ($existing) {
            $existing->delete();
        } else {
            UserFavoriteAction::create([
                'user_id'   => auth()->id(),
                'action_id' => $actionId,
            ]);
        }

        $this->loadQuickActionFavorites();
        $this->loadQuickActions();
    }

    /**
     * Check whether the given action ID is favorited by the current user.
     *
     * @param  string $actionId
     * @return bool
     */
    public function isQuickActionFavorited(string $actionId): bool
    {
        return in_array($actionId, $this->quickActionFavorites, true);
    }

    // ------------------------------------------------------------------
    //  Phase 4: First-Visit Pulse
    // ------------------------------------------------------------------

    /**
     * Determine whether to show the first-visit pulse animation on the ⚡ button.
     *
     * Uses a session flag so the pulse only shows once per session.
     *
     * @return void
     */
    protected function loadFirstVisitPulse(): void
    {
        $config = config('ui-library.quick_actions.top_nav_button', []);
        $showBadge = $config['show_badge_on_first_visit'] ?? true;

        if (!$showBadge) {
            $this->showQuickActionsPulse = false;
            return;
        }

        $sessionKey = 'quick_actions_first_visit_seen';

        if (Session::has($sessionKey)) {
            $this->showQuickActionsPulse = false;
            return;
        }

        // Show pulse on first visit, then mark as seen.
        $this->showQuickActionsPulse = true;
        Session::put($sessionKey, true);
    }

    /**
     * Open the quick actions command palette.
     */
    public function openQuickActions(): void
    {
        $this->dispatch('openQuickActions');
    }

    /**
     * Execute a quick action from the ⚡ dropdown by its ID.
     *
     * Mirrors QuickActionsPanel::executeAction(): records the execution for
     * personalized ranking via ActionTracker, then navigates or dispatches a
     * Livewire event based on the action's `action` type.
     */
    public function executeQuickAction(string $id): void
    {
        /** @var ActionRegistry $registry */
        $registry = app(ActionRegistry::class);
        $action = $registry->findByKey($id);

        if (!$action) {
            return;
        }

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
                $url = $this->resolveQuickActionUrl($action);
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
                $url = $this->resolveQuickActionUrl($action);
                if ($url) {
                    $this->dispatch('navigate', url: $url);
                }
                break;
        }
    }

    /**
     * Resolve the URL for a navigation quick action.
     */
    protected function resolveQuickActionUrl(array $action): ?string
    {
        // Prefer named route
        if (!empty($action['route'])) {
            try {
                return route($action['route']);
            } catch (\Exception $e) {
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
     * Open the notifications drawer.
     */
    public function openNotificationsDrawer(): void
    {
        $this->showNotificationsDrawer = true;
    }

    /**
     * Close the notifications drawer.
     */
    public function closeNotificationsDrawer(): void
    {
        $this->showNotificationsDrawer = false;
    }

    /**
     * Return unread notifications for the currently authenticated user.
     */
    public function getUnreadNotificationsProperty(): Collection
    {
        $user = auth()->user();

        if (!$user instanceof Notifiable) {
            return collect();
        }

        return app(NotificationService::class)->getUnread($user);
    }

    /**
     * Return the unread notification count for the currently authenticated user.
     */
    public function getUnreadCountProperty(): int
    {
        return $this->getUnreadNotificationsProperty()->count();
    }

    /**
     * Mark a notification as read for the currently authenticated user.
     */
    public function markAsRead(int $notificationId): void
    {
        $user = auth()->user();

        if (!$user instanceof Notifiable) {
            return;
        }

        $notification = \QuickerFaster\UILibrary\Models\Notification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId())
            ->first();

        $notification?->markAsRead();
    }

    /**
     * Handle an inline action button click for a notification.
     *
     * Loads the notification, resolves the handler from the
     * NotificationActionRegistry, calls handle(), and optionally
     * marks the notification as read.
     */
    public function handleAction(int $notificationId, string $handler, array $data = []): void
    {
        $user = auth()->user();

        if (! $user instanceof Notifiable) {
            return;
        }

        $notification = \QuickerFaster\UILibrary\Models\Notification::query()
            ->where('id', $notificationId)
            ->where('notifiable_type', $user->getNotifiableType())
            ->where('notifiable_id', $user->getNotifiableId())
            ->first();

        if (! $notification) {
            return;
        }

        $registry = app(\QuickerFaster\UILibrary\Services\Notifications\NotificationActionRegistry::class);
        $registry->handle($handler, $notification, $data);

        // Optionally mark as read after handling the action.
        $notification->markAsRead();
    }

    /**
     * Navigate to the URL associated with a notification.
     *
     * Marks the notification as read (if not already) and dispatches
     * a Livewire navigate event to the URL stored in the notification's
     * data payload.
     */
    public function navigateToNotification(int $notificationId): void
    {
        $notification = Notification::find($notificationId);

        if (! $notification) {
            return;
        }

        // Mark as read
        if (! $notification->read_at) {
            $notification->update(['read_at' => now()]);
        }

        // Navigate to the URL stored in data, if present
        $url = $notification->data['url'] ?? null;

        if ($url) {
            $this->redirect($url);
        }
    }

    /**
     * Livewire event listeners for real-time notification broadcasts.
     */
    public function getListeners(): array
    {
        $user = auth()->user();
        $id = $user instanceof Notifiable ? $user->getNotifiableId() : '0';

        return [
            "echo-private:notifiable.{$id},notification.dispatched" => 'refreshUnreadCount',
            'execute-quick-action' => 'executeQuickAction',
        ];
    }

    /**
     * Re-render the component when a new notification is broadcast.
     */
    public function refreshUnreadCount(): void
    {
        $this->dispatch('$refresh');
    }

    /**
     * Load all companies and set the current selection from session.
     */
    protected function loadCompanies(): void
    {
        if (!auth()->check()) {
            $this->companies = collect();
            return;
        }

        $user = auth()->user();

        // Check if company switcher is enabled in config
        if (!config('ui-library.navigation.show_company_switcher', true)) {
            $this->companies = collect();
            return;
        }

        $config = config('ui-library.multitenancy', []);
        $switcherRoles = $config['switcher_roles'] ?? ['super_admin'];
        $isWildcard = ($switcherRoles === '*' || $switcherRoles === ['*']);

        if (!$isWildcard && !$user->hasAnyRole((array) $switcherRoles)) {
            $this->companies = collect();
            return;
        }

        $this->companies = $this->companyProvider->getCompanies($user);

        // Determine current company from session
        $sessionCompanyId = Session::get('current_company_id');
        $providerCompanyId = $this->companyProvider->getCurrentCompanyId($user);

        if ($sessionCompanyId === 0) {
            $this->currentCompanyId = 0;
        } elseif ($sessionCompanyId && $this->companies->pluck('id')->contains($sessionCompanyId)) {
            $this->currentCompanyId = $sessionCompanyId;
        } elseif ($providerCompanyId) {
            $this->currentCompanyId = $providerCompanyId;
            Session::put('current_company_id', $providerCompanyId);
        } elseif ($this->companies->isNotEmpty()) {
            $this->currentCompanyId = $this->companies->first()->id;
            Session::put('current_company_id', $this->currentCompanyId);
        }

        $this->updateCurrentCompanyName();
    }

    /**
     * Switch the active company and persist to session.
     */
    public function switchCompany(int $companyId): void
    {
        // Allow 0 for "All Companies"; otherwise validate company exists
        if ($companyId !== 0 && (!$this->companies || !$this->companies->pluck('id')->contains($companyId))) {
            return;
        }

        $this->currentCompanyId = $companyId;
        Session::put('current_company_id', $companyId);
        $this->updateCurrentCompanyName();

        // Dispatch event so other components can react to company change
        $this->dispatch('companySwitched', companyId: $companyId);

        // Redirect to dashboard to refresh the page context
        $this->redirect(url('/' . strtolower($this->moduleName) . '/dashboard'));
    }

    /**
     * Update the display name for the currently selected company.
     */
    protected function updateCurrentCompanyName(): void
    {
        if ($this->currentCompanyId === 0) {
            $this->currentCompanyName = 'All Companies';
        } elseif ($this->currentCompanyId && $this->companies) {
            $company = $this->companies->firstWhere('id', $this->currentCompanyId);
            $this->currentCompanyName = $company ? $company->name : 'Select Company';
        } else {
            $this->currentCompanyName = 'Select Company';
        }
    }

    // ------------------------------------------------------------------
    //  Desktop visible / overflow  (with active-item promotion)
    // ------------------------------------------------------------------

    /**
     * Visible desktop items.
     *
     * Normally the first $maxDesktop items, BUT if the active context
     * falls into the overflow we "promote" it — keeping the first
     * maxDesktop-1 items and appending the active item.
     */
    public function getVisibleDesktopProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxDesktop) {
            return $items;
        }

        if ($this->activeContext !== null && $this->activeContext !== '') {
            $keys = $items->keys()->values();
            $activeIndex = $keys->search($this->activeContext);

            if ($activeIndex !== false && $activeIndex >= $this->maxDesktop) {
                // The active context is in the overflow — promote it.
                // Keep the first (maxDesktop - 1) items plus the active item.
                $visible = $items->take($this->maxDesktop - 1);
                $activeItem = $items->only([$this->activeContext]);

                return $visible->merge($activeItem);
            }
        }

        return $items->take($this->maxDesktop);
    }

    /**
     * Desktop overflow items — everything NOT in getVisibleDesktopProperty().
     */
    public function getOverflowDesktopProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxDesktop) {
            return collect();
        }

        $visibleKeys = $this->getVisibleDesktopProperty()->keys()->toArray();

        return $items->reject(fn($item, $key) => in_array($key, $visibleKeys, true));
    }

    // ------------------------------------------------------------------
    //  Mobile visible / overflow  (with active-item promotion)
    // ------------------------------------------------------------------

    /**
     * Visible mobile items.
     *
     * Same promotion logic as desktop but uses $maxMobile.
     */
    public function getVisibleMobileProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxMobile) {
            return $items;
        }

        if ($this->activeContext !== null && $this->activeContext !== '') {
            $keys = $items->keys()->values();
            $activeIndex = $keys->search($this->activeContext);

            if ($activeIndex !== false && $activeIndex >= $this->maxMobile) {
                $visible = $items->take($this->maxMobile - 1);
                $activeItem = $items->only([$this->activeContext]);

                return $visible->merge($activeItem);
            }
        }

        return $items->take($this->maxMobile);
    }

    /**
     * Mobile overflow items — everything NOT in getVisibleMobileProperty().
     */
    public function getOverflowMobileProperty(): Collection
    {
        $items = collect($this->items);

        if ($items->count() <= $this->maxMobile) {
            return collect();
        }

        $visibleKeys = $this->getVisibleMobileProperty()->keys()->toArray();

        return $items->reject(fn($item, $key) => in_array($key, $visibleKeys, true));
    }

    public function handleOverflowSelect($value)
    {
        // $value is the selected item's key (the array key from $items)
        $this->dispatch('contextSelected', $value);
        // Optionally navigate to the item's default route
        $item = $this->items[$value] ?? null;
        if ($item && isset($item['route'])) {
            $isNamedRoute = !str_contains($item['route'], '/');
            $url = $isNamedRoute ? route($item['route']) : url($item['route']);
            $this->redirect($url);
        } else {
            // Fallback to a constructed URL
            $this->redirect(url("/{$this->moduleName}/" . Str::kebab($value)));
        }
    }

    public function selectContext(string $context): void
    {
        $this->dispatch('contextSelected', $context);
    }

    /**
     * Open the background jobs drawer.
     */

    public function openBackgroundJobsDrawer(): void
    {
        $this->dispatch('openDrawer', 'qf.background-jobs-panel', [], 'Background Jobs');
    }

    public function logout()
    {
        // 1. Log the user out using the Auth facade
        auth()->logout();

        // 2. Invalidate the user's session
        session()->invalidate();

        // 3. Regenerate the CSRF token for security
        session()->regenerateToken();

        // 4. Redirect to the login page or homepage (this is a GET)
        return redirect('/login');
    }


    public function switchModule(string $moduleKey): void
    {
        session(['active_module' => $moduleKey]);
        $module = collect($this->modules)->firstWhere('key', $moduleKey);
        if ($module && isset($module['route'])) {
            $this->redirect(route($module['route']));
        }
    }

    public function getCurrentModuleLabelProperty(): string
    {
        $current = collect($this->modules)->firstWhere('key', $this->activeModuleKey);
        return $current['label'] ?? ucfirst($this->activeModuleKey);
    }

    protected function loadModules(): void
    {
        $allModules = config('ui-library.modules', []);

        $modules = [];
        foreach ($allModules as $key => $config) {
            if (!($config['enabled'] ?? true)) {
                continue;
            }

            if (!($config['user_facing'] ?? true)) {
                continue;
            }

            $roles = $config['roles'] ?? ['*'];
            if ($roles !== ['*']) {
                $user = auth()->user();

                if (! $user) {
                    continue;
                }

                // Mirrors AuthorizationService::isBypassAllowed() — the
                // configured super admin email always bypasses the module
                // role filter. This prevents a failed role assignment from
                // silently hiding admin/system modules from the super admin.
                $superAdminEmail = (string) env('SUPER_ADMIN_EMAIL', 'admin@example.com');
                $isSuperAdmin = $user->getAttribute('email') === $superAdminEmail;

                if (! $isSuperAdmin && ! $user->hasAnyRole(...$roles)) {
                    continue;
                }
            }
    
            // ------------------------------------------------------------------
            //  module_access filtering
            // ------------------------------------------------------------------
            // If the module key is listed in config('ui-library.module_access'),
            // the current user must have one of the allowed roles (or be an
            // admin bypass). Modules NOT listed in module_access are shown to
            // everyone (backward compatibility).
            $moduleAccess = config('ui-library.module_access', []);
            if (isset($moduleAccess[$key])) {
                $user = auth()->user();
    
                if (! $user) {
                    continue;
                }
    
                // Admin bypass: super_admin, admin, company_admin see all modules.
                if (! AuthorizationService::isBypassAllowed($user)) {
                    $allowedRoles = (array) $moduleAccess[$key];
    
                    if (! $user->hasAnyRole(...$allowedRoles)) {
                        continue;
                    }
                }
            }
    
            $modules[$key] = array_merge($config, ['key' => $key]);
        }

        uasort($modules, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        event(new NavigationBuilding($modules));

        $this->modules = $modules;
        $this->activeModuleKey = session('active_module', array_key_first($modules) ?? 'admin');
    }

    public function render()
    {
        return view('qf::livewire.navs.top-nav');
    }
}