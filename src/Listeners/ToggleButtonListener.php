<?php

namespace QuickerFaster\UILibrary\Listeners;

use QuickerFaster\UILibrary\Events\ToggleButtonEvent;

/**
 * Handles ToggleButtonEvent payloads for access control permission toggles.
 *
 * Mirrors the original QuickHR ToggleButtonListener: when a toggle button
 * uses stateSyncMethod "method" (rather than directly writing to a model
 * column), this listener syncs the granted permissions on the selected
 * scope (a Role or User) via Spatie's syncPermissions().
 */
class ToggleButtonListener
{
    public function handle(ToggleButtonEvent $event)
    {
        $buttonData = $event->data ?? [];

        if (($buttonData['stateSyncMethod'] ?? null) !== 'method') {
            return;
        }

        $this->updateToggleButtonGroupState($buttonData);
    }

    protected function updateToggleButtonGroupState(array $buttonData)
    {
        $selectedScope = $buttonData['data']['selectedScope'] ?? null;

        if (!$selectedScope) {
            return;
        }

        $selectedScopePermissions = $selectedScope->getPermissionNames()->toArray();

        if (!empty($buttonData['toggleAll'])) {
            $this->applyToggleAll($selectedScope, $buttonData, $selectedScopePermissions);
            return;
        }

        $permission = $buttonData['onStateValue'] ?? null;

        if (!$permission) {
            return;
        }

        if (!empty($buttonData['newState'])) {
            $permissions = array_unique(array_merge([$permission], $selectedScopePermissions));
        } else {
            $permissions = array_values(array_diff($selectedScopePermissions, [$permission]));
        }

        $selectedScope->syncPermissions($permissions);
    }

    protected function applyToggleAll($selectedScope, array $buttonData, array $selectedScopePermissions)
    {
        $permissions = array_keys($buttonData['buttonStates'] ?? []);
        $sameStateForAll = !empty($buttonData['theSameStateForAll']);
        $newState = !empty($buttonData['newState']);

        if ($sameStateForAll && $newState) {
            $permissions = array_unique(array_merge($selectedScopePermissions, $permissions));
        } elseif ($sameStateForAll && !$newState) {
            $permissions = array_values(array_diff($selectedScopePermissions, $permissions));
        } else {
            foreach ($buttonData['buttonStates'] ?? [] as $permission => $permissionState) {
                if ($permissionState) {
                    $selectedScopePermissions = array_unique(array_merge([$permission], $selectedScopePermissions));
                } else {
                    $selectedScopePermissions = array_values(array_diff($selectedScopePermissions, [$permission]));
                }
            }
            $permissions = $selectedScopePermissions;
        }

        $selectedScope->syncPermissions($permissions);
    }
}
