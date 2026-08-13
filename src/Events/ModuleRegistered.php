<?php

namespace QuickerFaster\UILibrary\Events;

use Illuminate\Foundation\Events\Dispatchable;

class ModuleRegistered
{
    use Dispatchable;

    /**
     * @param string $name Module key (e.g., 'admin', 'hr')
     * @param string $path Absolute filesystem path to the module
     * @param bool $userFacing Whether the module appears in user-facing navigation
     * @param array $dependsOn Module keys this module depends on
     */
    public function __construct(
        public readonly string $name,
        public readonly string $path,
        public readonly bool $userFacing = true,
        public readonly array $dependsOn = [],
    ) {}
}