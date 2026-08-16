<?php

namespace QuickerFaster\UILibrary\Services\Navigation;

class WorkspaceFilter
{
    /**
     * The resolved workspace context.
     *
     * @var array
     */
    protected array $workspace;

    /**
     * Create a new workspace filter instance.
     *
     * @param array $workspace  Resolved workspace context from WorkspaceResolver
     */
    public function __construct(array $workspace)
    {
        $this->workspace = $workspace;
    }

    /**
     * Filter context groups by feature gates.
     *
     * If a group has a `feature` key, it is only kept when that feature
     * is present in `$this->workspace['features']`. Groups with no `feature`
     * key are always kept.
     *
     * @param  array $groups  Context group definitions keyed by group slug
     * @return array          Filtered groups
     */
    public function filterContextGroups(array $groups): array
    {
        if (empty($this->workspace)) {
            return $groups;
        }

        $enabledFeatures = $this->workspace['features'] ?? [];

        return array_filter($groups, function ($group) use ($enabledFeatures) {
            // Groups without a feature gate are always shown
            if (!isset($group['feature'])) {
                return true;
            }

            // If there are no enabled features at all, exclude gated groups
            if (empty($enabledFeatures)) {
                return false;
            }

            return in_array($group['feature'], $enabledFeatures, true);
        });
    }

    /**
     * Filter context items by workspace constraints.
     *
     * If an item has a `workspace` key with constraint key-value pairs
     * (e.g., `['role' => 'finance_admin', 'department_type' => 'engineering']`),
     * the item is only kept when ALL constraints match the current workspace
     * context. Items with no `workspace` key are always kept.
     *
     * @param  array $items  Navigation item definitions
     * @return array         Filtered items
     */
    public function filterContextItems(array $items): array
    {
        if (empty($this->workspace)) {
            return $items;
        }

        return array_filter($items, function ($item) {
            // Items without workspace constraints are always shown
            if (!isset($item['workspace']) || !is_array($item['workspace'])) {
                return true;
            }

            foreach ($item['workspace'] as $constraintKey => $constraintValue) {
                // If the workspace context doesn't have this key, the constraint
                // cannot be satisfied — exclude the item
                if (!array_key_exists($constraintKey, $this->workspace)) {
                    return false;
                }

                // If the value doesn't match, exclude the item
                if ($this->workspace[$constraintKey] !== $constraintValue) {
                    return false;
                }
            }

            // All constraints matched
            return true;
        });
    }

    /**
     * Get the underlying workspace context array.
     *
     * @return array
     */
    public function getWorkspace(): array
    {
        return $this->workspace;
    }
}