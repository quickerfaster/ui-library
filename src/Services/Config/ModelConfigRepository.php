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
        // 1. Get the list of keys we've ever cached
        $keys = Cache::get($this->cachePrefix . 'index', []);

        // 2. Forget each individual key
        foreach ($keys as $cacheKey) {
            Cache::forget($cacheKey);
        }

        // 3. Clear the index itself
        Cache::forget($this->cachePrefix . 'index');
    }

    protected function getCacheKey(string $configKey): string
    {
        $cacheKey = $this->cachePrefix . str_replace('.', '_', $configKey);

        // Track this key in an index so we can flush it later
        $keys = Cache::get($this->cachePrefix . 'index', []);
        if (!in_array($cacheKey, $keys)) {
            $keys[] = $cacheKey;
            Cache::forever($this->cachePrefix . 'index', $keys);
        }

        return $cacheKey;
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