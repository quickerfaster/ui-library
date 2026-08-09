<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class NavigationBuilding
{
    use Dispatchable;

    /**
     * @param array $modules The modules array that will be rendered.
     * Listeners can modify this array to add/remove/reorder modules.
     */
    public function __construct(
        public array $modules,
    ) {}
}