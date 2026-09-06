<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Buttons;

use Livewire\Attributes\On;
use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use QuickerFaster\UILibrary\Events\ToggleButtonEvent;
use QuickerFaster\UILibrary\Traits\Buttons\HandlesToggleState;

class ToggleButton extends Component
{
    use HandlesToggleState;

    protected $listeners = [
        //'multipleComponentsStateChangedEvent' => 'multipleComponentsStateChanged'
        'updateToggleButtonStateEvent' => 'updateToggleButtonState'
    ];



    public function mount(
        $isCard = true,
        $title = null,
        $subtitle = null,
        $icon = null,
        $iconBg = 'primary',
        $iconColor = '',
        $hasCorners = true,
        $isOn = false,
        $componentId = null,
        $model = null,
        $column = null,
        $recordId = null,
        $onStateValue = 1,
        $offStateValue = 0,
        $stateSyncMethod = 'database',
        $method = null,
        $data = []
    ) {
        $this->isCard = $isCard;
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->icon = $icon;
        $this->iconBg = $iconBg;
        $this->iconColor = $iconColor;
        $this->hasCorners = $hasCorners;
        $this->isOn = $isOn;
        $this->componentId = $componentId;
        $this->model = $model;
        $this->column = $column;
        $this->recordId = $recordId;
        $this->onStateValue = $onStateValue;
        $this->offStateValue = $offStateValue;
        $this->stateSyncMethod = $stateSyncMethod;
        $this->method = $method;
        $this->data = $data;

        $this->syncStateFromDatabase();
    }


    public function updateToggleButtonState($newState, $buttonId)
    {
        if ($this->componentId === $buttonId) {
            $this->isOn = $newState;
            $this->saveState($newState);
        }
    }

    #[On('refresh-toggle-state')]
    public function refreshState(array $permissions = [])
    {
        if ($this->stateSyncMethod === 'method') {
            $this->isOn = in_array($this->componentId, $permissions, true);
        }
    }


    private function saveState($newState) {
        if ($this->stateSyncMethod == "database" && isset($this->model)) {
            $this->syncStateToDatabase($newState);
        }
    }

    private function retrieveState() {
        if ($this->stateSyncMethod == "database" && isset($this->model)) {
            $this->syncStateFromDatabase();
        } else if (isset($this->method)) {

        }
    }


    public function toggle()
    {
        $this->isOn = !$this->isOn;

        $this->saveState($this->isOn);

        $this->dispatch('toggleSingleComponentStateChangedEvent', $this->getToggleButtonData());
        ToggleButtonEvent::dispatch($this->getToggleButtonData());
    }


    protected function getToggleButtonData() {
        return [
            "newState" => $this->isOn,
            "stateSyncMethod" => $this->stateSyncMethod,
            "method" => $this->method,
            "data" => $this->data,
            "toggleAll" => false,
            "onStateValue" => $this->onStateValue,
            "componentId" => $this->componentId,
        ];
    }


    protected function syncStateToDatabase($newState)
    {
        if ($this->isValidModel()) {
            $record = $this->model::find($this->recordId);
            if ($record && $this->isValidColumn($record)) {
                $record->{$this->column} = $newState ? $this->onStateValue : $this->offStateValue;
                $record->save();
            }
        }

        $this->dispatch('$refresh');

    }


    protected function syncStateFromDatabase()
    {
        if ($this->isValidModel()) {
            $record = $this->model::find($this->recordId);
            if ($record && $this->isValidColumn($record)) {
                $this->isOn = $record->{$this->column} == $this->onStateValue;
            }
        }

        $this->dispatch('$refresh');

    }


    private function isValidModel()
    {
        return class_exists($this->model);
    }

    private function isValidColumn($record)
    {
        return Schema::hasColumn($record->getTable(), $this->column);
    }





    public function render()
    {


                    return view('qf::livewire.buttons.toggle-button');


    }
}





