<?php

namespace QuickerFaster\UILibrary\Http\Livewire\Buttons;

use Livewire\Component;
use Illuminate\Support\Facades\Schema;
use App\Modules\Admin\Events\ToggleButtonEvent;
use QuickerFaster\UILibrary\Traits\Buttons\HandlesToggleState;

class ToggleButton extends Component
{
    use HandlesToggleState;

    protected $listeners = [
        //'multipleComponentsStateChangedEvent' => 'multipleComponentsStateChanged'
        'updateToggleButtonStateEvent' => 'updateToggleButtonState'
    ];



    public function mount()
    {
        $this->syncStateFromDatabase();
    }


    public function updateToggleButtonState($newState, $buttonId)
    {
        if ($this->componentId === $buttonId) {
            $this->isOn = $newState;
            $this->saveState($newState);
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





