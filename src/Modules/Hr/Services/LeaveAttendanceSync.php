<?php

namespace App\Modules\Hr\Services;

use App\Modules\Hr\Models\LeaveRequest;
use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Models\Employee;
use App\Modules\Hr\Models\Holiday;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class LeaveAttendanceSync
{
    private AttendanceAggregator $attendanceAggregator;
    
    public function __construct(AttendanceAggregator $attendanceAggregator)
    {
        $this->attendanceAggregator = $attendanceAggregator;
    }
    
    /**
     * Sync approved leave request to attendance records
     */
    public function syncLeaveToAttendance(LeaveRequest $leaveRequest): bool
    {
        // Only sync approved leave requests
        if ($leaveRequest->status !== 'Approved') {
            Log::warning("Attempted to sync non-approved leave request", [
                'leave_request_id' => $leaveRequest->id,
                'status' => $leaveRequest->status
            ]);
            return false;
        }
        
        // Prevent duplicate syncing
        if ($leaveRequest->attendance_synced) {
            Log::info("Leave request already synced to attendance", [
                'leave_request_id' => $leaveRequest->id
            ]);
            return true;
        }
        
        DB::transaction(function () use ($leaveRequest) {
            $employee = Employee::where('employee_number', $leaveRequest->employee_id)->first();
            if (!$employee) {
                throw new \Exception("Employee not found for leave request: {$leaveRequest->employee_id}");
            }
            
            $startDate = Carbon::parse($leaveRequest->start_date);
            $endDate = Carbon::parse($leaveRequest->end_date);
            $daysSynced = 0;
            $workdaysCount = 0;
            
            // Calculate workdays (excluding weekends)
            $period = CarbonPeriod::create($startDate, $endDate);
            
            foreach ($period as $date) {
                $dateString = $date->format('Y-m-d');
                
                // Skip weekends (configurable per company policy)
                if ($date->isWeekend()) {
                    continue;
                }
                
                // Skip company holidays
                if (Holiday::whereDate('date', $dateString)->exists()) {
                    Log::info("Skipping holiday during leave sync", [
                        'date' => $dateString,
                        'leave_request_id' => $leaveRequest->id
                    ]);
                    continue;
                }
                
                $workdaysCount++;
                
                // Check if attendance record already exists
                $existingAttendance = Attendance::where('employee_id', $employee->employee_number)
                    ->whereDate('date', $dateString)
                    ->first();
                
                $standardHours = $this->getStandardWorkHours($employee->employee_number, $date);
                
                if ($existingAttendance) {
                    // Update existing record
                    $this->updateExistingAttendanceForLeave($existingAttendance, $leaveRequest, $standardHours);
                } else {
                    // Create new leave attendance record
                    $this->createLeaveAttendanceRecord($employee->employee_number, $date, $leaveRequest, $standardHours);
                }
                
                $daysSynced++;
            }
            
            // Update leave request with sync status
            $leaveRequest->update([
                'attendance_synced' => true,
                'attendance_records_count' => $daysSynced,
                'workdays_count' => $workdaysCount,
                'last_sync_at' => now(),
                'overlap_with_holiday' => $this->checkHolidayOverlap($leaveRequest),
            ]);
            
            Log::info("Successfully synced leave to attendance", [
                'leave_request_id' => $leaveRequest->id,
                'employee_id' => $employee->employee_number,
                'days_synced' => $daysSynced,
                'workdays' => $workdaysCount,
                'date_range' => "{$startDate->format('Y-m-d')} to {$endDate->format('Y-m-d')}"
            ]);
        });
        
        return true;
    }
    
    /**
     * Remove leave attendance records when leave is cancelled/denied
     */
    public function removeLeaveAttendance(LeaveRequest $leaveRequest): bool
    {
        if (!$leaveRequest->attendance_synced) {
            return true; // Nothing to remove
        }
        
        DB::transaction(function () use ($leaveRequest) {
            // Find and update attendance records linked to this leave
            $attendanceRecords = Attendance::where('leave_request_id', $leaveRequest->id)
                ->where('status', 'leave')
                ->get();
            
            foreach ($attendanceRecords as $attendance) {
                // Instead of deleting, mark for recalculation
                $attendance->update([
                    'leave_request_id' => null,
                    'status' => 'pending',
                    'notes' => 'Leave request cancelled',
                    'is_approved' => false,
                    'needs_review' => true,
                ]);
                
                // Trigger recalculation for this day
                $this->attendanceAggregator->recalculateForDay(
                    $attendance->employee_id,
                    $attendance->date->format('Y-m-d')
                );
            }
            
            // Reset sync status on leave request
            $leaveRequest->update([
                'attendance_synced' => false,
                'attendance_records_count' => 0,
                'last_sync_at' => null,
            ]);
            
            Log::info("Removed leave attendance linkage", [
                'leave_request_id' => $leaveRequest->id,
                'attendance_records_updated' => $attendanceRecords->count()
            ]);
        });
        
        return true;
    }
    
    /**
     * Sync all pending leave requests (for batch processing)
     */
    public function syncPendingLeaves(): array
    {
        $results = [
            'total' => 0,
            'synced' => 0,
            'failed' => 0,
            'details' => []
        ];
        
        $pendingLeaves = LeaveRequest::where('status', 'Approved')
            ->where('attendance_synced', false)
            ->where('start_date', '<=', now()->addDays(30)) // Only sync leaves starting soon
            ->get();
        
        $results['total'] = $pendingLeaves->count();
        
        foreach ($pendingLeaves as $leave) {
            try {
                $success = $this->syncLeaveToAttendance($leave);
                
                if ($success) {
                    $results['synced']++;
                    $results['details'][] = [
                        'leave_request_id' => $leave->id,
                        'employee_id' => $leave->employee_id,
                        'status' => 'synced'
                    ];
                } else {
                    $results['failed']++;
                    $results['details'][] = [
                        'leave_request_id' => $leave->id,
                        'employee_id' => $leave->employee_id,
                        'status' => 'failed',
                        'error' => 'Sync returned false'
                    ];
                }
            } catch (\Exception $e) {
                $results['failed']++;
                $results['details'][] = [
                    'leave_request_id' => $leave->id,
                    'employee_id' => $leave->employee_id,
                    'status' => 'error',
                    'error' => $e->getMessage()
                ];
                
                Log::error("Failed to sync leave to attendance", [
                    'leave_request_id' => $leave->id,
                    'error' => $e->getMessage(),
                    'trace' => $e->getTraceAsString()
                ]);
            }
        }
        
        Log::info("Batch leave sync completed", $results);
        
        return $results;
    }
    
    /**
     * Check if employee has overlapping approved leaves
     */
    public function checkLeaveOverlap(string $employeeNumber, Carbon $startDate, Carbon $endDate, ?int $excludeLeaveId = null): bool
    {
        $query = LeaveRequest::where('employee_id', $employeeNumber)
            ->where('status', 'Approved')
            ->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('start_date', [$startDate, $endDate])
                  ->orWhereBetween('end_date', [$startDate, $endDate])
                  ->orWhere(function ($q2) use ($startDate, $endDate) {
                      $q2->where('start_date', '<=', $startDate)
                         ->where('end_date', '>=', $endDate);
                  });
            });
        
        if ($excludeLeaveId) {
            $query->where('id', '!=', $excludeLeaveId);
        }
        
        return $query->exists();
    }
    
    /**
     * Get employee's standard work hours for a date
     */
    private function getStandardWorkHours(string $employeeNumber, Carbon $date): float
    {
        // TODO: Implement based on shift schedule
        // For now, return default 8 hours
        return 8.00;
    }
    
    /**
     * Update existing attendance record for leave
     */
    private function updateExistingAttendanceForLeave(Attendance $attendance, LeaveRequest $leaveRequest, float $standardHours): void
    {
        $attendance->update([
            'status' => 'leave',
            'leave_request_id' => $leaveRequest->id,
            'net_hours' => $standardHours,
            'is_approved' => true,
            'notes' => "On Leave: {$leaveRequest->leaveType->name}" . ($attendance->notes ? " | Previous: {$attendance->notes}" : ''),
            'needs_review' => false,
            'is_unplanned' => false,
            'absence_type' => 'planned_leave',
            'hours_deducted' => $leaveRequest->leaveType->deducts_from_balance ? $standardHours : 0,
            'is_paid_absence' => $leaveRequest->leaveType->is_paid ?? true,
        ]);
    }
    
    /**
     * Create new attendance record for leave
     */
    private function createLeaveAttendanceRecord(string $employeeNumber, Carbon $date, LeaveRequest $leaveRequest, float $standardHours): void
    {
        Attendance::create([
            'employee_id' => $employeeNumber,
            'date' => $date->format('Y-m-d H:i:s'),
            'status' => 'leave',
            'leave_request_id' => $leaveRequest->id,
            'net_hours' => $standardHours,
            'sessions' => null,
            'is_approved' => true,
            'approved_by' => $leaveRequest->approved_by,
            'approved_at' => $leaveRequest->approved_at,
            'notes' => "On Leave: {$leaveRequest->leaveType->name}",
            'needs_review' => false,
            'is_unplanned' => false,
            'absence_type' => 'planned_leave',
            'hours_deducted' => $leaveRequest->leaveType->deducts_from_balance ? $standardHours : 0,
            'is_paid_absence' => $leaveRequest->leaveType->is_paid ?? true,
        ]);
    }
    
    /**
     * Check if leave overlaps with company holidays
     */
    private function checkHolidayOverlap(LeaveRequest $leaveRequest): bool
    {
        $startDate = Carbon::parse($leaveRequest->start_date);
        $endDate = Carbon::parse($leaveRequest->end_date);
        
        $holidaysInRange = Holiday::whereDate('date', '>=', $startDate)
            ->whereDate('date', '<=', $endDate)
            ->count();
        
        return $holidaysInRange > 0;
    }
}