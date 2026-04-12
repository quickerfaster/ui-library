<?php

namespace QuickerFaster\UILibrary\Traits;



use Exception;
use QuickerFaster\CodeGen\Data\ModelDefinition;

trait HasCacheInvalidator {
        /**
     * Invalidate the cached config for the given model.
     */
    protected function forgetModelConfigCache(string $configKey) : void // ModelDefinition $model): void
    {
        // Build the config key, e.g. 'hr.employee'
        //$configKey = strtolower($model->module) . '.' . strtolower($model->name);

        try {
            $repository = app(\QuickerFaster\UILibrary\Services\Config\ModelConfigRepository::class);
            $repository->forget($configKey);
            if ($this->command) {
                $this->command->line("  <comment>→ Cleared cache for config: {$configKey}</comment>");
            }
        } catch (\Exception $e) {
            // If the repository class doesn't exist (e.g., when UI library is not installed), silently ignore
            if ($this->command) {
                $this->command->warn("  Could not clear config cache: {$e->getMessage()}");
            }
        }
    }

}
