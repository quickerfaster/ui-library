<?php

namespace QuickerFaster\UILibrary\Services\Navigation;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Traits\NavigationFilter;
use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;

/**
 * Phase 4.5: Configuration-driven Navigation Manager
 *
 * Reads config('ui-library.navigation.sidebar') for section/item definitions,
 * merges with module registry data, resolves permissions, dependencies,
 * and returns a structured collection for the sidebar view to render.
 *
 * Falls back to smart defaults (module registry + per-module navigation configs)
 * if no published sidebar config exists.
 */
class NavigationManager
{
    use NavigationFilter;

    /**
     * Cached sections result.
     *
     * @var array|null
     */
    protected ?array $cachedSections = null;

    /**
     * Get all sidebar sections with their items, sorted and filtered.
     *
     * Returns an array of sections, each containing:
     * - key: string (section slug)
     * - label: string
     * - icon: string (Font Awesome class)
     * - items: array of navigation items
     * - has_active: bool
     *
     * @return array
     */
    public function getSections(?string $activeModule = null): array
    {
        if ($this->cachedSections !== null) {
            return $this->cachedSections;
        }

        $sidebarConfig = config('ui-library.navigation.sidebar', []);

        // If the consuming app has published section definitions, use them
        if (!empty($sidebarConfig['sections'])) {
            $this->cachedSections = $this->buildFromConfig($sidebarConfig);
        } else {
            // Fall back to smart defaults: module registry + per-module navigation configs
            $this->cachedSections = $this->buildFromModuleRegistry($activeModule);
        }

        return $this->cachedSections;
    }

    /**
     * Build sections from the published sidebar config.
     *
     * @param  array $sidebarConfig
     * @return array
     */
    protected function buildFromConfig(array $sidebarConfig): array
    {
        $sections = [];
        $moduleRegistry = config('ui-library.modules', []);
        $currentRouteName = Route::currentRouteName();
        $currentPath = request()->path();

        foreach ($sidebarConfig['sections'] as $sectionDef) {
            // Skip disabled sections
            if (isset($sectionDef['enabled']) && !$sectionDef['enabled']) {
                continue;
            }

            // Check gate/permission for the section itself
            if (!$this->checkSectionGate($sectionDef)) {
                continue;
            }

            $sectionSlug = $sectionDef['slug'];
            $items = [];

            // Load items for this section
            if (!empty($sectionDef['items'])) {
                // Explicitly defined items in config
                $items = $this->resolveConfigItems($sectionDef['items'], $moduleRegistry);
            } elseif (!empty($sectionDef['module'])) {
                // Single module reference — load all its nav items
                $items = $this->loadModuleNavItems($sectionDef['module']);
            } else {
                // No items defined — skip section
                continue;
            }

            // Filter items by permission and dependency satisfaction
            $items = $this->filterSectionItems($items, $moduleRegistry);

            // Sort items by order
            usort($items, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

            // Skip sections with no visible items
            if (empty($items)) {
                continue;
            }

            $hasActive = $this->sectionHasActiveItem($items, $currentRouteName, $currentPath);

            $sections[$sectionSlug] = [
                'key' => $sectionSlug,
                'label' => $sectionDef['label'],
                'icon' => $sectionDef['icon'] ?? 'fa-cube',
                'items' => $items,
                'has_active' => $hasActive,
            ];
        }

        // Sort sections by order
        uasort($sections, fn($a, $b) => ($sidebarConfig['sections'][$a['key']]['order'] ?? 999)
            <=> ($sidebarConfig['sections'][$b['key']]['order'] ?? 999));

        return $sections;
    }

    /**
     * Build sections from the module registry (fallback when no config published).
     *
     * Replicates the current Sidebar::buildModuleSections() behavior.
     *
     * @return array
     */
    protected function buildFromModuleRegistry(?string $activeModule = null): array
    {
        $sections = [];

        $allModules = config('ui-library.modules', []);
        $userFacingModules = array_filter($allModules, function ($config) {
            return ($config['enabled'] ?? false) && ($config['user_facing'] ?? false);
        });

        if (empty($userFacingModules)) {
            return [];
        }

        // Sort modules by their configured order
        uasort($userFacingModules, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        $currentRouteName = Route::currentRouteName();
        $currentPath = request()->path();

        foreach ($userFacingModules as $moduleKey => $moduleConfig) {
            // When activeModule is provided, skip modules that don't match
            if ($activeModule !== null && $moduleKey !== $activeModule) {
                continue;
            }

            // Check dependency satisfaction
            if (!$this->areDependenciesSatisfied($moduleConfig)) {
                continue;
            }

            // Check module-level gate
            if (!$this->checkModuleGate($moduleConfig)) {
                continue;
            }

            $navItems = $this->loadModuleNavItems($moduleKey);

            // Skip modules with no navigation items
            if (empty($navItems)) {
                continue;
            }

            // Filter items by permission
            $navItems = $this->filterVisibleItems($navItems);

            // Sort items by order
            usort($navItems, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

            if (empty($navItems)) {
                continue;
            }

            $hasActive = $this->sectionHasActiveItem($navItems, $currentRouteName, $currentPath);

            $sections[$moduleKey] = [
                'key' => $moduleKey,
                'label' => $moduleConfig['label'],
                'icon' => $moduleConfig['icon'] ?? 'fa-cube',
                'items' => $navItems,
                'has_active' => $hasActive,
            ];
        }

        return $sections;
    }

    /**
     * Resolve items defined in the sidebar config.
     *
     * Each item can be:
     * - A module reference: ['module' => 'organization'] — loads all nav items from that module
     * - A custom route: ['label' => '...', 'route' => '...', 'icon' => '...']
     *
     * @param  array $itemDefs
     * @param  array $moduleRegistry
     * @return array
     */
    protected function resolveConfigItems(array $itemDefs, array $moduleRegistry): array
    {
        $items = [];

        foreach ($itemDefs as $itemDef) {
            // Module reference — expand to all its nav items
            if (!empty($itemDef['module'])) {
                $moduleKey = $itemDef['module'];
                $moduleConfig = $moduleRegistry[$moduleKey] ?? null;

                // Skip if module not found or not enabled
                if (!$moduleConfig || empty($moduleConfig['enabled'])) {
                    continue;
                }

                // Check dependency satisfaction
                if (!$this->areDependenciesSatisfied($moduleConfig)) {
                    continue;
                }

                $moduleItems = $this->loadModuleNavItems($moduleKey);

                // Apply per-item overrides from config
                foreach ($moduleItems as &$mItem) {
                    if (isset($itemDef['label'])) {
                        $mItem['label'] = $itemDef['label'];
                    }
                    if (isset($itemDef['icon'])) {
                        $mItem['icon'] = $itemDef['icon'];
                    }
                    if (isset($itemDef['order'])) {
                        $mItem['order'] = $itemDef['order'];
                    }
                    if (isset($itemDef['gate'])) {
                        $mItem['gate'] = $itemDef['gate'];
                    }
                    if (isset($itemDef['permission'])) {
                        $mItem['permission'] = $itemDef['permission'];
                    }
                }
                unset($mItem);

                $items = array_merge($items, $moduleItems);
                continue;
            }

            // Custom route item
            if (!empty($itemDef['route']) || !empty($itemDef['url'])) {
                // Skip if disabled
                if (isset($itemDef['enabled']) && !$itemDef['enabled']) {
                    continue;
                }

                // Normalize: ensure route consistency (consuming apps may use url paths, library uses named routes)
                $route = $itemDef['route'] ?? null;
                $url = $itemDef['url'] ?? null;
                if (empty($route) && !empty($url)) {
                    $route = $url;
                }

                $items[] = [
                    'label' => $itemDef['label'] ?? 'Untitled',
                    'route' => $route,
                    'url' => $url,
                    'icon' => $itemDef['icon'] ?? 'fa-circle',
                    'order' => $itemDef['order'] ?? 999,
                    'gate' => $itemDef['gate'] ?? null,
                    'permission' => $itemDef['permission'] ?? null,
                    'visibility' => $itemDef['visibility'] ?? 'any',
                ];
            }
        }

        return $items;
    }

    /**
     * Filter items by permission, gate, and dependency checks.
     *
     * @param  array $items
     * @param  array $moduleRegistry
     * @return array
     */
    protected function filterSectionItems(array $items, array $moduleRegistry): array
    {
        return array_filter($items, function ($item) use ($moduleRegistry) {
            // Check visibility rule
            if (isset($item['visibility']) && !$this->checkVisibility($item['visibility'])) {
                return false;
            }

            // Check gate string (e.g., 'role:super_admin', 'permission:view_dashboard')
            if (!empty($item['gate']) && !$this->checkGate($item['gate'])) {
                return false;
            }

            // Check permission string
            if (!empty($item['permission']) && !$this->checkPermission($item['permission'])) {
                return false;
            }

            return true;
        });
    }

    /**
     * Load all navigation items for a given module by reading its navigation config.
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
                // Tag each item with its source context
                $item['_context'] = $contextKey;

                // Normalize: ensure every item has a 'key' identifier
                if (empty($item['key'])) {
                    $item['key'] = \Illuminate\Support\Str::slug(
                        $item['label'] ?? $contextKey
                    );
                }

                // Normalize: ensure route consistency (consuming apps may use url paths, library uses named routes)
                if (empty($item['route']) && !empty($item['url'])) {
                    $item['route'] = $item['url'];
                }

                // Tag with source module so sidebar renderers know provenance
                $item['module'] = $moduleKey;

                // Ensure permission defaults to null (library configs omit this field)
                if (!array_key_exists('permission', $item)) {
                    $item['permission'] = null;
                }

                // Ensure page_title defaults to label (library configs omit this field)
                if (!array_key_exists('page_title', $item)) {
                    $item['page_title'] = $item['label'] ?? null;
                }

                // Ensure order has a default
                if (!isset($item['order'])) {
                    $item['order'] = 999;
                }

                $allItems[] = $item;
            }
        }

        // Apply workspace filtering to remove items gated by
        // workspace constraints (role, department_type, etc.)
        $workspaceResolver = app(WorkspaceResolver::class);
        $workspaceFilter = new WorkspaceFilter($workspaceResolver->resolve());
        $allItems = array_values($workspaceFilter->filterContextItems($allItems));

        return $allItems;
    }

    /**
     * Check if a section's gate/permission allows access.
     *
     * @param  array $sectionDef
     * @return bool
     */
    protected function checkSectionGate(array $sectionDef): bool
    {
        // Check gate string
        if (!empty($sectionDef['gate']) && !$this->checkGate($sectionDef['gate'])) {
            return false;
        }

        // Check permission string
        if (!empty($sectionDef['permission']) && !$this->checkPermission($sectionDef['permission'])) {
            return false;
        }

        return true;
    }

    /**
     * Check a module's gate/roles configuration.
     *
     * @param  array $moduleConfig
     * @return bool
     */
    protected function checkModuleGate(array $moduleConfig): bool
    {
        $roles = $moduleConfig['roles'] ?? ['*'];

        // Wildcard means all roles allowed
        if (in_array('*', $roles)) {
            return true;
        }

        $user = Auth::user();
        if (!$user) {
            return false;
        }

        foreach ($roles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check a gate string against the current user.
     *
     * Supported formats:
     * - 'role:super_admin' — checks user has role
     * - 'permission:view_dashboard' — checks user has permission
     * - 'can:update,App\Models\Post' — checks Gate::allows()
     *
     * @param  string $gate
     * @return bool
     */
    protected function checkGate(string $gate): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // role: check
        if (str_starts_with($gate, 'role:')) {
            $role = substr($gate, 5);
            return $user->hasRole(trim($role));
        }

        // permission: check
        if (str_starts_with($gate, 'permission:')) {
            $permission = substr($gate, 11);
            return \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView(trim($permission), $user);
        }

        // can: check (Laravel Gate)
        if (str_starts_with($gate, 'can:')) {
            $ability = substr($gate, 4);
            return Gate::allows(trim($ability));
        }

        // Unknown format — allow
        return true;
    }

    /**
     * Check a permission string against the current user.
     *
     * @param  string $permission
     * @return bool
     */
    protected function checkPermission(string $permission): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        return \QuickerFaster\UILibrary\Services\AccessControl\AuthorizationService::canAccessView($permission, $user);
    }

    /**
     * Check whether a module's dependencies are satisfied.
     *
     * @param  array $moduleConfig
     * @return bool
     */
    protected function areDependenciesSatisfied(array $moduleConfig): bool
    {
        $dependsOn = $moduleConfig['depends_on'] ?? [];

        if (empty($dependsOn)) {
            return true;
        }

        $allModules = config('ui-library.modules', []);

        foreach ($dependsOn as $dependency) {
            $depConfig = $allModules[$dependency] ?? null;

            if (!$depConfig || empty($depConfig['enabled'])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Determine whether any item in a section matches the current route.
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

            // Named route match
            if (!str_contains($route, '/') && $route === $currentRouteName) {
                return true;
            }

            // URL path match (prefix matching for nested pages)
            $pathToMatch = ltrim($route, '/');
            if ($pathToMatch === $currentPath || str_starts_with($currentPath, $pathToMatch . '/')) {
                return true;
            }
        }

        return false;
    }

    /**
     * Resolve the navigation config file path for a module.
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
}