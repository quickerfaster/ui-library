<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

/**
 * ToggleButtonEvent for the UI Library.
 *
 * This replaces the App\Modules\Admin\Events\ToggleButtonEvent reference
 * that was previously coupled to the QuickHR application.
 *
 * The event payload is a single array containing the full toggle state,
 * matching the data dispatched by ToggleButton and ToggleButtonGroup.
 */
class ToggleButtonEvent
{
    use Dispatchable;

    /**
     * @var array The toggle button state payload.
     */
    public $data;

    /**
     * @param array $data The toggle button state payload.
     */
    public function __construct($data)
    {
        $this->data = $data;
    }
}