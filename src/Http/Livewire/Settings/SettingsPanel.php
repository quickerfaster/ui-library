<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Settings;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Settings\SettingsManager;
use App\Models\System;
use Illuminate\Support\Facades\Auth;

class SettingsPanel extends Component
{
    public string $mode = 'user'; // 'user' or 'system'
    public string $activeGroup = 'general';
    public array $groups = [];
    public array $overrides = [];   // temporary storage for unsaved changes
    public array $effectiveValues = [];
    public array $inheritance = [];

    protected SettingsManager $settingsManager;

    public function boot(SettingsManager $settingsManager)
    {
        $this->settingsManager = $settingsManager;
    }

    public function mount(string $mode = 'user')
    {
        $this->mode = $mode;
        $this->loadGroups();
        $this->loadCurrentValues();
    }

    public function loadGroups(): void
    {
        $this->groups = config('app_general_settings.groups', []);
        if (empty($this->groups)) {
            return;
        }
        $firstGroup = array_key_first($this->groups);
        $this->activeGroup = $firstGroup;
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

    public function saveSetting(string $key)
    {
        $newValue = $this->overrides[$key] ?? null;
        if ($newValue === null) {
            $this->resetSetting($key);
        } else {
            $this->getSettableModel()->setSetting($key, $newValue);
            $this->dispatch('setting-updated', $key, $newValue);
            $this->dispatch('showAlert', ['type' => 'success', 'message' => "Setting saved: {$key}"]);
        }
        $this->loadCurrentValues(); // refresh
    }

    public function resetSetting(string $key)
    {
        $this->getSettableModel()->forgetSetting($key);
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





