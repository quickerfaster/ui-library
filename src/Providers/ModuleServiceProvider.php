<?php

namespace QuickerFaster\UILibrary\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\File;
use Illuminate\Contracts\Http\Kernel;
use QuickerFaster\UILibrary\Http\Middleware\CheckSetup;
use Spatie\Onboard\Facades\Onboard;

class ModuleServiceProvider extends ServiceProvider
{
    public function register(): void
    {

    }


    public function boot(Kernel $kernel)
    {
        $this->registerPublishables();
        $this->registerModuleConfig();
        $this->setupModules();

        /****************** UNLOCK THE APPLICATION SETUP MIDDLEWARE ********************/
        // Appends to the end of the 'web' middleware group. 
        // alternatively: $kernel->appendMiddlewareToGroup() 
        // $kernel->appendMiddlewareToGroup('web', CheckSetup::class);

        $this->registerAppOnboardingCnfig();
    }


    private function registerAppOnboardingCnfig()
    {

        $steps = config('app_onboarding.steps', []);

        foreach ($steps as $step) {
            Onboard::addStep($step['title'])
                ->link($step['link'])
                ->cta($step['cta'])
                ->completeIf(function (\App\Models\User $user) use ($step) {
                    // If a model is given, check if at least one record exists globally
                    if (isset($step['model'])) {
                        return $step['model']::exists();
                    }

                    // If a condition class is given, resolve and invoke it
                    if (isset($step['condition'])) {
                        $condition = app($step['condition']);
                        return $condition($user);
                    }

                    // Default to false if nothing is defined
                    return false;
                });
        }
    }






    private function registerPublishables()
    {
        // Assets
        $this->publishes([
            //__DIR__ . '/../../resources/assets' => public_path('vendor/qf'),
            __DIR__ . '/../../public' => public_path('/'),
        ], 'qf-public-assets');


        $this->publishes([
            __DIR__ . '/../Modules' => app_path('Modules'),
        ], 'qf-modules');
    }


    private function setupModules()
    {
        // Path to your modules (e.g., app/Modules)
        $modulePath = base_path('app/Modules');

        if (!is_dir($modulePath)) {
            return;
        }

        $this->registerModuleViewAlias($modulePath);
        $this->registerModuleRoutes($modulePath);
        $this->registerModuleMigrations($modulePath);

    }








    private function registerModuleViewAlias($modulePath)
    {
        // 1. Get all module directories (e.g., app/Modules/Hr, app/Modules/Admin)
        $moduleDirectories = glob($modulePath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirectories as $directory) {
            // 2. Get the Module Name from the folder name
            $moduleName = basename($directory); // e.g., "Admin"

            // 3. Define the path to the views folder
            $viewPath = $directory . '/Resources/views';

            // 4. Check if the folder exists
            if (File::isDirectory($viewPath)) {
                // 5. Register the namespace (e.g., "admin" or "hr")
                $alias = strtolower($moduleName);
                // Standard Laravel way to register views in a provider
                $this->loadViewsFrom($viewPath, $alias);


            }
        }
    }







    private function registerModuleRoutes($modulePath)
    {
        if (!is_dir($modulePath)) {
            return;
        }

        // Load routes FIRST so the named route exists
        $this->loadRoutesFrom(__DIR__ . '/../Routes/web.php');


        // Find all config files: app/Modules/{ModuleName}/Config/{file}.php
        // $configFiles = glob($modulePath . '/*/Routes/*.php'); web.php and api.php
        $webRoutePath = glob($modulePath . '/*/Routes/web.php');
        foreach ($webRoutePath as $path) {
            // Load Routes
            // loads the module’s routes file (web.php), if it exists,
            //so you don’t have to manually include each route file for every module.
            // Suspend loading the 'system' modules's routes to avoid conflicts
            $moduleName = strtolower(basename(dirname(dirname($path))));
            if ($moduleName != 'system') {
                //$this->loadRoutesFrom($path);
                \Route::middleware('web')->group($path);
            }
        }


        // Now loade the 'system' module's routes
        // Dynamic route loading for the 'system' module should be done last to avoid conflicts
        if (File::exists(app_path("Modules/System/Routes/web.php"))) {
            \Route::middleware('web')->group(app_path("Modules/System/Routes/web.php"));
        }


        // System Api loading
        if (File::exists(app_path("Modules/System/Routes/api.php"))) {
            \Route::prefix('api')->middleware('api')->group(app_path("Modules/System/Routes/api.php"));
        }

    }


    private function registerModuleConfig()
    {

        $modulePath = app_path('Modules');
        //$this->mergeModuleConfigs($modulePath);
        // $this->mergeDashboardConfigs($modulePath);
        // $this->mergeReportConfigs($modulePath);


        // Merge gloal config
        if (File::exists("{$modulePath}/app_setup.php"))
            $this->mergeConfigFrom("{$modulePath}/app_setup.php", "app_setup");
        if (File::exists("{$modulePath}/app_tour.php"))
            $this->mergeConfigFrom("{$modulePath}/app_tour.php", "app_tour");
        if (File::exists("{$modulePath}/app_onboarding.php"))
            $this->mergeConfigFrom("{$modulePath}/app_onboarding.php", "app_onboarding");
        if (File::exists("{$modulePath}/app_general_settings.php"))
            $this->mergeConfigFrom("{$modulePath}/app_general_settings.php", "app_general_settings");

    }


    /*private function mergeModuleConfigs($modulePath)
    {
        // Find all .php files in Modules/{Name}/Data/
        $configFiles = glob($modulePath . '/* /Data/*.php');

        foreach ($configFiles as $path) {
            // Get the module name (e.g., 'Hr') and filename (e.g., 'employee')
            $module = strtolower(basename(dirname(dirname($path))));
            $file = pathinfo($path, PATHINFO_FILENAME);

            // Create the key: 'hr_employee'
            $key = "{$module}_{$file}";

            $this->mergeConfigFrom($path, $key);
        }
    }*/


    private function mergeDashboardConfigs($modulePath)
    {
        // Find all .php files in Modules/{Name}/Data/Dashboard
        $configFiles = glob($modulePath . '/*/Data/Dashboards/*.php');

        foreach ($configFiles as $path) {
            // Get the module name (e.g., 'Hr') and filename (e.g., 'employee')
            $module = strtolower(basename(dirname(dirname(dirname($path)))));
            $file = pathinfo($path, PATHINFO_FILENAME);

            // Create the key: 'hr_employee'
            $key = "{$module}_{$file}";
//dd($path, $key);
            $this->mergeConfigFrom($path, $key);
        }
    }

    
    /**
     * Merge report configuration files from all modules.
     *
     * @param string $modulePath Path to the Modules directory (e.g., app_path('Modules'))
     */
private function mergeReportConfigs($modulePath)
{
    $configFiles = glob($modulePath . '/*/Data/reports/*.php');
    $reportKeys = [];

    foreach ($configFiles as $path) {
        $module = strtolower(basename(dirname(dirname(dirname($path)))));
        $file   = pathinfo($path, PATHINFO_FILENAME);
        $key    = "{$module}_{$file}";

        $this->mergeConfigFrom($path, $key);
        $reportKeys[] = $key;
    }

    if (!empty($reportKeys)) {
        config(['reports.registered' => $reportKeys]);
    }
}




    private function registerModuleMigrations($modulePath)
    {
        // 1. Get all module directories (e.g., app/Modules/Hr, app/Modules/Admin)
        $moduleDirectories = glob($modulePath . '/*', GLOB_ONLYDIR);

        foreach ($moduleDirectories as $directory) {
            // 2. Define the path to the migrations folder
            $migrationsPath = $directory . '/Database/Migrations';

            // 3. Check if the folder exists and register the whole directory
            if (File::isDirectory($migrationsPath)) {
                $this->loadMigrationsFrom($migrationsPath);
            }
        }
    }






}
