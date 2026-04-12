<?php

namespace QuickerFaster\UILibrary\Services\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ModelConfigRepository
{
    /**
     * Base path where module configs are stored (e.g., app/Modules)
     */
    protected string $basePath;

    /**
     * Cache prefix for model configs
     */
    protected string $cachePrefix = 'model_config_';

    public function __construct()
    {
        $this->basePath = app_path('Modules');
    }

    /**
     * Get the configuration array for a given config key (e.g., 'hr.employee').
     *
     * @param string $configKey
     * @return array
     * @throws \InvalidArgumentException
     */
    public function get(string $configKey): array
    {
        $cacheKey = $this->getCacheKey($configKey);
        return Cache::rememberForever($cacheKey, function () use ($configKey) {
            return $this->loadFromFile($configKey);
        });
    }

    /**
     * Invalidate the cached config for a specific key.
     * Call this after regenerating a model's config file.
     *
     * @param string $configKey
     * @return void
     */
    public function forget(string $configKey): void
    {
        Cache::forget($this->getCacheKey($configKey));
    }

    /**
     * Invalidate all cached model configs (useful for bulk regeneration).
     *
     * @return void
     */
    public function flush(): void
    {
        // In a real app you'd use a tagged cache or pattern matching.
        // For simplicity, we'll just clear all keys with our prefix.
        // This is acceptable if you don't have many other cache keys.
        $cacheStore = Cache::store();
        // Not all drivers support flush, so we rely on key prefix.
        // Alternative: store keys in a set and flush them.
    }

    protected function getCacheKey(string $configKey): string
    {
        return $this->cachePrefix . str_replace('.', '_', $configKey);
    }

   /**
     * Load the config file from disk based on the dotted key.
     * Examples:
     *   'hr.employee'                 → Modules/Hr/Data/employee.php
     *   'hr.dashboards.dashboard'     → Modules/Hr/Data/dashboards/dashboard.php
     *   'hr.dashboards.employee_overview' → Modules/Hr/Data/dashboards/employee_overview.php
     */
    protected function loadFromFile(string $configKey): array
    {
        $parts = explode('.', $configKey);
        if (count($parts) < 2) {
            throw new \InvalidArgumentException("Invalid config key format: {$configKey}. Expected 'module.path...'");
        }

        $module = ucfirst(array_shift($parts));
        // The remaining parts form the relative path (with dots replaced by directory separators)
        $relativePath = implode(DIRECTORY_SEPARATOR, $parts);
        $filePath = $this->basePath . '/' . $module . '/Data/' . $relativePath . '.php';

        if (!File::exists($filePath)) {
            throw new \InvalidArgumentException("Configuration not found for key: {$configKey} at {$filePath}");
        }

        return require $filePath;
    }
}