<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Session;
use QuickerFaster\UILibrary\Contracts\Navigation\CompanyProvider;
use QuickerFaster\UILibrary\Events\NavigationBuilding;

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