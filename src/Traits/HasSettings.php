<?php

namespace QuickerFaster\UILibrary\Traits;

use QuickerFaster\UILibrary\Models\SystemSetting as Setting;
use Illuminate\Support\Facades\Cache;

trait HasSettings
{
    
public function settings()
{
    // Use 'owner' as morph name, but keep column names 'settable_type' and 'settable_id'
    return $this->morphMany(\QuickerFaster\UILibrary\Models\SystemSetting::class, 'settingable');
}



    public function getSetting(string $key, $default = null)
    {
        $cacheKey = $this->getSettingCacheKey($key);
        return Cache::remember($cacheKey, 3600, function () use ($key, $default) {
            $setting = $this->settings()->where('key', $key)->first();
            return $setting ? $setting->value : $default;
        });
    }

    public function setSetting(string $key, $value, ?string $group = null): void
    {
        $this->settings()->updateOrCreate(
            ['key' => $key],
            ['value' => $value, 'group' => $group]
        );
        Cache::forget($this->getSettingCacheKey($key));
    }

    public function forgetSetting(string $key): void
    {
        $this->settings()->where('key', $key)->delete();
        Cache::forget($this->getSettingCacheKey($key));
    }

    protected function getSettingCacheKey(string $key): string
    {
        return "setting.{$this->getMorphClass()}.{$this->id}.{$key}";
    }
}