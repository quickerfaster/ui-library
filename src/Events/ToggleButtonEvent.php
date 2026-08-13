<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * ToggleButtonEvent stub for the UI Library.
 *
 * This replaces the App\Modules\Admin\Events\ToggleButtonEvent reference
 * that was previously coupled to the QuickHR application.
 *
 * Consuming applications should listen for this event to perform
 * application-specific actions when toggle buttons change state.
 */
class ToggleButtonEvent
{
    use Dispatchable, SerializesModels;

    /**
     * @param string $componentId The toggle button component ID
     * @param bool $newState The new toggle state
     * @param array $data Additional context data
     */
    public function __construct(
        public string $componentId,
        public bool $newState,
        public array $data = []
    ) {}
}