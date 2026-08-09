<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Event;
use QuickerFaster\UILibrary\Events\ModuleRegistered;
use Spatie\Onboard\Facades\Onboard;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->discoverBusinessModules();
        $this->registerModuleConfigs();
        $this->registerOnboardingConfig();
    }

    /**
     * Discover business modules in app/Modules/ and register them.
     */
    private function discoverBusinessModules(): void
    {
        $businessPath = base_path('app/Modules');

        if (!is_dir($businessPath)) {
            return;
        }

        $moduleDirectories = glob($businessPath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirectories as $directory) {
            $moduleName = strtolower(basename($directory));

            // Skip if already registered as Core module
            if (config("ui-library.modules.{$moduleName}")) {
                continue;
            }

            // Register module in config
            config()->set("ui-library.modules.{$moduleName}", [
                'enabled' => true,
                'label' => ucfirst($moduleName),
                'icon' => 'fa-cube',
                'route' => "{$moduleName}.dashboard",
                'order' => 100,
                'roles' => ['*'],
                'core' => false,
            ]);

            // Fire event
            event(new ModuleRegistered($moduleName, $directory));

            // Register views
            $viewPath = "{$directory}/Resources/views";
            if (is_dir($viewPath)) {
                $this->loadViewsFrom($viewPath, $moduleName);
            }

            // Register routes (web)
            $webRoutePath = "{$directory}/Routes/web.php";
            if (file_exists($webRoutePath)) {
                \Route::middleware('web')->group($webRoutePath);
            }

            // Register routes (api)
            $apiRoutePath = "{$directory}/Routes/api.php";
            if (file_exists($apiRoutePath)) {
                \Route::prefix('api')->middleware('api')->group($apiRoutePath);
            }

            // Register migrations
            $migrationPath = "{$directory}/Database/Migrations";
            if (is_dir($migrationPath)) {
                $this->loadMigrationsFrom($migrationPath);
            }

            // Register event listeners
            $this->registerModuleEvents($directory, $moduleName);
        }

        // Load System catch-all route LAST (from Core, not app/Modules)
        $systemCatchAll = base_path('vendor/quicker-faster/ui-library/src/Core/System/Routes/web.php');
        if (file_exists($systemCatchAll)) {
            \Route::middleware('web')->group($systemCatchAll);
        }
    }

    /**
     * Register event listeners from a module's Listeners directory.
     */
    private function registerModuleEvents(string $modulePath, string $moduleName): void
    {
        $listenersPath = "{$modulePath}/Listeners";

        if (!is_dir($listenersPath)) {
            return;
        }

        $cacheKey = "module_event_listeners_{$moduleName}";

        if (app()->environment('production') && cache()->has($cacheKey)) {
            foreach (cache()->get($cacheKey) as $eventClass => $listenerClass) {
                Event::listen($eventClass, $listenerClass);
            }
            return;
        }

        $listenersMap = [];
        foreach (File::allFiles($listenersPath) as $file) {
            $listenerClass = $this->getClassFromFile($moduleName, 'Listeners', $file->getPathname());

            if (!class_exists($listenerClass)) {
                continue;
            }

            $eventClass = $this->getEventFromListener($listenerClass);
            if ($eventClass && class_exists($eventClass)) {
                Event::listen($eventClass, $listenerClass);
                $listenersMap[$eventClass] = $listenerClass;
            }
        }

        if (app()->environment('production')) {
            cache()->forever($cacheKey, $listenersMap);
        }
    }

    /**
     * Register module configs (dashboard, report, global).
     */
    private function registerModuleConfigs(): void
    {
        $modulePaths = [
            base_path('vendor/quicker-faster/ui-library/src/Core'),
            base_path('app/Modules'),
        ];

        foreach ($modulePaths as $basePath) {
            if (!is_dir($basePath)) continue;

            // Dashboard configs
            $dashboardFiles = glob($basePath . '/*/Data/Dashboards/*.php');
            foreach ($dashboardFiles as $path) {
                $module = strtolower(basename(dirname(dirname(dirname($path)))));
                $file = pathinfo($path, PATHINFO_FILENAME);
                $this->mergeConfigFrom($path, "{$module}_{$file}");
            }

            // Report configs
            $reportFiles = glob($basePath . '/*/Data/reports/*.php');
            $reportKeys = [];
            foreach ($reportFiles as $path) {
                $module = strtolower(basename(dirname(dirname(dirname($path)))));
                $file = pathinfo($path, PATHINFO_FILENAME);
                $key = "{$module}_{$file}";
                $this->mergeConfigFrom($path, $key);
                $reportKeys[] = $key;
            }
            if (!empty($reportKeys)) {
                $existing = config('reports.registered', []);
                config(['reports.registered' => array_merge($existing, $reportKeys)]);
            }
        }

        // Global configs from Core Common
        $commonConfigPath = base_path('vendor/quicker-faster/ui-library/src/Core/Common/Config');
        if (is_dir($commonConfigPath)) {
            foreach (['app_setup', 'app_tour', 'app_onboarding', 'app_general_settings'] as $config) {
                $path = "{$commonConfigPath}/{$config}.php";
                if (file_exists($path)) {
                    $this->mergeConfigFrom($path, $config);
                }
            }
        }
    }

    /**
     * Register onboarding steps from config.
     */
    private function registerOnboardingConfig(): void
    {
        $steps = config('app_onboarding.steps', []);

        foreach ($steps as $step) {
            Onboard::addStep($step['title'])
                ->link($step['link'])
                ->cta($step['cta'])
                ->completeIf(function ($user) use ($step) {
                    if (isset($step['model'])) {
                        return $step['model']::exists();
                    }

                    if (isset($step['condition'])) {
                        $condition = app($step['condition']);
                        return $condition($user);
                    }

                    return false;
                });
        }
    }

    private function getClassFromFile(string $moduleName, string $directory, string $filePath): string
    {
        $className = str_replace('.php', '', basename($filePath));
        return "App\\Modules\\{$moduleName}\\{$directory}\\{$className}";
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
}
