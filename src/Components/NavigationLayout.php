<?php

namespace QuickerFaster\UILibrary\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Traits\NavigationFilter;
use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;
use QuickerFaster\UILibrary\Services\Navigation\NavigationManager;
use QuickerFaster\UILibrary\Services\Navigation\WorkspaceFilter;

class NavigationLayout extends Component
{
    use NavigationFilter;

    public ?string $configKey;
    public ?string $moduleName;
    public array $overrides;
    public ?string $context;

    public array $sharedHeaderItems = [];
    public array $sharedFooterItems = [];
    public ?string $activeContext = null;
    public array $contextGroups = [];
    public array $contextItems = [];
    public array $layoutConfig = [];
    public string $sidebarState = 'full';
    public ?array $currentContextItem = null;
    public array $sharedTopLeft = [];
    public array $sharedTopRight = [];
    public ?string $currentModelName = null;
    public ?string $settingsContext = null;

    public string $pageTitle;
    public array $breadcrumbItems;

    public string $contextMenuType;
    public string $contextMenuPosition;
    public bool $allowMenuTypeSwitch;

    // ------------------------------------------------------------------
    //  Phase 2: Cross-Context Dropdowns
    // ------------------------------------------------------------------

    /** @var bool When true, render all context groups as dropdowns in the horizontal bar. */
    public bool $showAllContexts = false;

    /** @var bool When true + showAllContexts, hide context tabs in TopNav. */
    public bool $hideTopnavContexts = false;

    // ------------------------------------------------------------------

    public ?ConfigResolver $configResolver = null;

    public function __construct(
        ?string $configKey = null,
        ?string $moduleName = null,
        ?string $context = null,
        array $overrides = []
    ) {
        $this->configKey = $configKey;
        if ($this->configKey) {
            try {
                $this->configResolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
            } catch (\Exception $e) {
                // Config not found — proceed without a resolver.
                // The navigation layout will still work with defaults.
                $this->configResolver = null;
            }
        }

        if ($this->configResolver) {
            try {
                $this->currentModelName = $this->configResolver->getModelName();
            } catch (\Exception $e) {
                $this->currentModelName = null;
            }
        }


        $this->moduleName = $moduleName;
        $this->overrides = $overrides;
        $this->context = $context;

        $this->determineModuleName();
        $this->loadNavigationConfig();
        $this->setActiveContext();
        
        $this->sidebarState = $this->layoutConfig['sidebar']['initial_state'] ?? 'full';
        $this->currentContextItem = $this->getCurrentContextItem();
        $this->pageTitle = $this->getPageTitle();
        $this->breadcrumbItems = $this->getBreadcrumbItems();

        // Context menu settings from config
        $this->contextMenuType = $this->layoutConfig['context_menu']['type'] ?? 'sidebar';
        $this->contextMenuPosition = $this->layoutConfig['context_menu']['position'] ?? 'left';
        $this->allowMenuTypeSwitch = $this->layoutConfig['context_menu']['allow_switch'] ?? false;

        // Override with session preference
        if (session()->has('context_menu_type')) {
            $this->contextMenuType = session('context_menu_type');
        }

        // Override with saved session preference if exists
        if (session()->has('sidebar_state')) {
            $this->sidebarState = session('sidebar_state');
        }

        // Phase 2: Cross-Context Dropdowns config
        $this->showAllContexts = (bool) ($this->layoutConfig['context_menu']['show_all_contexts'] ?? false);
        $this->hideTopnavContexts = (bool) ($this->layoutConfig['context_menu']['hide_topnav_contexts'] ?? false);
    }

    protected function determineModuleName(): void
    {
        // Only derive from ConfigResolver if no explicit moduleName was provided
        if (!$this->moduleName && $this->configKey) {
            try {
                $this->moduleName = $this->configResolver?->getModuleName() ?? 'module';
            } catch (\Exception $e) {
                // Fall through to use existing moduleName or throw
            }
        }

        // Fall back to session('active_module') when moduleName is still not set
        if (!$this->moduleName) {
            $this->moduleName = session('active_module', 'admin');
        }
    }

    protected function loadNavigationConfig(): void
    {
        $configPath = $this->resolveNavigationConfigPath($this->moduleName);

        if (!$configPath || !file_exists($configPath)) {
            $this->layoutConfig = [
                'top_bar' => ['enabled' => true],
                'context_menu' => ['type' => 'sidebar', 'position' => 'left', 'allow_switch' => true],
                'sidebar' => ['initial_state' => 'full'],
                'bottom_bar' => ['enabled' => true],
            ];
        } else {
            $config = require $configPath;
            $this->contextGroups = $config['context_groups'] ?? [];
            $this->contextItems = $config['contexts'] ?? [];
            $this->sharedHeaderItems = $config['shared_items']['header'] ?? [];
            $this->sharedFooterItems = $config['shared_items']['footer'] ?? [];
            $this->sharedTopLeft = $config['shared_top_items']['left'] ?? [];
            $this->sharedTopRight = $config['shared_top_items']['right'] ?? [];
            $this->layoutConfig = $config['layout'] ?? [];
        }

        // Apply overrides regardless of whether a navigation config file was found.
        // This ensures overrides work even when using the fallback layout config
        // (e.g. moduleName="common" with no Common/Config/navigation.php).
        foreach ($this->overrides as $key => $value) {
            if (isset($this->layoutConfig[$key]) && is_array($this->layoutConfig[$key]) && is_array($value)) {
                $this->layoutConfig[$key] = array_merge($this->layoutConfig[$key], $value);
            } else {
                $this->layoutConfig[$key] = $value;
            }
        }

        $this->contextGroups = $this->filterVisibleItems($this->contextGroups);
        foreach ($this->contextItems as $group => &$items) {
            $items = $this->filterVisibleItems($items);
        }
        $this->sharedHeaderItems = $this->filterVisibleItems($this->sharedHeaderItems);
        $this->sharedFooterItems = $this->filterVisibleItems($this->sharedFooterItems);
        $this->sharedTopLeft = $this->filterVisibleItems($this->sharedTopLeft);
        $this->sharedTopRight = $this->filterVisibleItems($this->sharedTopRight);

        // Apply workspace filtering: remove groups gated by feature flags
        // and items constrained by workspace context (role, department, etc.)
        $workspaceResolver = app(WorkspaceResolver::class);
        $workspaceFilter = new WorkspaceFilter($workspaceResolver->resolve());
        $this->contextGroups = $workspaceFilter->filterContextGroups($this->contextGroups);
        foreach ($this->contextItems as $group => &$items) {
            $items = $workspaceFilter->filterContextItems($items);
        }

        uasort($this->contextGroups, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        foreach ($this->contextItems as $groupKey => &$items) {
            usort($items, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));
        }
    }

    protected function setActiveContext(): void
    {
        if ($this->context && isset($this->contextGroups[$this->context])) {
            $this->activeContext = $this->context;
            return;
        }

        $currentPath = request()->path();
        $currentRouteName = Route::currentRouteName();


        foreach ($this->contextItems as $ctx => $items) {
            foreach ($items as $item) {
                $route = $item['route'] ?? null;
                if (!$route)
                    continue;

                if (!str_contains($route, '/') && $route === $currentRouteName) {
                    $this->activeContext = $ctx;
                    return;
                }
                $pathToMatch = ltrim($route, '/');
                if ($pathToMatch === $currentPath || str_starts_with($currentPath, $pathToMatch)) {
                    $this->activeContext = $ctx;
                    return;
                }
            }
        }

        $keys = array_keys($this->contextGroups);
        $this->activeContext = $this->context ?? ($keys[0] ?? null);
    }

    protected function getCurrentContextItem(): ?array
    {
        $currentPath = request()->path();
        $currentRouteName = Route::currentRouteName();
        $items = $this->contextItems[$this->activeContext] ?? [];

        foreach ($items as $item) {
            $route = $item['route'] ?? null;
            if (!$route)
                continue;

            if (!str_contains($route, '/') && $route === $currentRouteName) {
                return $item;
            }
            $pathToMatch = ltrim($route, '/');
            if ($pathToMatch === $currentPath || str_starts_with($currentPath, $pathToMatch)) {
                return $item;
            }
        }
        return null;
    }

    public function getBreadcrumbItems(): array
    {
        $items = [];

        // 1. Home
        if (config('ui-library.breadcrumb.show_home', true)) {
            $items[] = ['label' => __('Home'), 'url' => url('/')];
        }

        // 2. Application (module name)
        $moduleLabel = $this->resolveModuleLabel();
        $items[] = ['label' => $moduleLabel, 'url' => $this->resolveModuleUrl()];

        // 3. Workspace (active context group)
        if ($this->activeContext && isset($this->contextGroups[$this->activeContext])) {
            $group = $this->contextGroups[$this->activeContext];
            $items[] = ['label' => $group['label'], 'url' => $group['route'] ?? $group['url'] ?? null];
        }

        // 4. Section (active sidebar section from NavigationManager)
        $section = $this->resolveCurrentSection();
        if ($section && $section['label'] && strcasecmp((string) $section['label'], (string) $moduleLabel) !== 0) {
            $items[] = ['label' => $section['label'], 'url' => $section['url'] ?? null];
        }

        // 5. Page / Record
        if ($this->currentContextItem) {
            $items[] = [
                'label' => $this->currentContextItem['page_title'] ?? $this->currentContextItem['label'],
                'url' => $this->currentContextItem['route'] ?? null,
            ];
        }

        return $items;
    }

    /**
     * Resolve the current sidebar section via NavigationManager.
     *
     * @return array{label: string|null, url: string|null}|null
     */
    protected function resolveCurrentSection(): ?array
    {
        try {
            $sections = app(NavigationManager::class)->getSections($this->moduleName);
        } catch (\Throwable $e) {
            $sections = [];
        }

        foreach ($sections as $section) {
            if (!empty($section['has_active'])) {
                $url = null;
                $firstItem = $section['items'][0] ?? null;

                if ($firstItem) {
                    $url = $this->resolveBreadcrumbUrl(
                        $firstItem['route'] ?? null,
                        $firstItem['url'] ?? null
                    );
                }

                return [
                    'label' => $section['label'] ?? null,
                    'url' => $url,
                ];
            }
        }

        return null;
    }

    protected function resolveModuleLabel(): string
    {
        $moduleConfig = config('ui-library.modules.' . $this->moduleName);

        return $moduleConfig['label'] ?? $this->moduleName ?? 'Module';
    }

    protected function resolveModuleUrl(): ?string
    {
        $moduleConfig = config('ui-library.modules.' . $this->moduleName);

        if (! $moduleConfig) {
            return null;
        }

        if (! empty($moduleConfig['route'])) {
            try {
                return route($moduleConfig['route']);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $moduleConfig['url'] ?? null;
    }

    protected function resolveBreadcrumbUrl(?string $route, ?string $url): ?string
    {
        if ($route) {
            // A leading slash indicates a URL path rather than a named route.
            if (str_starts_with($route, '/')) {
                return $route;
            }

            try {
                return route($route);
            } catch (\Throwable $e) {
                return null;
            }
        }

        return $url;
    }

    public function getPageTitle(): string
    {
        $parts = [];
        if ($this->activeContext && isset($this->contextGroups[$this->activeContext])) {
            $parts[] = $this->contextGroups[$this->activeContext]['label'];
        }
        if ($this->currentContextItem) {
            $titlePart = $this->currentContextItem['page_title'] ?? $this->currentContextItem['label'];
            $parts[] = $titlePart;
        }
        $separator = config('ui-library.title.separator', ' - ');
        return implode($separator, $parts);
    }

    public function render()
    {
        // Determine settings context by checking the module's Config/settings.php
        $this->settingsContext = null;
        if ($this->activeContext && $this->moduleName) {
            $settingsPath = $this->resolveNavigationConfigPath($this->moduleName);
            // For settings, look in the same directory as navigation
            if ($settingsPath) {
                $settingsPath = dirname($settingsPath) . '/settings.php';
            }
            if ($settingsPath && file_exists($settingsPath)) {
                $moduleSettings = require $settingsPath;
                $contextKey = strtolower($this->activeContext);
                $contextSettings = $moduleSettings['contexts'][$contextKey]['groups'] ?? [];
                $this->settingsContext = (!empty($contextSettings)) ? $this->activeContext : null;
            }
        }

        return view('qf::components.layouts.navigation-layout', [
            'moduleName' => $this->moduleName,
            'configKey' => $this->configKey,
            'activeContext' => $this->activeContext,
            'contextGroups' => $this->contextGroups,
            'contextItems' => $this->contextItems,
            'sharedTopLeft' => $this->sharedTopLeft,
            'sharedTopRight' => $this->sharedTopRight,
            'sharedHeaderItems' => $this->sharedHeaderItems,
            'sharedFooterItems' => $this->sharedFooterItems,
            'sidebarState' => $this->sidebarState,
            'contextMenuType' => $this->contextMenuType,
            'contextMenuPosition' => $this->contextMenuPosition,
            'allowMenuTypeSwitch' => $this->allowMenuTypeSwitch,
            'pageTitle' => $this->pageTitle,
            'breadcrumbItems' => $this->breadcrumbItems,

            'layoutConfig' => $this->layoutConfig,
            'configResolver' => $this->configResolver,
            'crudType' => $this->configResolver?->getConfig()['crudType'] ?? 'modal',
            'currentModelName' => $this->currentModelName,
            'settingsContext' => $this->settingsContext,

            // Horizontal context menu overflow: per-module config > global config > default
            'maxVisibleItems' => $this->layoutConfig['context_menu']['max_visible_items']
                ?? config('ui-library.navigation.context_menu.max_visible_items', 7),

            // Phase 2: Cross-Context Dropdowns
            'showAllContexts' => $this->showAllContexts,
            'hideTopnavContexts' => $this->hideTopnavContexts,
        ]);

    }
    protected function resolveNavigationConfigPath(string $moduleName): ?string
    {
        // Priority order (descending): Published override → Business module → Core module → Vendor fallback
        // This ensures consuming apps can override library defaults.

        // 1. Published override (highest priority — consuming app's explicit customization)
        $publishedPath = resource_path(
            "views/vendor/ui-library/core/" . strtolower($moduleName) . "/Config/navigation.php"
        );
        if (file_exists($publishedPath)) {
            return $publishedPath;
        }

        // 2. Business module (consuming app's own module overrides library core)
        $businessPath = app_path("Modules/" . ucfirst($moduleName) . "/Config/navigation.php");
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

        // 4. Vendor fallback (for apps without published config)
        $vendorCorePath = base_path(
            "vendor/quicker-faster/ui-library/src/Core/" . ucfirst($moduleName) . "/Config/navigation.php"
        );
        if (file_exists($vendorCorePath)) {
            return $vendorCorePath;
        }

        return null;
    }
}