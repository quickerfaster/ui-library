<?php

namespace QuickerFaster\UILibrary\Services\AccessControl;

use Illuminate\Support\Facades\File;

/**
 * Discovers model classes across both business modules (app/Modules) and
 * core modules (src/Core), providing a unified list of model names for
 * access control permission seeding and management.
 */
class ModelDiscovery
{
    /**
     * Base paths to scan for module Models directories, in priority order.
     *
     * @var array<int, string>
     */
    protected array $basePaths;

    /**
     * Namespace prefix for each base path, keyed by path.
     *
     * @var array<string, string>
     */
    protected array $namespaceMap;

    public function __construct(?array $basePaths = null)
    {
        $this->basePaths = $basePaths ?? [
            app_path('Modules'),
            base_path('vendor/quicker-faster/ui-library/src/Core'),
        ];

        $this->namespaceMap = [
            app_path('Modules') => config('ui-library.module_paths.business_namespace', 'App\\Modules'),
            base_path('vendor/quicker-faster/ui-library/src/Core') => 'QuickerFaster\\UILibrary\\Core',
        ];
    }

    /**
     * Get all module names discovered across all base paths.
     *
     * @return array<int, string>  Module directory names (e.g., ['Admin', 'Hr', 'System'])
     */
    public function getModuleNames(): array
    {
        $moduleNames = [];

        foreach ($this->basePaths as $basePath) {
            if (!is_dir($basePath)) {
                continue;
            }

            $directories = File::directories($basePath);
            foreach ($directories as $directory) {
                $moduleNames[] = basename($directory);
            }
        }

        return array_unique($moduleNames);
    }

    /**
     * Get all model class names for a given module.
     *
     * @param string $moduleName  e.g., 'Admin', 'Hr'
     * @return array<int, string>  e.g., ['User', 'Role', 'Permission']
     */
    public function getModelNames(string $moduleName): array
    {
        $modelNames = [];

        foreach ($this->basePaths as $basePath) {
            $modelsPath = $basePath . '/' . ucfirst($moduleName) . '/Models';

            if (!is_dir($modelsPath)) {
                continue;
            }

            $namespace = $this->namespaceMap[$basePath] . '\\' . ucfirst($moduleName) . '\\Models\\';

            $files = File::allFiles($modelsPath);
            foreach ($files as $file) {
                $relativePath = $file->getRelativePathname();
                $fullClassName = $namespace . str_replace(['/', '.php'], ['\\', ''], $relativePath);
                $modelNames[] = class_basename($fullClassName);
            }
        }

        return array_unique($modelNames);
    }

    /**
     * Get all model names across all modules.
     *
     * @return array<int, string>
     */
    public function getAllModelNames(): array
    {
        $allModels = [];
        $modules = $this->getModuleNames();

        foreach ($modules as $module) {
            $allModels = array_merge($allModels, $this->getModelNames($module));
        }

        return array_unique($allModels);
    }

    /**
     * Get the Models directory path for a given module.
     * Returns the first match across all base paths.
     *
     * @param string $moduleName
     * @return string|null
     */
    public function getModelsDirectory(string $moduleName): ?string
    {
        foreach ($this->basePaths as $basePath) {
            $path = $basePath . '/' . ucfirst($moduleName) . '/Models';
            if (is_dir($path)) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Get the namespace prefix for a given module's Models directory.
     *
     * @param string $moduleName
     * @return string|null
     */
    public function getModelsNamespace(string $moduleName): ?string
    {
        foreach ($this->basePaths as $basePath) {
            $path = $basePath . '/' . ucfirst($moduleName) . '/Models';
            if (is_dir($path)) {
                return $this->namespaceMap[$basePath] . '\\' . ucfirst($moduleName) . '\\Models\\';
            }
        }

        return null;
    }
}