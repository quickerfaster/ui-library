<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

class HorizontalContextMenu extends Component
{
    public array $items = [];
    public string $position = 'left';
    public bool $allowTypeSwitch = false;
    public ?string $currentModelName = null;   // <-- add this

    public function mount(
        array $items,
        string $position = 'left',
        bool $allowTypeSwitch = false,
        ?string $currentModelName = null       // <-- add this
    ) {
        $this->items = $items;
        $this->position = $position;
        $this->allowTypeSwitch = $allowTypeSwitch;
        $this->currentModelName = $currentModelName; // <-- set it
    }

    public function switchToSidebar(): void
    {
        session(['context_menu_type' => 'sidebar']);
        $this->dispatch('doReload');
    }

    public function render()
    {
        return view('qf::livewire.navs.horizontal-context-menu');
    }
}