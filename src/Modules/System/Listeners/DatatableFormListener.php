<?php

namespace App\Modules\System\Listeners;

use App\Modules\System\Events\DataTableFormEvent;
use App\Modules\System\Models\Status;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use App\Modules\Production\Models\ProductionProcessLog;
use  App\Modules\Production\Events\ProductionProcessLogEvent;
use Illuminate\Contracts\Events\ShouldHandleEventsAfterCommit;

 class DatatableFormListener
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
    public function handle(DataTableFormEvent $event): void
    {
        $this->handleModelEvent($event);

    }

    protected function handleModelEvent($event) {

        if (!str_contains($event->model, "PayrollRun")
        ) {
            return;
        }

        if ($event->eventName == "AfterCreate" || $event->eventName == "AfterUpdate"
            || $event->eventName == "created" || $event->eventName == "updated"
        )  {
            $this->handleStatusChange($event);
        }
    }



    protected function handleStatusChange($event)
    {
        

        if (isset($event->newRecord) &&  isset($event->newRecord["status"])) {
            switch ($event->newRecord["status"]) {
                case "approved":
                   $this->handleApprovedStatus($event);
                case "deleted":
                   // Handle deleted record
                    $this->handleDeletedRecord($event);

            }

        }


    }


    protected function handleApprovedStatus($event)
    {
        // To be implemented in subclasses
    }


    protected function handleDeletedRecord($event)
    {
        // To be implemented in subclasses
    }







}
