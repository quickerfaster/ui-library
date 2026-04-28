<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;

class Drawer extends Component
{
    public bool $isOpen = false;
    public ?string $component = null;
    public array $componentParams = [];
    public string $title = '';

    protected $listeners = [
        'openDrawer' => 'open',
        'closeDrawer' => 'close',
        'formSaved' => 'close',          // optional: auto‑close after save
    ];

    /**
     * Open the drawer with a specific Livewire component.
     *
     * @param string $component  e.g. 'qf.data-table-form', 'qf.dashboard', 'custom-component'
     * @param array  $params     Parameters for the component (configKey, recordId, inline, etc.)
     * @param string $title      Drawer header title (optional)
     */

    
    public function open(string $component, array $params = [], string $title = ''): void
    {
        if ($this->isOpen && $this->component === $component) {
            // Already open with same component – just refresh?
            return;
        }


        $this->component = $component;
        $this->componentParams = $params;
        $this->title = $title ?: $this->extractTitleFromComponent($component);
        $this->isOpen = true;

        $this->dispatch('drawerOpened');  // triggers JS to show offcanvas
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->component = null;
        $this->componentParams = [];
        $this->title = '';
        $this->dispatch('drawerClosed');
    }

    /**
     * Fallback title generation (can be overridden by passed title).
     */
    protected function extractTitleFromComponent(string $component): string
    {
        // Simple mapping for known components
        return match ($component) {
            'qf.data-table-form' => 'Add / Edit Record',
            'qf.data-table-detail' => 'Record Details',
            'qf.dashboard' => 'Dashboard',
            default => ucfirst(str_replace('_', ' ', class_basename($component))),
        };
    }

    public function render()
    {
        return view('qf::livewire.drawer');
    }
}