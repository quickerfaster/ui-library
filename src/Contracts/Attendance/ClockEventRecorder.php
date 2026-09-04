<?php

namespace QuickerFaster\UILibrary\Contracts\Attendance;

/**
 * ClockEventRecorder contract — allows the domain-independent library
 * to record clock-in/clock-out events without knowing about the consuming
 * app's ClockEvent model.
 *
 * The consuming app binds its implementation in a service provider:
 *
 *   $this->app->bind(
 *       \QuickerFaster\UILibrary\Contracts\Attendance\ClockEventRecorder::class,
 *       \App\Modules\Attendance\Services\ClockEventRecorderService::class
 *   );
 */
interface ClockEventRecorder
{
    /**
     * Get the latest clock event for an employee today.
     *
     * @param int|string $employeeId
     * @return array{event_type: string, timestamp: string}|null
     */
    public function getLatestToday(int|string $employeeId): ?array;

    /**
     * Record a clock-in or clock-out event.
     *
     * @param int|string $employeeId
     * @param string $eventType  'clock_in' or 'clock_out'
     * @param array $meta        Optional metadata (ip, device, location, etc.)
     * @return array{event_type: string, timestamp: string}
     */
    public function record(int|string $employeeId, string $eventType, array $meta = []): array;
}