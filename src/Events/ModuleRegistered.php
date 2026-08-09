<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ModuleRegistered
{
    use Dispatchable;

    /**
     * @param string $name Module key (e.g., 'admin', 'hr')
     * @param string $path Absolute filesystem path to the module
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
    ) {}
}