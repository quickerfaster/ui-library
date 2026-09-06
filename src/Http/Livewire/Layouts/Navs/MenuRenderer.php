<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Layouts\Navs;

use Livewire\Component;

class MenuRenderer extends Component
{
    public string $menuType;
    public int $counter = 0;

    public string $moduleName;
    public ?string $activeContext = null;
    public array $contextItems;
    public string $contextMenuPosition;
    public bool $allowMenuTypeSwitch;
    public string $sidebarState;
    public array $sharedHeaderItems;
    public array $sharedFooterItems;

    /** @var string|null */
    public $currentModelName = null;

    /** @var string|null */
    public $settingsContext = null;

    protected $listeners = ['menuTypeSwitched' => 'switchMenuType'];

    public function mount(
        string $moduleName,
        ?string $activeContext = null,
        array $contextItems,
        string $contextMenuPosition,
        bool $allowMenuTypeSwitch,
        string $sidebarState,
        array $sharedHeaderItems,
        array $sharedFooterItems,
        ?string $currentModelName = null,
        ?string $settingsContext = null
    ) {
        $this->moduleName = $moduleName;
        $this->activeContext = $activeContext;
        $this->contextItems = $contextItems;
        $this->contextMenuPosition = $contextMenuPosition;
        $this->allowMenuTypeSwitch = $allowMenuTypeSwitch;
        $this->sidebarState = $sidebarState;
        $this->sharedHeaderItems = $sharedHeaderItems;
        $this->sharedFooterItems = $sharedFooterItems;
        $this->currentModelName = $currentModelName;
        $this->settingsContext = $settingsContext;

        // Read from session (set by switch buttons)
        $this->menuType = session('context_menu_type', 'sidebar');
    }

    public function switchMenuType($type)
    {
        if (!in_array($type, ['sidebar', 'horizontal'])) {
            return;
        }
        $this->menuType = $type;
        $this->counter++;   // <-- forces child component to re‑mount
        session(['context_menu_type' => $type]);
        $this->dispatch('saveMenuType', $type);
        $this->dispatch('menu-type-changed', $type); // for Alpine layout toggle
    }

    public function render()
    {
        return view('qf::livewire.navs.menu-renderer');
    }
}