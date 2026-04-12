<?php

namespace App\Modules\Hr\Listeners;

use App\Modules\System\Events\DataTableFormEvent;
use App\Modules\System\Listeners\DatatableFormListener;

use  App\Modules\Hr\Http\Controllers\PayrollRunController;
use App\Modules\Hr\Models\PayrollRun;
use App\Modules\Hr\Services\LeaveAttendanceSync;
use App\Modules\Hr\Models\LeaveRequest;
use App\Modules\Hr\Services\AttendanceAggregator;

class LeaveRequestEventListener extends DatatableFormListener
{


    protected function handleApprovedStatus($event)
    {
        // Specific handling for LeaveRequest approval

        if (isset($event->newRecord) && isset($event->newRecord["id"])) {
            $leaveRequestId = $event->newRecord["id"];
            $leaveRequest = LeaveRequest::find($leaveRequestId);
            if ($leaveRequest) {
                $this->approveLeave($leaveRequest, $event);
            } else {
                \Log::warning('LeaveRequestEventListener: LeaveRequest not found', [
                    'leaveRequestId' => $leaveRequestId
                ]);
            }

        } else {
            \Log::warning('LeaveRequestEventListener: Approved status but no ID found in newRecord', [
                'newRecord' => $event->newRecord ?? null
            ]);
        }
        
                   
    }





    public function approveLeave(LeaveRequest $leaveRequest, $event)
    {


        $originalStatus = $event->oldRecord["status"] ?? null;
        $newStatus = $leaveRequest->status;


        // NEW: Sync to attendance
        $syncService = new LeaveAttendanceSync(new AttendanceAggregator());
        $syncService->syncLeaveToAttendance($leaveRequest);

        
        // Handle approval: Sync to attendance
        if ($originalStatus !== 'Approved' && $newStatus === 'Approved') {
            $syncService->syncLeaveToAttendance($leaveRequest);
        }
        
        // Handle cancellation/denial: Remove from attendance
        if ($originalStatus === 'Approved' && in_array($newStatus, ['Denied', 'Cancelled'])) {
            $syncService->removeLeaveAttendance($leaveRequest);
        }
        
        // Handle re-approval after changes
        if ($originalStatus === 'Approved' && $newStatus === 'Approved') {
            // If dates changed, re-sync
            if ($leaveRequest->isDirty(['start_date', 'end_date'])) {
                // First remove old sync
                $syncService->removeLeaveAttendance($leaveRequest);
                // Then re-sync with new dates
                $syncService->syncLeaveToAttendance($leaveRequest);
            }
        }

        
        // Send notifications, etc.
    }



    protected function handleDeletedRecord($event) {
        // Specific handling for LeaveRequest deletion
        /*$syncService = new LeaveAttendanceSync(new AttendanceAggregator());
        
        if (isset($event->oldRecord) && isset($event->oldRecord["status"]) && $event->oldRecord["status"] === 'Approved') {
            
            if ($event->oldRecord) {
                $syncService->removeLeaveAttendance($event->oldRecord);
            } else {
                \Log::warning('LeaveRequestEventListener: Deleted record not found in oldRecord', [
                    'oldRecord' => $event->oldRecord ?? null
                ]);
            }

        }*/
            
    }



}



