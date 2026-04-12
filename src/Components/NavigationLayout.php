<?php

namespace QuickerFaster\UILibrary\Components;

use Illuminate\View\Component;
use Illuminate\Support\Facades\Route;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;
use QuickerFaster\UILibrary\Traits\NavigationFilter;

class NavigationLayout extends Component
{
    use NavigationFilter;

    public ?string $configKey;
    public ?string $moduleName;
    public array $overrides;
    public ?string $context;

    public array $sharedHeaderItems = [];
    public array $sharedFooterItems = [];
    public string $activeContext;
    public array $contextGroups = [];
    public array $contextItems = [];
    public array $layoutConfig = [];
    public string $sidebarState = 'full';
    public ?array $currentContextItem = null;
    public array $sharedTopLeft = [];
    public array $sharedTopRight = [];
    public ?string $currentModelName = null;

    public string $pageTitle;
    public array $breadcrumbItems;

    public string $contextMenuType;
    public string $contextMenuPosition;
    public bool $allowMenuTypeSwitch;

    public ConfigResolver $configResolver;

    public function __construct(
        ?string $configKey = null,
        ?string $moduleName = null,
        ?string $context = null,
        array $overrides = []
    ) {
        $this->configKey = $configKey;
        if ($this->configKey) {
            $this->configResolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        }

        if ($this->configResolver) {
            $this->currentModelName = $this->configResolver->getModelName();
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
    }

    protected function determineModuleName(): void
    {
        if ($this->configKey) {
            try {
                $this->moduleName = $this->configResolver?->getModuleName() ?? 'module';
            } catch (\Exception $e) {
                if (!$this->moduleName) {
                    throw new \InvalidArgumentException("No valid module name could be determined.");
                }
            }
        }
        if (!$this->moduleName) {
            throw new \InvalidArgumentException("NavigationLayout requires either a valid configKey or moduleName.");
        }
    }

    protected function loadNavigationConfig(): void
    {
        $moduleName = ucfirst($this->moduleName);
        $configPath = app_path("Modules/{$moduleName}/Config/navigation.php");

        if (!file_exists($configPath)) {
            $this->layoutConfig = [
                'top_bar' => ['enabled' => true],
                'context_menu' => ['type' => 'sidebar', 'position' => 'left'],
                'sidebar' => ['initial_state' => 'full'],
                'bottom_bar' => ['enabled' => true],
            ];
            return;
        }

        $config = require $configPath;
        $this->contextGroups = $config['context_groups'] ?? [];
        $this->contextItems = $config['contexts'] ?? [];
        $this->sharedHeaderItems = $config['shared_items']['header'] ?? [];
        $this->sharedFooterItems = $config['shared_items']['footer'] ?? [];
        $this->sharedTopLeft = $config['shared_top_items']['left'] ?? [];
        $this->sharedTopRight = $config['shared_top_items']['right'] ?? [];
        $this->layoutConfig = $config['layout'] ?? [];

        foreach ($this->overrides as $key => $value) {
            if (isset($this->layoutConfig[$key]) && is_array($this->layoutConfig[$key])) {
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
        $this->activeContext = $this->context?? ($keys[0] ?? '');
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
        if (config('quicker-faster-ui.breadcrumb.show_home', true)) {
            $items[] = ['label' => __('Home'), 'url' => url('/')];
        }
        if ($this->activeContext && isset($this->contextGroups[$this->activeContext])) {
            $group = $this->contextGroups[$this->activeContext];
            $items[] = ['label' => $group['label'], 'url' => $group['route'] ?? $group['url'] ?? null];
        }
        if ($this->currentContextItem) {
            $items[] = ['label' => $this->currentContextItem['label'], 'url' => $this->currentContextItem['route'] ?? null];
        }
        return $items;
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
        $separator = config('quicker-faster-ui.title.separator', ' - ');
        return implode($separator, $parts);
    }

    public function render()
    {
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
            'viewType' => $this->configResolver?->getConfig()['viewType'] ?? 'modal',
            'currentModelName' => $this->currentModelName,  


        ]);

    }
}