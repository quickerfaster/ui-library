<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Services\Navigation\NavigationManager;

class Sidebar extends Component
{
    public ?array $items = null;
    public string $state = 'full';
    public array $headerItems = [];
    public array $footerItems = [];
    public ?string $currentModelName = null;
    public ?string $settingsContext = null;
    public ?string $moduleName = null;

    /**
     * The currently active context group key (e.g., "People", "Organization").
     *
     * Set by NavigationLayout to tell the sidebar which context group's items
     * should be rendered, enabling the context_groups → sidebar linkage.
     *
     * @var string|null
     */
    public ?string $activeContext = null;

    /**
     * The human-readable label for the active context group (e.g., "Companies").
     *
     * Passed from NavigationLayout to display as the expandable section header
     * in Priority 1 sidebar rendering.
     *
     * @var string|null
     */
    public ?string $contextGroupLabel = null;

    /**
     * The Font Awesome icon class for the active context group (e.g., "fa-building").
     *
     * Passed from NavigationLayout; falls back to "fa-folder" if not provided.
     *
     * @var string|null
     */
    public ?string $contextGroupIcon = null;

    /**
     * Full sidebar configuration from the active context group in navigation.php.
     *
     * Contains the optional 'sidebar' key with rendering options:
     *   - section_label: string|null|false  Custom label (null=use group label, false=no header)
     *   - collapsible: bool                 Expandable/collapsible toggle
     *   - expanded_default: bool            Start expanded (only when collapsible)
     *
     * @var array|null
     */
    public ?array $contextGroupConfig = null;

    public bool $allowTypeSwitch = false;

    /**
     * Phase 4.3/4.5: Module sections for collapsible sidebar rendering.
     *
     * Populated by NavigationManager (Phase 4.5) or buildModuleSections()
     * fallback (Phase 4.3).
     *
     * @var array<string, array{key: string, label: string, icon: string, items: array, has_active: bool}>
     */
    public array $moduleSections = [];

    /**
     * Phase 4.3: Keys of currently expanded sections.
     *
     * @var array<string, bool>
     */
    public array $expandedSections = [];

    /**
     * Phase 4.4: Current organization for the application switcher.
     *
     * @var array{id: int|string, name: string, logo: string|null}|null
     */
    public ?array $currentOrganization = null;

    /**
     * Phase 4.4: All organizations the authenticated user belongs to.
     *
     * @var \Illuminate\Support\Collection
     */
    public $userOrganizations;

    public function mount(?array $items = null, string $state = 'full', array $headerItems = [], array $footerItems = [],
        bool $allowTypeSwitch = false,
        ?string $currentModelName = null,
        ?string $settingsContext = null,
        ?string $moduleName = null,
        ?array $currentOrganization = null,
        $userOrganizations = null,
        ?string $activeContext = null,
        ?string $contextGroupLabel = null,
        ?string $contextGroupIcon = null,
        ?array $contextGroupConfig = null
        )
    {
        $this->items = is_array($items) ? $items : [];
        $this->state = $state;
        $this->headerItems = $headerItems;
        $this->footerItems = $footerItems;
        $this->allowTypeSwitch = $allowTypeSwitch;
        $this->currentModelName = $currentModelName;
        $this->settingsContext = $settingsContext;
        $this->moduleName = $moduleName;
        $this->activeContext = $activeContext;
        $this->contextGroupLabel = $contextGroupLabel;
        $this->contextGroupIcon = $contextGroupIcon;
        $this->contextGroupConfig = $contextGroupConfig ?? [];

        // Apply expanded_default from sidebar config if available
        $sidebarConfig = $this->contextGroupConfig['sidebar'] ?? [];
        if (!empty($this->activeContext) && ($sidebarConfig['collapsible'] ?? true)) {
            $expandedDefault = $sidebarConfig['expanded_default'] ?? true;
            if ($expandedDefault) {
                $this->expandedSections['context-' . $this->activeContext] = true;
            }
        }

        // Phase 4.4: Organization data for the application switcher
        $this->currentOrganization = $currentOrganization;
        $this->userOrganizations = $userOrganizations instanceof \Illuminate\Support\Collection
            ? $userOrganizations
            : collect($userOrganizations ?? []);

        // Phase 4.5: Use NavigationManager if available, fall back to Phase 4.3 behaviour
        $this->buildModuleSections();
    }

    /**
     * Phase 4.3: Toggle a module section's expanded/collapsed state.
     */
    public function toggleSection(string $moduleKey): void
    {
        if (isset($this->expandedSections[$moduleKey])) {
            unset($this->expandedSections[$moduleKey]);
        } else {
            $this->expandedSections[$moduleKey] = true;
        }
    }

    /**
     * Phase 4.5: Build module-grouped sections using NavigationManager.
     *
     * Falls back to the Phase 4.3 direct module-registry approach if
     * NavigationManager is not bound in the container.
     */
    protected function buildModuleSections(): void
    {
        // P0 Fix: When context-specific items are available from NavigationLayout,
        // skip NavigationManager entirely and use the items directly.
        // This enables the context_groups → sidebar linkage: selecting a top nav
        // tab shows only that context group's items in the sidebar.
        if (!empty($this->items) && $this->activeContext !== null && $this->activeContext !== '') {
            $this->moduleSections = [];
            return;
        }

        // Phase 4.5: Try NavigationManager first
        try {
            $navigationManager = app(NavigationManager::class);
            $this->moduleSections = $navigationManager->getSections($this->moduleName);

            // Auto-expand sections containing the active route
            foreach ($this->moduleSections as $sectionKey => $section) {
                if (!empty($section['has_active'])) {
                    $this->expandedSections[$sectionKey] = true;
                }
            }

            return;
        } catch (\Exception $e) {
            // NavigationManager not bound — fall through to Phase 4.3 fallback
        }

        // Phase 4.3 fallback: Build from module registry directly
        $this->buildModuleSectionsLegacy();
    }

    /**
     * Phase 4.3 (legacy): Build module-grouped sections from all user-facing modules.
     *
     * Used as fallback when NavigationManager is not available.
     */
    protected function buildModuleSectionsLegacy(): void
    {
        $allModules = config('ui-library.modules', []);
        $userFacingModules = array_filter($allModules, function ($config) {
            return ($config['enabled'] ?? false) && ($config['user_facing'] ?? false);
        });

        if (empty($userFacingModules)) {
            return;
        }

        uasort($userFacingModules, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        $currentRouteName = Route::currentRouteName();
        $currentPath = request()->path();

        foreach ($userFacingModules as $moduleKey => $moduleConfig) {
            // When moduleName is set, skip modules that don't match
            if ($this->moduleName !== null && $moduleKey !== $this->moduleName) {
                continue;
            }
            $navItems = $this->loadModuleNavItems($moduleKey);

            if (empty($navItems)) {
                continue;
            }

            $hasActive = $this->sectionHasActiveItem($navItems, $currentRouteName, $currentPath);

            $this->moduleSections[$moduleKey] = [
                'key' => $moduleKey,
                'label' => $moduleConfig['label'],
                'icon' => $moduleConfig['icon'] ?? 'fa-cube',
                'items' => $navItems,
                'has_active' => $hasActive,
            ];

            if ($hasActive) {
                $this->expandedSections[$moduleKey] = true;
            }
        }
    }

    /**
     * Phase 4.3: Load all navigation items for a given module by reading its
     * navigation config and flattening all context items into a single array.
     *
     * @param  string $moduleKey
     * @return array
     */
    protected function loadModuleNavItems(string $moduleKey): array
    {
        $configPath = $this->resolveNavigationConfigPath($moduleKey);

        if (!$configPath || !file_exists($configPath)) {
            return [];
        }

        $config = require $configPath;
        $contextItems = $config['contexts'] ?? [];

        $allItems = [];
        foreach ($contextItems as $contextKey => $items) {
            foreach ($items as $item) {
                $item['_context'] = $contextKey;
                $allItems[] = $item;
            }
        }

        usort($allItems, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        return $allItems;
    }

    /**
     * Phase 4.3: Determine whether any item in a section matches the current route.
     *
     * @param  array       $items
     * @param  string|null $currentRouteName
     * @param  string      $currentPath
     * @return bool
     */
    protected function sectionHasActiveItem(array $items, ?string $currentRouteName, string $currentPath): bool
    {
        foreach ($items as $item) {
            $route = $item['route'] ?? null;
            if (!$route) {
                continue;
            }

            if (!str_contains($route, '/') && $route === $currentRouteName) {
                return true;
            }

            $pathToMatch = ltrim($route, '/');
            if ($pathToMatch === $currentPath || str_starts_with($currentPath, $pathToMatch . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Phase 4.3: Resolve the navigation config file path for a module.
     *
     * @param  string      $moduleName
     * @return string|null
     */
    protected function resolveNavigationConfigPath(string $moduleName): ?string
    {
        // Priority order (descending): Published override → Business module → Core module → Vendor fallback
        // This ensures consuming apps can override library defaults.

        // 1. Published override in the consuming app (highest priority — explicit customization)
        $publishedPath = resource_path(
            'views/vendor/ui-library/core/' . strtolower($moduleName) . '/Config/navigation.php'
        );
        if (file_exists($publishedPath)) {
            return $publishedPath;
        }

        // 2. Business module in the consuming app (consuming app's own module overrides library core)
        $businessPath = app_path('Modules/' . ucfirst($moduleName) . '/Config/navigation.php');
        if (file_exists($businessPath)) {
            return $businessPath;
        }

        // 3. Core module path from config (library defaults — lowest library priority)
        $coreBasePath = config('ui-library.module_paths.core');
        if ($coreBasePath) {
            $corePath = $coreBasePath . '/' . ucfirst($moduleName) . '/Config/navigation.php';
            if (file_exists($corePath)) {
                return $corePath;
            }
        }

        // 4. Core module shipped with the package (vendor fallback)
        $vendorCorePath = base_path(
            'vendor/quicker-faster/ui-library/src/Core/' . ucfirst($moduleName) . '/Config/navigation.php'
        );
        if (file_exists($vendorCorePath)) {
            return $vendorCorePath;
        }

        return null;
    }

public function toggleState(): void
{
    $states = ['full', 'icon'];
    $currentIndex = array_search($this->state, $states);
    $nextIndex = ($currentIndex + 1) % count($states);
    $this->state = $states[$nextIndex];
    
    // Persist to session and localStorage
    session(['sidebar_state' => $this->state]);
    $this->dispatch('saveSidebarState', $this->state);
    // $this->dispatch('sidebarStateChanged', $this->state);
}

public function switchToHorizontal(): void
{
    session(['context_menu_type' => 'horizontal']);
    $this->dispatch('doReload');
}

public function openSettings()
{
    $contextKey = strtolower($this->settingsContext);
    $title = ($this->settingsContext ? $this->settingsContext . ' ' : '') . 'Settings';
    $this->dispatch('openDrawer',
        component: 'qf.settings-panel',
        params: [
            'mode' => 'company',
            'context' => $contextKey,
            'moduleName' => $this->moduleName ?? null,
            'initialGroup' => 'auto_generation',
        ],
        title: $title
    );
}

    public function render()
    {
        return view('qf::livewire.navs.sidebar');
    }
}