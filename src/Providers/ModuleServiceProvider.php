<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use QuickerFaster\UILibrary\Events\ModuleRegistered;
use QuickerFaster\UILibrary\Services\Discovery\DiscoveryRegistrar;
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
        $this->registerNotificationChannels();
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
            // Preserve the verbatim directory name for PSR-4 namespace resolution
            // (e.g. "Hr"), while keeping a separate lowercased key for config
            // keys, the module registry, route prefixes, and view namespaces.
            $moduleNamespace = basename($directory);
            $moduleName = strtolower($moduleNamespace);

            // Skip if already registered as Core module
            if (config("ui-library.modules.{$moduleName}.core", false)) {
                continue;
            }

            // Preserve explicit per-module auto-registration opt-outs that a
            // consuming app may have published, defaulting to enabled.
            $autoRegisterListeners = config(
                "ui-library.modules.{$moduleName}.auto_register_listeners",
                true
            );
            $autoRegisterReports = config(
                "ui-library.modules.{$moduleName}.auto_register_reports",
                true
            );
            $autoRegisterWorkflows = config(
                "ui-library.modules.{$moduleName}.auto_register_workflows",
                true
            );
            $autoRegisterPermissions = config(
                "ui-library.modules.{$moduleName}.auto_register_permissions",
                true
            );
            $autoRegisterNotifications = config(
                "ui-library.modules.{$moduleName}.auto_register_notifications",
                true
            );

            // Register module in config
            config()->set("ui-library.modules.{$moduleName}", [
                'enabled' => true,
                'label' => ucfirst($moduleName),
                'icon' => 'fa-cube',
                'route' => "{$moduleName}.dashboard",
                'order' => 100,
                'roles' => ['*'],
                'core' => false,
                'user_facing' => true,
                'depends_on' => [],
                'auto_register_listeners' => $autoRegisterListeners,
                'auto_register_reports' => $autoRegisterReports,
                'auto_register_workflows' => $autoRegisterWorkflows,
                'auto_register_permissions' => $autoRegisterPermissions,
                'auto_register_notifications' => $autoRegisterNotifications,
            ]);

            // Read user_facing and depends_on from config
            $userFacing = config("ui-library.modules.{$moduleName}.user_facing", true);
            $dependsOn = config("ui-library.modules.{$moduleName}.depends_on", []);

            // Validate depends_on modules are enabled
            foreach ($dependsOn as $dependency) {
                if (!config("ui-library.modules.{$dependency}.enabled", false)) {
                    throw new \RuntimeException(
                        "Module '{$moduleName}' depends on '{$dependency}', but it is not enabled."
                    );
                }
            }

            // Fire event
            event(new ModuleRegistered($moduleName, $directory, $userFacing, $dependsOn));

            // Allow this business module to be resolved via the catch-all route.
            // Core modules are already present in the config allow-list; app-level
            // modules are appended here so consuming apps do not need to publish
            // the config just to enable their own modules.
            $allowedModules = config('ui-library.catch_all.allowed_modules', []);
            if (!in_array($moduleName, $allowedModules, true)) {
                $allowedModules[] = $moduleName;
                config()->set('ui-library.catch_all.allowed_modules', $allowedModules);
            }

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

            // Register auto-discovered event listeners, Reportable implementations,
            // and workflow definitions.
            $discovery = app(DiscoveryRegistrar::class);
            $discovery->registerListeners($directory, $moduleNamespace, $moduleName);
            $discovery->registerReports($directory, $moduleNamespace, $moduleName);
            $discovery->registerWorkflows($directory, $moduleName);
        }

        // Load System catch-all route LAST (from Core, not app/Modules)
        $systemCatchAll = base_path('vendor/quicker-faster/ui-library/src/Core/System/Routes/web.php');
        if (file_exists($systemCatchAll)) {
            \Route::middleware('web')->group($systemCatchAll);
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
     * Register auto-discovered notification channels from business modules.
     */
    private function registerNotificationChannels(): void
    {
        $discovery = app(\QuickerFaster\UILibrary\Services\Notifications\NotificationDiscoveryService::class);
        $discovery->registerChannels();
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

    /**
     * Get only modules marked as user-facing.
     */
    public function getUserFacingModules(): array
    {
        $allModules = config('ui-library.modules', []);

        return array_filter($allModules, function ($config) {
            return ($config['enabled'] ?? false) && ($config['user_facing'] ?? false);
        });
    }
}
