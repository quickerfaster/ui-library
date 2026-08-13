<?php

namespace QuickerFaster\UILibrary\Contracts\Navigation;

interface WorkspaceResolver
{
    /**
     * Get the current workspace context map.
     *
     * Returns key-value pairs representing the current workspace context,
     * for example:
     *   ['company_id' => 1, 'role' => 'payroll_admin', 'department_type' => 'engineering', 'features' => ['departments', 'time']]
     *
     * Navigation items can define `workspace` constraints that are matched
     * against this map. Context groups can define `feature` gates that are
     * checked against the `features` array.
     *
     * @return array
     */
    public function resolve(): array;

    /**
     * Check if a specific feature is enabled in the current workspace.
     *
     * @param  string $feature
     * @return bool
     */
    public function hasFeature(string $feature): bool;
}