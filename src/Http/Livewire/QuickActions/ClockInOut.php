<?php

namespace QuickerFaster\UILibrary\Http\Livewire\QuickActions;

use Livewire\Component;
use QuickerFaster\UILibrary\Contracts\Attendance\ClockEventRecorder;

/**
 * ClockInOut — a standalone Livewire component for employee clock-in/clock-out.
 *
 * Shows current clock status (Clocked In since [time] or Not clocked in),
 * provides a large toggle button, records clock events via the injected
 * ClockEventRecorder contract, and dispatches a 'clockEventRecorded' event
 * so other components (activity logs, stat widgets) can refresh.
 *
 * Usage:
 *   <livewire:qf.clock-in-out :employee-id="$employee->id" />
 */
class ClockInOut extends Component
{
    /** @var int|string */
    public $employeeId;

    /** @var string 'clocked_in' | 'clocked_out' */
    public string $status = 'clocked_out';

    /** @var string|null Human-readable clock-in time (e.g. "8:00 AM") */
    public ?string $clockedInSince = null;

    /** @var string|null ISO timestamp of the last event */
    public ?string $lastEventAt = null;

    /** @var bool Loading state */
    public bool $recording = false;

    /** @var string|null Error message to display */
    public ?string $error = null;

    /** @var float|null Browser geolocation latitude */
    public ?float $latitude = null;

    /** @var float|null Browser geolocation longitude */
    public ?float $longitude = null;

    protected $listeners = [
        // 'clockEventRecorded' listener removed — the component already
        // sets its own status in toggle(). Listening to its own event
        // caused a redundant refreshStatus() call that re-queried the DB
        // and briefly flickered the UI back to the previous state.
    ];

    public function mount($employeeId = null): void
    {
        $this->employeeId = $employeeId;

        if ($this->employeeId) {
            $this->refreshStatus();
        }
    }

    /**
     * Refresh the current clock status from the database.
     */
    public function refreshStatus(): void
    {
        if (!$this->employeeId) {
            return;
        }

        try {
            $recorder = app(ClockEventRecorder::class);
            $latest = $recorder->getLatestToday($this->employeeId);

            if ($latest && $latest['event_type'] === 'clock_in') {
                $this->status = 'clocked_in';
                $this->clockedInSince = $this->formatTime($latest['timestamp']);
                $this->lastEventAt = $latest['timestamp'];
            } else {
                $this->status = 'clocked_out';
                $this->clockedInSince = null;
                $this->lastEventAt = $latest['timestamp'] ?? null;
            }
        } catch (\Throwable $e) {
            $this->error = 'Unable to load clock status.';
        }
    }

    /**
     * Toggle clock in / clock out.
     */
    public function toggle(?float $latitude = null, ?float $longitude = null): void
    {
        if (!$this->employeeId) {
            $this->error = 'No employee record found.';
            return;
        }

        $this->recording = true;
        $this->error = null;

        try {
            $recorder = app(ClockEventRecorder::class);

            // Build meta array with optional geolocation data
            $meta = [];
            if ($latitude !== null && $longitude !== null) {
                $meta['latitude'] = $latitude;
                $meta['longitude'] = $longitude;
            }

            if ($this->status === 'clocked_out') {
                $result = $recorder->record($this->employeeId, 'clock_in', $meta);
                $this->status = 'clocked_in';
                $this->clockedInSince = $this->formatTime($result['timestamp']);
                $this->lastEventAt = $result['timestamp'];
            } else {
                $result = $recorder->record($this->employeeId, 'clock_out', $meta);
                $this->status = 'clocked_out';
                $this->clockedInSince = null;
                $this->lastEventAt = $result['timestamp'];
            }

            $this->dispatch('clockEventRecorded', [
                'employee_id' => $this->employeeId,
                'event_type' => $result['event_type'],
                'timestamp' => $result['timestamp'],
            ]);

            $this->dispatch('notify', [
                'type' => 'success',
                'message' => $this->status === 'clocked_in'
                    ? 'Clocked in successfully!'
                    : 'Clocked out successfully!',
            ]);
        } catch (\Throwable $e) {
            $this->error = $e->getMessage();
        } finally {
            $this->recording = false;
        }
    }

    /**
     * Format an ISO timestamp into a human-readable time string.
     */
    protected function formatTime(?string $timestamp): ?string
    {
        if (!$timestamp) {
            return null;
        }

        try {
            return \Carbon\Carbon::parse($timestamp)->format('g:i A');
        } catch (\Throwable) {
            return $timestamp;
        }
    }

    public function render()
    {
        return view('qf::livewire.quick-actions.clock-in-out');
    }
}