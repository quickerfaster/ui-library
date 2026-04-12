<?php

namespace App\Modules\Hr\Listeners;

use App\Modules\System\Events\DataTableFormEvent;
use App\Modules\System\Listeners\DatatableFormListener;
use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Models\AttendanceAdjustment;
use App\Modules\Hr\Services\AttendanceAggregator;

use QuickerFaster\UILibrary\Services\GUI\SweetAlertService;
use QuickerFaster\UILibrary\Services\AccessControl\AccessControlPermissionService;


use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class AttendanceEventListener extends DatatableFormListener
{
    protected $attendanceAggregator;

    public function __construct(AttendanceAggregator $attendanceAggregator)
    {
        $this->attendanceAggregator = $attendanceAggregator;
    }

    public function handle(DataTableFormEvent $event): void
    {
        // Access the event data here
        $oldRecord = $event->oldRecord ?? null;
        $newRecord = $event->newRecord ?? null;
        $model = $event->model ?? null;
        $livewireComponent =  $oldRecord["component"] ?? null;


        if ($oldRecord && $model) {
            $requiredRole = $oldRecord['requiredRole'] ?? [];
            $params = $oldRecord['params'] ?? [];
            $attendance_id = $params['attendance_id'] ?? null;
            $actions = $params['actions'] ?? [];

            // Handle "Recalculate Hours" action
            if (in_array("recalculateAttendanceHours", $actions)) {
                // Check authorization - only hr_admin and system_admin can perform this action
                if (!$this->isAuthorized($requiredRole) || AccessControlPermissionService::checkPermission('edit', 'attendance')) {
                    Log::warning('Unauthorized attempt to recalculate attendance', [
                        'user_id' => Auth::id(),
                        'attendance_id' => $attendance_id
                    ]);
                    // throw new \Exception('You are not authorized to perform this action.');
                    SweetAlertService::showError($livewireComponent, "Error!", AccessControlPermissionService::MSG_PERMISSION_DENIED);

                }



                $this->handleRecalculation($attendance_id, $livewireComponent);
            }

            // You can add more actions here in the future
            // Example: if (isset($actions["anotherAction"])) { ... }
        }
    }

    /**
     * Handle attendance recalculation from clock events
     */
    private function handleRecalculation($attendanceId, $livewireComponent): void
    {
        DB::beginTransaction();

        try {
            $attendance = Attendance::with(['employee'])->findOrFail($attendanceId);

            // Check if attendance is already approved (from YAML condition)
            if ($attendance->is_approved) {
                // throw new \Exception('Cannot recalculate hours for approved attendance records.');
                SweetAlertService::showError($livewireComponent, "Error!", 'Cannot recalculate hours for approved attendance records.');

            }

            // Capture before state for audit
            $beforeState = [
                'net_hours' => $attendance->net_hours,
                'status' => $attendance->status,
                'calculation_method' => $attendance->calculation_method,
                'sessions_count' => $attendance->attendanceSessions()->count()
            ];

            Log::info('Starting attendance recalculation', [
                'attendance_id' => $attendanceId,
                'employee_id' => $attendance->employee_id,
                'date' => $attendance->date,
                'before_state' => $beforeState
            ]);

            // Get employee number and date
            $employeeNumber = $attendance->employee_number;
            $dateString = $attendance->date->toDateString();

            // Recalculate using the AttendanceAggregator service
            $this->attendanceAggregator->recalculateForDay($employeeNumber, $dateString);

            // Refresh to get updated values
            $attendance->refresh();

            // Capture after state
            $afterState = [
                'net_hours' => $attendance->net_hours,
                'status' => $attendance->status,
                'calculation_method' => $attendance->calculation_method,
                'sessions_count' => $attendance->attendanceSessions()->count()
            ];

            // Create detailed audit record
            // $this->createAuditRecord($attendance, $beforeState, $afterState);

            DB::commit();

            Log::info('Attendance recalculation completed successfully', [
                'attendance_id' => $attendanceId,
                'after_state' => $afterState,
                'changes_detected' => $beforeState != $afterState
            ]);

            SweetAlertService::showSuccess($livewireComponent, "Success!", 'Attendance recalculation completed successfully');

            // Dispatch success event for UI feedback
            $livewireComponent->dispatch('refreshDataTable');

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Attendance recalculation failed', [
                'attendance_id' => $attendanceId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            // Dispatch error event for UI feedback
            $this->dispatchErrorEvent($e->getMessage());

            // Re-throw to ensure the UI knows something went wrong
            throw $e;
        }
    }

    /**
     * Check if user is authorized to recalculate attendance
     */
    private function isAuthorized($requiredRoles): bool
    {

        $user = Auth::user();

        if (!$user) {
            return false;
        }

        // Check for required roles (hr_admin, system_admin)
        // This assumes you have a role system. Adjust based on your implementation.
        // $requiredRoles = ['hr_admin', 'system_admin'];

        // Method 1: If using Spatie Laravel Permission
        return $user->hasAnyRole($requiredRoles);

        // Method 2: If using simple role check
        // return in_array($user->role, $requiredRoles);

        // Method 3: If you have a different authorization system
        /*foreach ($requiredRoles as $role) {
            if ($user->hasRole($role)) {
                return true;
            }
        }

        return false;*/
    }

    /**
     * Create audit record for the recalculation
     */
    private function createAuditRecord(Attendance $attendance, array $before, array $after): void
    {
        // Determine if changes were detected
        $changesDetected = $before != $after;

        // Create meaningful reason based on changes
        $reason = $changesDetected
            ? 'System recalculation from clock events - values updated'
            : 'System recalculation from clock events - no changes needed';

        // If hours changed, add specific note
        if ($before['net_hours'] != $after['net_hours']) {
            $hoursDiff = $after['net_hours'] - $before['net_hours'];
            $reason .= sprintf(" (Hours changed by %+.2f)", $hoursDiff);
        }

        // Create the audit adjustment record
        AttendanceAdjustment::create([
            'attendance_id' => $attendance->id,
            'original_net_hours' => $before['net_hours'],
            'original_status' => $before['status'],
            'adjusted_net_hours' => $after['net_hours'],
            'adjusted_status' => $after['status'],
            'reason' => $reason,
            'adjustment_type' => 'system_recalculation',
            'adjusted_by' => Auth::user()->name . ' (System Recalc)',
            'adjusted_at' => now(),
            'requested_changes' => json_encode([
                'trigger' => 'recalculate_from_clock_events',
                'before' => $before,
                'after' => $after,
                'changes_detected' => $changesDetected,
                'recalculated_at' => now()->toIso8601String()
            ])
        ]);

        // Also update attendance notes with recalculation info
        $note = sprintf(
            "\n[%s] System recalculation by %s: %s -> %s hours, %s -> %s status",
            now()->format('Y-m-d H:i'),
            Auth::user()->name,
            $before['net_hours'],
            $after['net_hours'],
            $before['status'],
            $after['status']
        );

        $attendance->update([
            'notes' => ($attendance->notes ? $attendance->notes . $note : $note),
            'calculation_method' => 'auto_recalculated',
        ]);
    }

    /**
     * Dispatch success event for UI feedback
     */
    private function dispatchSuccessEvent(Attendance $attendance): void
    {
        // You can dispatch a custom event here if needed
        // Example:
        // event(new AttendanceRecalculated($attendance));

        // Or use Livewire events if in Livewire context
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\dispatch('attendance-recalculated', [
                'attendance_id' => $attendance->id,
                'message' => 'Hours recalculated successfully from clock events.',
                'new_hours' => $attendance->net_hours,
                'new_status' => $attendance->status
            ]);
        }
    }

    /**
     * Dispatch error event for UI feedback
     */
    private function dispatchErrorEvent(string $errorMessage): void
    {
        if (class_exists(\Livewire\Livewire::class)) {
            \Livewire\dispatch('attendance-recalculation-failed', [
                'message' => 'Recalculation failed: ' . $errorMessage
            ]);
        }
    }
}
