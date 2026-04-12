<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

class Sidebar extends Component
{
    public array $items = [];
    public string $state = 'full';
    public array $headerItems = [];
    public array $footerItems = [];
    public ?string $currentModelName = null;   

    public bool $allowTypeSwitch = false;

    public function mount(array $items, string $state = 'full', array $headerItems = [], array $footerItems = [], 
        bool $allowTypeSwitch = false,
        ?string $currentModelName = null
        
        )
    {
        $this->items = $items;
        $this->state = $state;
        $this->headerItems = $headerItems;
        $this->footerItems = $footerItems;
        $this->allowTypeSwitch = $allowTypeSwitch;
        $this->currentModelName = $currentModelName;  

    }

public function toggleState(): void
{
    $states = ['full', 'icon'];
    $currentIndex = array_search($this->state, $states);
    $nextIndex = ($currentIndex + 1) % count($states);
    $this->state = $states[$nextIndex];
    
    // Persist to session and localStorage
    session(['sidebar_state' => $this->state]);
    $this->dispatch('saveSidebarState', $this->state);
    // $this->dispatch('sidebarStateChanged', $this->state);
}

public function switchToHorizontal(): void
{
    session(['context_menu_type' => 'horizontal']);
    $this->dispatch('doReload');
}

    public function render()
    {
        return view('qf::livewire.navs.sidebar');
    }
}