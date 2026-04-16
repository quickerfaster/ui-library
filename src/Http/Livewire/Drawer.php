<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;
use QuickerFaster\UILibrary\Services\Config\ConfigResolver;

class Drawer extends Component
{
    public bool $isOpen = false;
    public ?string $currentDrawerKey = null;
    public array $drawerConfig = [];
    public ?string $configKey = null;

protected $listeners = [
    'openDrawer' => 'open',
    'closeDrawer' => 'close',
];
    /**
     * Open a drawer by its configuration key.
     */
    public function open(string $drawerKey, string $configKey, array $additionalParams = []): void
    {
        $this->configKey = $configKey;

        $resolver = app(ConfigResolver::class, ['configKey' => $this->configKey]);
        $drawers = $resolver->getConfig()['drawers'] ?? [];
        $config = $drawers[$drawerKey] ?? null;

        if (!$config) {
            \Log::error("Drawer config not found: {$drawerKey} for configKey {$this->configKey}");
            return;
        }

        $this->currentDrawerKey = $drawerKey;
        $this->drawerConfig = $config;

        // Merge additional params and replace placeholders
        $params = $config['params'] ?? [];
        foreach ($additionalParams as $key => $value) {
            $params[$key] = $value;
        }
        array_walk_recursive($params, function (&$item) {
            if (is_string($item) && str_contains($item, '{configKey}')) {
                $item = str_replace('{configKey}', $this->configKey, $item);
            }
        });
        $this->drawerConfig['params'] = $params;

        $this->isOpen = true;

        // Emit an event to tell JavaScript to show the offcanvas
        $this->dispatch('drawerOpened');
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->currentDrawerKey = null;
        $this->drawerConfig = [];
        // No need to emit anything; the offcanvas hidden event will call this
    }

    public function render()
    {
        return view('qf::livewire.drawer');
    }
}