<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;
use QuickerFaster\UILibrary\Events\NavigationBuilding;

class ModuleSwitcher extends Component
{
    public array $modules = [];
    public string $activeModule = 'admin';

    public function mount(): void
    {
        $this->loadModules();
    }

    public function switchModule(string $moduleKey): void
    {
        session(['active_module' => $moduleKey]);
        $this->activeModule = $moduleKey;

        $module = collect($this->modules)->firstWhere('key', $moduleKey);
        if ($module && isset($module['route'])) {
            $this->redirect(route($module['route']));
        }
    }

    protected function loadModules(): void
    {
        $allModules = config('ui-library.modules', []);

        $modules = [];
        foreach ($allModules as $key => $config) {
            if (!($config['enabled'] ?? true)) {
                continue;
            }

            // Role check
            $roles = $config['roles'] ?? ['*'];
            if ($roles !== ['*'] && !auth()->user()?->hasAnyRole($roles)) {
                continue;
            }

            $modules[$key] = array_merge($config, ['key' => $key]);
        }

        // Sort by order
        uasort($modules, fn($a, $b) => ($a['order'] ?? 999) <=> ($b['order'] ?? 999));

        // Fire event for listeners to modify modules
        event(new NavigationBuilding($modules));

        $this->modules = $modules;
        $this->activeModule = session('active_module', array_key_first($modules) ?? 'admin');
    }

    public function render()
    {
        return view('qf::livewire.layouts.navs.module-switcher');
    }
}