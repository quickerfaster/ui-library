<?php

namespace App\Modules\Admin\Listeners;

use App\Modules\System\Models\Status;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Modules\System\Events\DataTableFormEvent;

use App\Modules\Admin\Events\ToggleButtonEvent;
use App\Modules\Production\Models\ProductionProcessLog;
use App\Modules\Production\Events\ProductionProcessLogEvent;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

class ToggleButtonListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ToggleButtonEvent $event)
    {

        // To be handled by a method not by DTABASE!
        if (isset($event->data["stateSyncMethod"]) && $event->data["stateSyncMethod"] == "method") {
            $this->updateToggleButtonGroupState($event->data);
        }
    }





    protected function updateToggleButtonGroupState($buttonData)
    {


        if (!isset($buttonData["stateSyncMethod"]) || $buttonData["stateSyncMethod"] != "method")
            return;

        if (!isset($buttonData["data"]) || !isset($buttonData["data"]["selectedScope"]))
            return;


        $selectedScope = $buttonData["data"]["selectedScope"];
        $selectedScopePermissions = $selectedScope?->getPermissionNames()->toArray();


        if ($buttonData["toggleAll"]) { // Handle toggleAll Button
            if (isset($buttonData["buttonStates"]) && isset($buttonData['theSameStateForAll']) && isset($buttonData['newState'])) {
                $permissions = array_keys($buttonData["buttonStates"]);

                if ($buttonData['theSameStateForAll'] && $buttonData['newState']) {
                    $permissions = array_unique(array_merge($selectedScopePermissions, $permissions));
                } else if ($buttonData['theSameStateForAll'] && !$buttonData['newState']) {
                    $permissions = array_diff($selectedScopePermissions,$permissions);

                } else if (!$buttonData['theSameStateForAll']) {
                    foreach ($buttonData["buttonStates"] as $permission => $newState) {
                        if ($newState === true) {
                            //$scope->givePermissionTo($permission);
                            $permissions = array_unique(array_merge([$permission], $selectedScopePermissions));
                        } else {
                            //$scope->revokePermissionTo($permission);
                            $permissions = array_diff($selectedScopePermissions, [$permission]);
                        }
                    }
                }

            }


        } else {  // Handle single Button toggle

            $permission = $buttonData["onStateValue"];
            if ($buttonData["newState"] == true) {
                //$scope->givePermissionTo($permission);
                $permissions = array_unique(array_merge([$permission], $selectedScopePermissions));
            } else {
                //$scope->revokePermissionTo($permission);
                $permissions = array_diff($selectedScopePermissions, [$permission]);
            }

        }


        $selectedScope->syncPermissions($permissions);

    }











}
