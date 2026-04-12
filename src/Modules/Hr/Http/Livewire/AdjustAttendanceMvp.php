<?php

namespace App\Modules\Hr\Http\Livewire;

use Livewire\Component;
use App\Modules\Hr\Models\Attendance;
use App\Modules\Hr\Models\AttendanceAdjustment;
use Illuminate\Support\Facades\DB;

class AdjustAttendanceMvp extends Component
{
    public $attendanceId;
    public $attendance;

    // Form fields
    public $original_net_hours;
    public $original_status;
    public $adjusted_net_hours;
    public $adjusted_status;
    public $reason;

    // Validation rules
    protected $rules = [
        'adjusted_net_hours' => 'required|numeric|min:0|max:24',
        'adjusted_status' => 'required|in:present,absent,late,half_day,holiday,leave',
        'reason' => 'required|string|max:500',
    ];

    // Livewire lifecycle - load the attendance record
    public function mount($attendanceId)
    {


        $this->attendanceId = $attendanceId;
        $this->attendance = Attendance::with(['employee', 'adjustments'])
            ->find($attendanceId);

        if (!$this->attendance)
             abort(404, 'Attendance Adjustments Not found');

        // Authorization check (optional but recommended)
        // $this->authorize('update', $this->attendance);

        // Set current values
        $this->original_net_hours = $this->attendance->net_hours;
        $this->original_status = $this->attendance->status;
        $this->adjusted_net_hours = $this->attendance->net_hours;
        $this->adjusted_status = $this->attendance->status;
    }

    // Main save method
    public function save()
    {
        // Validate the form
        $this->validate();

        // Check if anything actually changed
        if (!$this->hasChanges()) {
            $this->addError('adjusted_net_hours', 'No changes detected. Please adjust the values before saving.');
            return;
        }

        // Use transaction for data consistency
        DB::beginTransaction();

        try {
            // 1. Create audit trail record
            $adjustment = AttendanceAdjustment::create([
                'attendance_id' => $this->attendanceId,
                'original_net_hours' => $this->original_net_hours,
                'original_status' => $this->original_status,
                'adjusted_net_hours' => $this->adjusted_net_hours,
                'adjusted_status' => $this->adjusted_status,
                'reason' => $this->reason,
                'adjusted_by' => auth()->user()->name,
                'adjusted_at' => now(),
            ]);

            // 2. Update the actual attendance record
            $this->attendance->update([
                'net_hours' => $this->adjusted_net_hours,
                'status' => $this->adjusted_status,
                'calculation_method' => 'adjusted',
                'notes' => $this->appendToNotes($this->reason),
            ]);

            DB::commit();

            // Show success message and redirect
            session()->flash('message', 'Attendance adjusted successfully.');

            // Option 1: Stay on page with updated data
            $this->refreshData();

            // Option 2: Redirect back to attendance list
            // return redirect()->route('attendances.index');

        } catch (\Exception $e) {
            DB::rollBack();
            $this->addError('adjusted_net_hours', 'Failed to save adjustment: ' . $e->getMessage());
        }
    }

    // Check if user actually changed anything
    private function hasChanges()
    {
        return $this->adjusted_net_hours != $this->original_net_hours
            || $this->adjusted_status != $this->original_status;
    }

    // Format notes for the attendance record
    private function appendToNotes($reason)
    {
        $timestamp = now()->format('Y-m-d H:i');
        $userName = auth()->user()->name;

        $note = "\n[{$timestamp}] Adjusted by {$userName}: {$reason}";

        // If attendance already has notes, append. Otherwise, start new.
        return $this->attendance->notes
            ? $this->attendance->notes . $note
            : $note;
    }

    // Refresh the data after successful save
    private function refreshData()
    {
        $this->attendance->refresh();
        $this->original_net_hours = $this->attendance->net_hours;
        $this->original_status = $this->attendance->status;
        $this->adjusted_net_hours = $this->attendance->net_hours;
        $this->adjusted_status = $this->attendance->status;
        $this->reason = ''; // Clear the reason field
    }

    // Cancel and go back
    public function cancel()
    {
        return redirect()->route('attendances.show', $this->attendanceId);
    }

    // Get recent adjustments for the audit trail display
    public function getRecentAdjustmentsProperty()
    {
        return AttendanceAdjustment::where('attendance_id', $this->attendanceId)
            ->orderBy('adjusted_at', 'desc')
            ->limit(5)
            ->get();
    }

    // Render the component view
    public function render()
    {
        return view('hr::components.livewire.bootstrap.time.attendance.adjust-attendance-mvp', [
            'recentAdjustments' => $this->recentAdjustments,
        ]);


    }
}
