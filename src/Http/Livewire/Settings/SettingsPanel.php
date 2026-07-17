<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Settings;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;
use App\Modules\System\Models\System;
use Illuminate\Support\Facades\Auth;

class SettingsPanel extends Component
{
    public string $mode = 'user'; // 'user', 'system', or 'company'

    public function getPanelTitleProperty(): string
    {
        if ($this->context) {
            return $this->groups[$this->activeGroup]['label'] ?? 'Settings';
        }
        return match ($this->mode) {
            'system' => 'General Settings',
            'company' => 'Company Settings',
            default => 'My Preferences',
        };
    }
    public ?string $context = null;
    public ?string $moduleName = null;
    public string $activeGroup = 'general';
    public array $groups = [];
    public array $overrides = [];   // temporary storage for unsaved changes
    public array $effectiveValues = [];
    public array $inheritance = [];
    public array $patternPreviews = [];
    public array $settingsMap = [];  // key → setting definition (public for Livewire persistence)

    protected SettingsManager $settingsManager;

    public function boot(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function mount(string $mode = 'user', ?string $context = null, ?string $initialGroup = null, ?string $moduleName = null)
    {
        $this->mode = $mode;
        $this->context = $context;
        $this->moduleName = $moduleName;
        $this->loadGroups($initialGroup);
        $this->loadCurrentValues();
    }

    public function loadGroups(?string $initialGroup = null): void
    {
        if ($this->context && $this->moduleName) {
            // Context-specific settings: load from module's Config/settings.php
            $moduleName = ucfirst($this->moduleName);
            $settingsPath = app_path("Modules/{$moduleName}/Config/settings.php");
            if (file_exists($settingsPath)) {
                $moduleSettings = require $settingsPath;
                $this->groups = $moduleSettings['contexts'][$this->context]['groups'] ?? [];
            } else {
                $this->groups = [];
            }
        } else {
            // User preferences: load global groups only
            $this->groups = config('app_general_settings.groups', []);
        }

        if (empty($this->groups)) {
            return;
        }

        // Build flat settings map for lookup by key
        $this->settingsMap = [];
        foreach ($this->groups as $group) {
            foreach ($group['settings'] as $setting) {
                $this->settingsMap[$setting['key']] = $setting;
            }
        }

        if ($initialGroup && isset($this->groups[$initialGroup])) {
            $this->activeGroup = $initialGroup;
        } else {
            $firstGroup = array_key_first($this->groups);
            $this->activeGroup = $firstGroup;
        }
    }

    public function loadCurrentValues(): void
    {
        $this->effectiveValues = [];
        $this->inheritance = [];
        $this->overrides = [];

        foreach ($this->groups as $groupKey => $group) {
            foreach ($group['settings'] as $setting) {
                $key = $setting['key'];
                // Get effective value (with inheritance)
                $effective = $this->settingsManager->get($key, $setting['default'] ?? null);
                $this->effectiveValues[$key] = $effective;

                // Get user's own stored value (if any)
                $userValue = $this->getSettableModel()->getSetting($key);
                $this->overrides[$key] = $userValue;

                // Determine inheritance source
                $this->inheritance[$key] = $this->resolveInheritanceSource($key);
            }
        }
    }

    protected function getSettableModel()
    {
        if ($this->mode === 'system') {
            return System::find(1);
        }
        if ($this->mode === 'company') {
            $companyId = \Illuminate\Support\Facades\Session::get('current_company_id') ?? auth()->user()?->company_id;
            return $companyId ? \App\Modules\Hr\Models\Company::find($companyId) : System::find(1);
        }
        return Auth::user();
    }

    protected function resolveInheritanceSource(string $key): ?string
    {
        // For now only for user later the system and module will be implemented
        $userValue = $this->getSettableModel()->getSetting($key);
        if ($userValue !== null) {
            return 'user';
        }

        // Check context (module/organization) – simplified for demo
        /*$contextValue = null;
        $moduleSlug = request()->route('module') ?? session('active_module');
        if ($moduleSlug) {
            $module = \App\Models\Module::where('slug', $moduleSlug)->first();
            $contextValue = $module?->getSetting($key);
        }
        if ($contextValue !== null) {
            return 'context';
        }*/

        // Otherwise system default
        return 'system';
    }

    public function updatedOverrides($value, $key)
    {
        // Called when user changes an input/select
        // We don't save immediately – we'll save only when clicking "Save"
    }

    public function insertPatternPlaceholder(string $key, string $placeholder): void
    {
        $current = $this->overrides[$key] ?? '';
        $this->overrides[$key] = $current . $placeholder;
    }

    public function previewPattern(string $key): void
    {
        $pattern = $this->overrides[$key] ?? '';
        $now = now();

        $preview = $pattern;
        $preview = str_replace('{year}', $now->format('Y'), $preview);
        $preview = str_replace('{year:2}', $now->format('y'), $preview);
        $preview = str_replace('{month}', $now->format('m'), $preview);
        $preview = str_replace('{month:short}', $now->format('M'), $preview);
        $preview = str_replace('{day}', $now->format('d'), $preview);
        $preview = preg_replace('/\{sequence:(\d+)\}/', '0__SEQ__$1', $preview);
        $preview = preg_replace_callback('/0__SEQ__(\d+)/', fn($m) => str_repeat('0', (int) $m[1]), $preview);
        $preview = str_replace('{sequence}', '00000', $preview);
        $preview = str_replace('{id}', 'NEW', $preview);

        $this->patternPreviews[$key] = $preview;
    }

    public function saveSetting(string $key)
    {
        $newValue = $this->overrides[$key] ?? null;
        if ($newValue === null) {
            $this->resetSetting($key);
        } else {
            $this->getSettableModel()->setSetting($key, $newValue);

            // Flush cache for this key so SettingsManager picks up the new value
            $this->settingsManager->flush($key);

            // Dual-write for pattern settings: also save under the dotted key ConfigResolver expects
            $settingDef = $this->settingsMap[$key] ?? [];
            if (($settingDef['pattern_model'] ?? '') && ($settingDef['pattern_field'] ?? '')) {
                $dottedKey = 'auto_gen.' . $settingDef['pattern_model'] . '.' . $settingDef['pattern_field'] . '.pattern';
                $this->getSettableModel()->setSetting($dottedKey, $newValue);
                $this->settingsManager->flush($dottedKey);
            }

            $this->dispatch('setting-updated', $key, $newValue);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => "Setting saved: {$key}"]);
        }
        $this->loadCurrentValues(); // refresh
    }

    public function resetSetting(string $key)
    {
        $this->getSettableModel()->forgetSetting($key);
        $this->settingsManager->flush($key);

        // Also reset the dotted key for pattern settings
        $settingDef = $this->settingsMap[$key] ?? [];
        if (($settingDef['pattern_model'] ?? '') && ($settingDef['pattern_field'] ?? '')) {
            $dottedKey = 'auto_gen.' . $settingDef['pattern_model'] . '.' . $settingDef['pattern_field'] . '.pattern';
            $this->getSettableModel()->forgetSetting($dottedKey);
            $this->settingsManager->flush($dottedKey);
        }

        $this->dispatch('setting-updated', $key, null);
        $this->dispatch('showAlert', ['type' => 'info', 'message' => "Reset to default"]);
        $this->loadCurrentValues();
    }

    public function setActiveGroup(string $groupKey)
    {
        $this->activeGroup = $groupKey;
    }

    public function render()
    {
        $currentGroupSettings = $this->groups[$this->activeGroup]['settings'] ?? [];

        return view('qf::livewire.settings.settings-panel', [
            'currentGroupSettings' => $currentGroupSettings,
        ]);
    }
}





