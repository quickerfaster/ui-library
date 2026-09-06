<?php

namespace QuickerFaster\UILibrary\Services\Navigation;

use QuickerFaster\UILibrary\Contracts\Navigation\WorkspaceResolver;

class NullWorkspaceResolver implements WorkspaceResolver
{
    /**
     * Returns an empty context — no workspace constraints applied.
     *
     * @return array
     */
    public function resolve(): array
    {
        return [];
    }

    /**
     * All features are considered enabled when no workspace is set.
     *
     * @param  string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool
    {
        return true;
    }
}