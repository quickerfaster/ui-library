<?php

namespace QuickerFaster\UILibrary\Services\Settings;

use Illuminate\Support\Facades\Cache;

class SettingsManager
{
    protected array $resolvers = [];

    /**
     * Add a resolver (priority order: first added = highest priority)
     */
    public function addResolver(string $name, callable $resolver): self
    {
        $this->resolvers[$name] = $resolver;
        return $this;
    }

    /**
     * Get the effective setting value using cascading resolvers.
     */
    public function get(string $key, $default = null)
    {
        $cacheKey = $this->getCacheKey($key);
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            foreach ($this->resolvers as $resolver) {
                $value = $resolver($key);
                if ($value !== null) {
                    return $value;
                }
            }
            return $default;
        });
    }

    /**
     * Flush cache for a specific key (call after updating settings).
     */
    public function flush(string $key): void
    {
        Cache::forget($this->getCacheKey($key));
    }

    protected function getCacheKey(string $key): string
    {
        $context = $this->getContextHash();
        return "setting_resolved.{$context}.{$key}";
    }

    protected function getContextHash(): string
    {
        $userId = auth()->id() ?? 'guest';
        $module = request()->route('module') ?? session('active_module') ?? 'system';
        $companyId = \Illuminate\Support\Facades\Session::get('current_company_id', '0');
        return md5($userId . '_' . $module . '_' . $companyId);
    }
}