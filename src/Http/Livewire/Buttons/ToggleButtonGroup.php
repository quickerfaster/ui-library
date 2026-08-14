<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Buttons;

use Livewire\Attributes\On;
use QuickerFaster\UILibrary\Events\ToggleButtonEvent;
use Livewire\Component;
use Illuminate\Support\Facades\Log;
use QuickerFaster\UILibrary\Traits\Buttons\HandlesToggleState;

class ToggleButtonGroup extends Component
{
    use HandlesToggleState;

    public $groupId;
    public $buttons = [];
    public $buttonStates = [];
    public $parentState = 'off'; // off, on, mixed
    public $isExpanded = false;
    public $version = 0;



    protected $listeners = [
        'toggleSingleComponentStateChangedEvent' => 'toggleSingleComponentStateChanged',
    ];



    public function mount(
        $title = null,
        $subtitle = null,
        $componentId = null,
        $buttons = [],
        $groupId = null,
        $stateSyncMethod = 'database',
        $data = [],
        $version = 0
    ) {
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->componentId = $componentId;
        $this->buttons = $buttons;
        $this->groupId = $groupId;
        $this->stateSyncMethod = $stateSyncMethod;
        $this->data = $data;
        $this->version = $version;

        // Ensure children are properly initialized BEFORE computing
        // description, which now derives from $buttonStates.
        $this->initializeChildrenStates();

        $this->description = $this->getUpdatedDescription();

        // Derive the parent's state from the children's states
        $this->refreshParentState();
    }




    private function initializeChildrenStates()
    {
        $componentIds = array_column($this->buttons, 'componentId');
        $states = array_column($this->buttons, 'state');

        if (count($componentIds) !== count($states)) {
            throw new \Exception('Mismatch between children component IDs and their states.');
        }

        $this->buttonStates = array_combine($componentIds, $states);
    }



    public function toggleSingleComponentStateChanged($data) {
        if (isset($data["componentId"]) && isset($data["newState"]) && array_key_exists($data["componentId"], $this->buttonStates) ) {
            $this->buttonStates[$data["componentId"]] = $data["newState"];
            $this->refreshParentState();
            $this->description = $this->getUpdatedDescription();
            $this->dispatch('$refresh');
        }
    }



    #[On('refresh-toggle-state')]
    public function refreshState(array $permissions = [])
    {
        foreach ($this->buttonStates as $componentId => $state) {
            $this->buttonStates[$componentId] = in_array($componentId, $permissions, true);
        }

        $this->refreshParentState();
        $this->description = $this->getUpdatedDescription();
    }

    private function refreshParentState()
    {
        $allOn = count(array_filter($this->buttonStates)) === count($this->buttonStates);
        $allOff = count(array_filter($this->buttonStates)) === 0;

        if ($allOn) {
            $this->parentState = 'on';
        } elseif ($allOff) {
            $this->parentState = 'off';
        } else {
            $this->parentState = 'mixed';
        }
    }

    public function toggleAll()
    {
        if ($this->parentState == "off")
            $this->parentState = "on";
        else
            $this->parentState = "off";

        $newState = $this->parentState == "on"? 1 : 0; // Toggle all to 'on' if parent is not already 'on'

        foreach ($this->buttonStates as $childId => $currentState) {
            $this->buttonStates[$childId] = $newState;
            $this->dispatch( 'updateToggleButtonStateEvent', $newState, $childId);
        }


        ToggleButtonEvent::dispatch( [
            "buttonStates" => $this->buttonStates,
            "groupId" => $this->groupId,
            "theSameStateForAll" => true,
            "newState" => $newState,
            "stateSyncMethod" => $this->stateSyncMethod,
            "method" => $this->method,
            "data" => $this->data,
            "toggleAll" => true,
        ]);


        $this->description = $this->getUpdatedDescription();
        $this->dispatch('$refresh');

    }


    protected function getUpdatedDescription() {

        $resourceName = $this->data["resourceName"];
        $controlsCSSClasses = $this->data["controlsCSSClasses"];
        $description = "";

        if (!$controlsCSSClasses) {
            return $description;
        }

        foreach ($this->buttonStates as $componentId => $isOn) {
            if (!$isOn) {
                continue;
            }

            $searchPos = strpos($componentId, '_');
            if ($searchPos === false) {
                continue;
            }

            $control  = substr($componentId, 0, $searchPos);
            $resource = substr($componentId, $searchPos + 1);

            if (strtolower(\Str::snake($resourceName)) !== $resource) {
                continue;
            }

            if (!isset($controlsCSSClasses[$control]['bg'])) {
                continue;
            }

            $description .= "<span class='badge rounded-pill bg-gradient-"
                . $controlsCSSClasses[$control]['bg']
                . "' style='font-size: 0.7em; margin: 0em 0.2em;'>"
                . $control
                . "</span>";
        }

        return $description;
    }


















    public function toggleAccordion()
    {
        $this->isExpanded = !$this->isExpanded;
    }

    public function render()
    {
            return view('qf::livewire.buttons.toggle-button-group');
    }
}


