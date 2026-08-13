<?php

namespace QuickerFaster\UILibrary\Services\Config;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;

class ModelConfigRepository
{
    /**
     * Base paths where module configs are stored, in resolution order.
     * First match wins — business modules (app/Modules) take priority over core modules (src/Core).
     *
     * @var array<int, string>
     */
    protected array $basePaths;

    /**
     * Cache prefix for model configs
     */
    protected string $cachePrefix = 'model_config_';

    public function __construct(?array $basePaths = null)
    {
        $this->basePaths = $basePaths ?? [
            app_path('Modules'),                                          // Business modules (higher priority)
            base_path('vendor/quicker-faster/ui-library/src/Core'),       // Core modules (fallback)
        ];
    }

    /**
     * Get the configuration array for a given config key (e.g., 'module.resource').
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
     * Searches multiple base paths in priority order (business modules first, then core).
     *
     * When the exact path is not found, progressive path fallback is applied:
     * intermediate segments between Data/ and the filename are stripped from the right,
     * trying each progressively shorter path. This handles the mismatch where config keys
     * use subdirectories (e.g., 'dashboards/dashboard') but library stores files flat
     * (e.g., 'dashboard.php' directly under Data/).
     *
     * Resolution order for 'admin.dashboards.dashboard':
     *   1. app/Modules/Admin/Data/dashboards/dashboard.php  — consuming app exact
     *   2. src/Core/Admin/Data/dashboards/dashboard.php      — library exact
     *   3. app/Modules/Admin/Data/dashboard.php              — consuming app stripped
     *   4. src/Core/Admin/Data/dashboard.php                 — library stripped ✓
     *
     * Examples:
     *   'module.resource'             → app/Modules/{Module}/Data/{resource}.php
     *   'admin.user'                  → src/Core/Admin/Data/user.php
     *   'admin.dashboards.dashboard'  → src/Core/Admin/Data/dashboard.php (via fallback)
     *   'system.dashboards.dashboard' → src/Core/System/Data/dashboard.php (via fallback)
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

        $searchedPaths = [];

        // 1. Try exact paths first (backward compatible — consuming app wins, then library)
        foreach ($this->basePaths as $basePath) {
            $filePath = $basePath . '/' . $module . '/Data/' . $relativePath . '.php';
            $searchedPaths[] = $filePath;

            if (File::exists($filePath)) {
                return require $filePath;
            }
        }

        // 2. Progressive path fallback: strip intermediate segments from the right.
        //    Only applies when there are at least 2 segments beyond the module
        //    (e.g., 'dashboards/dashboard' → try 'dashboard').
        //    For 'a/b/c/file': try 'a/b/file', 'a/file', 'file'.
        if (count($parts) >= 2) {
            for ($i = count($parts) - 2; $i >= 0; $i--) {
                // Keep the first $i intermediate segments, then append the filename
                $strippedParts = $i === 0
                    ? [end($parts)]  // just the filename (strip all intermediates)
                    : array_merge(
                        array_slice($parts, 0, $i),
                        [end($parts)]
                    );
                $strippedPath = implode(DIRECTORY_SEPARATOR, $strippedParts);

                foreach ($this->basePaths as $basePath) {
                    $fallbackPath = $basePath . '/' . $module . '/Data/' . $strippedPath . '.php';
                    $searchedPaths[] = $fallbackPath;

                    if (File::exists($fallbackPath)) {
                        return require $fallbackPath;
                    }
                }
            }
        }

        throw new \InvalidArgumentException(
            "Configuration not found for key: {$configKey}. " .
            "Searched paths:\n  - " . implode("\n  - ", $searchedPaths)
        );
    }
}