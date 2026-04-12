<?php
namespace QuickerFaster\UILibrary\Traits\Buttons;



trait HandlesToggleState
{
    public $isOn = false;
    public $onStateValue = 1;
    public $offStateValue = 0;



    public $title = null;
    public $subtitle = null;
    public $description = null;
    public $icon = null;
    public $iconBg = "primary";
    public $iconColor = "";

    public $isCard = true; // Flag to control card container
    public $showLabel = false; // Flag to show/hide labels for standalone


    public $onStateColor = 'success';
    public $offStateColor = 'light';
    public $mixedStateColor = 'secondary';



    public $model;
    public $column;
    public $recordId;
    public $componentId;
    public $labelPosition = 'right';




    public $hasCorners = true;
    public $stateSyncMethod = "database";
    public $method = null;
    public $data = [];







    /*public function initializeToggleState($isOn = false, $onValue = 1, $offValue = 0)
    {
        $this->isOn = $isOn;
        $this->onStateValue = $onValue;
        $this->offStateValue = $offValue;
    }*/

    public function toggleState()
    {
        $this->isOn = !$this->isOn;
        return $this->isOn ? $this->onStateValue : $this->offStateValue;
    }

    public function isMixedState($childrenStates)
    {
        $onCount = count(array_filter($childrenStates, fn($state) => boolval($state) === true));
        $offCount = count(array_filter($childrenStates, fn($state) => boolval($state) === false));

        if ($onCount === count($childrenStates)) {
            return 'on';
        } elseif ($offCount === count($childrenStates)) {
            return 'off';
        } else {
            return 'mixed';
        }
    }
}
