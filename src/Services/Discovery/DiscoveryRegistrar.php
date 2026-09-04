<?php

namespace QuickerFaster\UILibrary\Services\Discovery;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use QuickerFaster\UILibrary\Attributes\ReportType;
use QuickerFaster\UILibrary\Contracts\Reports\Reportable;

/**
 * Centralizes convention-based auto-discovery of consuming-app business-module
 * assets under app/Modules/*.
 *
 * The ModuleServiceProvider drives registration during boot; the
 * ui-library:discover command calls discover() to render a debuggable summary
 * of the same discoveries.
 *
 * Caching policy (production only):
 *  - Keys are content-hashed from the candidate file paths + mtimes, so they
 *    self-invalidate whenever a file is added, removed, or changed on deploy.
 *  - Values are stored with a finite TTL (never cache()->forever()).
 *  - Dev/test always re-scan so tests observe fresh state.
 */
class DiscoveryRegistrar
{
    /**
     * Register all discoverable business-module assets and return a summary.
     *
     * @return array<string, mixed>
     */
    public function registerAll(): array
    {
        $summary = [
            'modules' => [],
            'listeners' => [],
            'reports' => [],
            'workflows' => [],
        ];

        foreach ($this->businessModules() as $module) {
            $summary['modules'][$module['key']] = $module['path'];

            $listeners = $this->registerListeners($module['path'], $module['namespace'], $module['key']);
            if ($listeners !== []) {
                $summary['listeners'][$module['key']] = $listeners;
            }

            $reports = $this->registerReports($module['path'], $module['namespace'], $module['key']);
            if ($reports !== []) {
                $summary['reports'][$module['key']] = $reports;
            }

            $workflows = $this->registerWorkflows($module['path'], $module['key']);
            if ($workflows !== []) {
                $summary['workflows'][$module['key']] = $workflows;
            }
        }

        return $summary;
    }

    /**
     * Produce a fresh, non-mutating discovery summary for the discover command.
     *
     * This intentionally bypasses the production cache so the command always
     * reports the current on-disk state.
     *
     * @return array<string, mixed>
     */
    public function discover(): array
    {
        $summary = [
            'modules' => [],
            'listeners' => [],
            'reports' => [],
            'workflows' => [],
            'configs' => $this->discoverConfigs(),
            'permissions' => $this->discoverPermissions(),
            'notifications' => $this->discoverNotifications(),
        ];

        foreach ($this->businessModules() as $module) {
            $summary['modules'][$module['key']] = $module['path'];

            $listeners = $this->discoverListeners($module);
            if ($listeners !== []) {
                $summary['listeners'][$module['key']] = $listeners;
            }

            $reports = $this->discoverReports($module);
            if ($reports !== []) {
                $summary['reports'][$module['key']] = $reports;
            }

            $workflows = $this->discoverWorkflows($module);
            if ($workflows !== []) {
                $summary['workflows'][$module['key']] = $workflows;
            }
        }

        return $summary;
    }

    // ---------------------------------------------------------------------
    // Listeners
    // ---------------------------------------------------------------------

    /**
     * Register event listeners from a module's Listeners directory.
     */
    public function registerListeners(string $modulePath, string $moduleNamespace, string $moduleKey): array
    {
        if (!config('ui-library.discovery.listeners', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$moduleKey}.auto_register_listeners", true)) {
            return [];
        }

        $files = $this->listenerFiles($modulePath);
        if ($files === []) {
            return [];
        }

        $cacheKey = $this->cacheKey('listeners', 2, $moduleNamespace, $files);

        if ($this->inProduction() && cache()->has($cacheKey)) {
            $listenersMap = cache()->get($cacheKey, []);
            $this->applyListeners($listenersMap);

            return $listenersMap;
        }

        $listenersMap = $this->scanListeners($moduleNamespace, $modulePath, $files);
        $this->applyListeners($listenersMap);

        if ($this->inProduction() && $listenersMap !== []) {
            cache()->put($cacheKey, $listenersMap, $this->cacheTtl());
        }

        return $listenersMap;
    }

    private function listenerFiles(string $modulePath): array
    {
        $listenersPath = "{$modulePath}/Listeners";

        return is_dir($listenersPath) ? File::allFiles($listenersPath) : [];
    }

    /**
     * One-to-many: an event may have many listeners.
     *
     * @return array<string, string[]>
     */
    private function scanListeners(string $moduleNamespace, string $modulePath, array $files): array
    {
        $listenersMap = [];

        foreach ($files as $file) {
            $listenerClass = $this->getClassFromRelativeFile($moduleNamespace, $modulePath, $file->getPathname());

            if (!class_exists($listenerClass)) {
                continue;
            }

            $eventClass = $this->getEventFromListener($listenerClass);
            if ($eventClass && class_exists($eventClass)) {
                $listenersMap[$eventClass][] = $listenerClass;
            }
        }

        return $listenersMap;
    }

    private function applyListeners(array $listenersMap): void
    {
        foreach ($listenersMap as $eventClass => $listeners) {
            foreach ((array) $listeners as $listenerClass) {
                Event::listen($eventClass, $listenerClass);
            }
        }
    }

    private function getEventFromListener(string $class): ?string
    {
        try {
            $reflection = new \ReflectionClass($class);
            if ($reflection->hasMethod('handle')) {
                $method = $reflection->getMethod('handle');
                $parameters = $method->getParameters();
                if (!empty($parameters)) {
                    $parameterType = $parameters[0]->getType();

                    return $parameterType ? $parameterType->getName() : null;
                }
            }
        } catch (\ReflectionException $e) {
            // Silently skip
        }

        return null;
    }

    // ---------------------------------------------------------------------
    // Reports (Reportable)
    // ---------------------------------------------------------------------

    /**
     * Auto-register Reportable implementations into ui-library.reports.report_types.
     */
    public function registerReports(string $modulePath, string $moduleNamespace, string $moduleKey): array
    {
        if (!config('ui-library.discovery.reports', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$moduleKey}.auto_register_reports", true)) {
            return [];
        }

        $files = $this->reportFiles($modulePath);
        if ($files === []) {
            return [];
        }

        $cacheKey = $this->cacheKey('reports', 1, $moduleNamespace, $files);

        if ($this->inProduction() && cache()->has($cacheKey)) {
            $discovered = cache()->get($cacheKey, []);
            $this->mergeReportTypes($discovered);

            return $discovered;
        }

        $discovered = $this->scanReports($moduleNamespace, $modulePath, $files);
        $this->mergeReportTypes($discovered);

        if ($this->inProduction() && $discovered !== []) {
            cache()->put($cacheKey, $discovered, $this->cacheTtl());
        }

        return $discovered;
    }

    /**
     * Candidate report files: the Reports/ convention directory plus any other
     * PSR-4 class file in the module (uppercase basename) that may implement
     * Reportable elsewhere.
     *
     * @return string[]
     */
    private function reportFiles(string $modulePath): array
    {
        $files = [];

        $reportsDir = "{$modulePath}/Reports";
        if (is_dir($reportsDir)) {
            foreach (glob($reportsDir . '/*.php') ?: [] as $path) {
                $files[] = $path;
            }
        }

        // Fallback: any class file (PascalCase basename) anywhere in the module
        // that implements Reportable, honouring "any class in app/Modules/*".
        if (is_dir($modulePath)) {
            foreach (File::allFiles($modulePath) as $file) {
                if ($file->getExtension() !== 'php') {
                    continue;
                }

                $base = $file->getBasename('.php');
                if ($base === '' || $base[0] !== strtoupper($base[0])) {
                    // Skip lowercase config/route/data files that cannot be PSR-4 classes.
                    continue;
                }

                $files[] = $file->getPathname();
            }
        }

        return array_values(array_unique($files));
    }

    /**
     * @return array<string, string>
     */
    private function scanReports(string $moduleNamespace, string $modulePath, array $files): array
    {
        $discovered = [];

        foreach ($files as $path) {
            $class = $this->getClassFromRelativeFile($moduleNamespace, $modulePath, $path);

            if (!class_exists($class) || !is_subclass_of($class, Reportable::class)) {
                continue;
            }

            $type = $this->resolveReportType($class);
            if ($type === null) {
                continue;
            }

            $discovered[$type] = $class;
        }

        return $discovered;
    }

    private function resolveReportType(string $class): ?string
    {
        $reflection = new \ReflectionClass($class);

        // 1. #[ReportType('foo')] attribute (no instantiation required).
        $attributes = $reflection->getAttributes(ReportType::class);
        if ($attributes !== []) {
            $type = $attributes[0]->newInstance()->type;
            if (is_string($type) && $type !== '') {
                return $type;
            }
        }

        // 2. public const REPORT_TYPE.
        if ($reflection->hasConstant('REPORT_TYPE')) {
            $constant = $reflection->getConstant('REPORT_TYPE');
            if (is_string($constant) && $constant !== '') {
                return $constant;
            }
        }

        // 3. Fallback to app($class)->getReportType().
        try {
            $type = app($class)->getReportType();

            return is_string($type) && $type !== '' ? $type : null;
        } catch (\Throwable $e) {
            return null;
        }
    }

    private function mergeReportTypes(array $discovered): void
    {
        if ($discovered === []) {
            return;
        }

        $existing = config('ui-library.reports.report_types', []);
        config()->set('ui-library.reports.report_types', $this->deepMerge($discovered, $existing));
    }

    // ---------------------------------------------------------------------
    // Workflows
    // ---------------------------------------------------------------------

    /**
     * Merge app/Modules/{Module}/Config/workflows.php into ui-library.workflows.definitions.
     */
    public function registerWorkflows(string $modulePath, string $moduleKey): array
    {
        if (!config('ui-library.discovery.workflows', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$moduleKey}.auto_register_workflows", true)) {
            return [];
        }

        $files = $this->workflowFiles($modulePath);
        if ($files === []) {
            return [];
        }

        $cacheKey = $this->cacheKey('workflows', 1, $moduleKey, $files);

        if ($this->inProduction() && cache()->has($cacheKey)) {
            $discovered = cache()->get($cacheKey, []);
            $this->mergeWorkflows($discovered);

            return $discovered;
        }

        $discovered = $this->scanWorkflows($files);
        $this->mergeWorkflows($discovered);

        if ($this->inProduction() && $discovered !== []) {
            cache()->put($cacheKey, $discovered, $this->cacheTtl());
        }

        return $discovered;
    }

    /**
     * @return string[]
     */
    private function workflowFiles(string $modulePath): array
    {
        $file = "{$modulePath}/Config/workflows.php";

        return file_exists($file) ? [$file] : [];
    }

    /**
     * @param  string[] $files
     * @return array<string, array<string, mixed>>
     */
    private function scanWorkflows(array $files): array
    {
        $discovered = [];

        foreach ($files as $path) {
            $definitions = require $path;

            if (!is_array($definitions)) {
                continue;
            }

            foreach ($definitions as $key => $definition) {
                if (is_string($key) && is_array($definition)) {
                    $discovered[$key] = $definition;
                }
            }
        }

        return $discovered;
    }

    private function mergeWorkflows(array $discovered): void
    {
        if ($discovered === []) {
            return;
        }

        $existing = config('ui-library.workflows.definitions', []);
        config()->set('ui-library.workflows.definitions', $this->deepMerge($discovered, $existing));
    }

    // ---------------------------------------------------------------------
    // Config merges (dashboard/report Data configs)
    // ---------------------------------------------------------------------

    /**
     * Report the convention-based Data config files that ModuleServiceProvider
     * merges into {module}_{file} config keys (mirrors registerModuleConfigs).
     *
     * @return array{dashboards: array<int, array<string, string>>, reports: array<int, array<string, string>>}
     */
    public function discoverConfigs(): array
    {
        $businessPath = config('ui-library.module_paths.business', base_path('app/Modules'));
        $result = ['dashboards' => [], 'reports' => []];

        if (!is_dir($businessPath)) {
            return $result;
        }

        foreach (glob($businessPath . '/*/Data/Dashboards/*.php') ?: [] as $path) {
            $module = strtolower(basename(dirname(dirname(dirname($path)))));
            $file = pathinfo($path, PATHINFO_FILENAME);
            $result['dashboards'][] = [
                'module' => $module,
                'config' => "{$module}_{$file}",
                'path' => $path,
            ];
        }

        foreach (glob($businessPath . '/*/Data/reports/*.php') ?: [] as $path) {
            $module = strtolower(basename(dirname(dirname(dirname($path)))));
            $file = pathinfo($path, PATHINFO_FILENAME);
            $result['reports'][] = [
                'module' => $module,
                'config' => "{$module}_{$file}",
                'path' => $path,
            ];
        }

        return $result;
    }

    // ---------------------------------------------------------------------
    // Fresh discovery (command)
    // ---------------------------------------------------------------------

    private function discoverListeners(array $module): array
    {
        if (!config('ui-library.discovery.listeners', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$module['key']}.auto_register_listeners", true)) {
            return [];
        }

        $files = $this->listenerFiles($module['path']);

        return $files === [] ? [] : $this->scanListeners($module['namespace'], $module['path'], $files);
    }

    private function discoverReports(array $module): array
    {
        if (!config('ui-library.discovery.reports', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$module['key']}.auto_register_reports", true)) {
            return [];
        }

        $files = $this->reportFiles($module['path']);

        return $files === [] ? [] : $this->scanReports($module['namespace'], $module['path'], $files);
    }

    private function discoverWorkflows(array $module): array
    {
        if (!config('ui-library.discovery.workflows', true)) {
            return [];
        }

        if (!config("ui-library.modules.{$module['key']}.auto_register_workflows", true)) {
            return [];
        }

        $files = $this->workflowFiles($module['path']);

        return $files === [] ? [] : $this->scanWorkflows($files);
    }

    // ---------------------------------------------------------------------
    // Permissions
    // ---------------------------------------------------------------------

    /**
     * Discover auto-generated permission names from business modules.
     *
     * @return array<string, array{models: array<string, string[]>, extra: string[]}>
     */
    private function discoverPermissions(): array
    {
        return \QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService::getDiscoverablePermissionNames();
    }

    // ---------------------------------------------------------------------
    // Notifications
    // ---------------------------------------------------------------------

    /**
     * Discover notification templates and channels from business modules.
     *
     * @return array<string, array{templates: array<int, array<string, string>>, channels: array<string, string>}>
     */
    private function discoverNotifications(): array
    {
        $discovery = app(\QuickerFaster\UILibrary\Services\Notifications\NotificationDiscoveryService::class);
        return $discovery->discoverForCommand();
    }

    // ---------------------------------------------------------------------
    // Shared helpers
    // ---------------------------------------------------------------------

    /**
     * @return array<int, array{path: string, namespace: string, key: string}>
     */
    private function businessModules(): array
    {
        $businessPath = config('ui-library.module_paths.business', base_path('app/Modules'));

        if (!is_dir($businessPath)) {
            return [];
        }

        $modules = [];

        foreach (glob($businessPath . '/*', GLOB_ONLYDIR) ?: [] as $directory) {
            $namespace = basename($directory);
            $key = strtolower($namespace);

            // Skip Core modules that were registered separately.
            if (config("ui-library.modules.{$key}.core", false)) {
                continue;
            }

            $modules[] = [
                'path' => $directory,
                'namespace' => $namespace,
                'key' => $key,
            ];
        }

        return $modules;
    }

    /**
     * Resolve a PSR-4 class name from a file path relative to the module root.
     */
    private function getClassFromRelativeFile(string $moduleNamespace, string $modulePath, string $filePath): string
    {
        $relative = ltrim(str_replace($modulePath, '', $filePath), DIRECTORY_SEPARATOR);
        $segments = explode(DIRECTORY_SEPARATOR, $relative);
        $className = str_replace('.php', '', (string) array_pop($segments));

        $namespacePrefix = config('ui-library.module_paths.business_namespace', 'App\\Modules');
        $suffix = implode('\\', array_filter($segments));

        return $suffix === ''
            ? "{$namespacePrefix}\\{$moduleNamespace}\\{$className}"
            : "{$namespacePrefix}\\{$moduleNamespace}\\{$suffix}\\{$className}";
    }

    private function cacheKey(string $type, int $version, string $namespace, array $files): string
    {
        $signature = $namespace . '|' . implode('|', array_map(
            fn (string $path) => $path . ':' . @filemtime($path),
            $files
        ));

        return "ui-library.{$type}:v{$version}:" . md5($signature);
    }

    private function inProduction(): bool
    {
        return app()->environment('production');
    }

    private function cacheTtl(): int
    {
        return (int) config('ui-library.discovery.cache_ttl', 86400);
    }

    /**
     * Deep-merge discovered values under published config, with published
     * config winning on every conflict. Numeric (list) arrays are replaced
     * entirely rather than merged by position.
     *
     * @param  array<mixed> $discovered
     * @param  array<mixed> $published
     * @return array<mixed>
     */
    private function deepMerge(array $discovered, array $published): array
    {
        foreach ($published as $key => $value) {
            if (
                is_array($value)
                && isset($discovered[$key])
                && is_array($discovered[$key])
                && ! $this->isList($value)
                && ! $this->isList($discovered[$key])
            ) {
                $discovered[$key] = $this->deepMerge($discovered[$key], $value);
            } else {
                $discovered[$key] = $value;
            }
        }

        return $discovered;
    }

    private function isList(array $value): bool
    {
        return array_keys($value) === range(0, count($value) - 1);
    }
}
