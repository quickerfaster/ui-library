<?php

namespace QuickerFaster\UILibrary\Http\Livewire;

use Livewire\Component;

class Collapsible extends Component
{
    public string $collapsibleId;          // unique identifier (e.g., "row-{$recordId}")
    public bool $isOpen = false;
    public ?string $component = null;      // component to render when open
    public array $componentParams = [];    // parameters for that component
    public string $title = '';

    protected $listeners = [
        'openCollapsible' => 'open',
        'closeCollapsible' => 'close',
        'toggleCollapsible' => 'toggle',
    ];

    public function mount(string $collapsibleId): void
    {
        $this->collapsibleId = $collapsibleId;
    }

    /**
     * Open the collapsible and load a component.
     */

public function open($component, $params = [], $title = '', $target = null): void
    {

        if ($target && $target !== $this->collapsibleId) {
            return;
        }
        // Only react if the event targets this specific collapsible
        // We'll check via a target parameter sent with the event.
        $targetId = func_get_arg(3) ?? null;
        if ($targetId && $targetId !== $this->collapsibleId) {
            return;
        }

        $this->component = $component;
        $this->componentParams = $params;
        $this->title = $title ?: $this->extractTitle($component);
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->component = null;
        $this->componentParams = [];
        $this->title = '';
    }

    public function toggle($component = null, $params = [], $title = '', $target = null): void
    {
        
        if ($target && $target !== $this->collapsibleId) {
            return;
        }
        if ($this->isOpen) {
            $this->close();
        } else {
            if ($component) {
                $this->component = $component;
                $this->componentParams = $params;
                $this->title = $title ?: $this->extractTitle($component);
            }
            $this->isOpen = true;
        }
    }

    protected function extractTitle(string $component): string
    {
        return match ($component) {
            'qf.data-table-detail' => 'Record Details',
            'qf.data-table-form' => 'Edit Record',
            'qf.dashboard' => 'Dashboard',
            default => ucfirst(str_replace('_', ' ', class_basename($component))),
        };
    }

    public function render()
    {
        return view('qf::livewire.collapsible');
    }
}