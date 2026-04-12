<?php

namespace App\Modules\Hr\Commands;

use Illuminate\Console\Command;
use App\Modules\Hr\Services\LeaveAttendanceSync;

class SyncLeaveAttendance extends Command
{
    protected $signature = 'hr:sync-leave-attendance 
                            {--employee= : Sync for specific employee}
                            {--date= : Sync for specific date (YYYY-MM-DD)}
                            {--all-pending : Sync all pending leaves}
                            {--force : Force re-sync even if already synced}';
    
    protected $description = 'Synchronize approved leave requests with attendance records';
    
    public function handle(LeaveAttendanceSync $syncService)
    {
        $this->info('Starting leave-attendance synchronization...');
        
        if ($this->option('all-pending')) {
            $results = $syncService->syncPendingLeaves();
            
            $this->table(
                ['Metric', 'Count'],
                [
                    ['Total Leaves', $results['total']],
                    ['Successfully Synced', $results['synced']],
                    ['Failed', $results['failed']]
                ]
            );
            
            if ($results['failed'] > 0) {
                $this->error('Some leaves failed to sync. Check logs for details.');
            }
        }
        
        $this->info('Synchronization completed.');
        
        return 0;
    }
}