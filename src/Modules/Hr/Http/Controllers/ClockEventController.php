<?php

namespace App\Modules\Hr\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Hr\Models\ClockEvent;
use App\Modules\Hr\Services\AttendanceAggregator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Carbon\Carbon;

class ClockEventController extends Controller
{
    public function __construct(
        private AttendanceAggregator $aggregator
    ) {}

    /**
     * Convert Android payload timestamp (milliseconds) to datetime string
     */
    private function convertAndroidTimestamp($timestampMs)
    {
        // Convert milliseconds to seconds and create Carbon instance
        return Carbon::createFromTimestampMs($timestampMs);
    }

    /**
     * Convert latitude/longitude from decimal degrees to microdegrees
     */
    private function convertToMicrodegrees($decimal)
    {
        return $decimal ? (int) round($decimal * 1_000_000) : null;
    }

    /**
     * Convert Android event type to internal event type
     */
    private function convertEventType($androidEventType)
    {
        return $androidEventType === 'check-in' ? 'clock_in' : 'clock_out';
    }

    /**
     * Validate a single Android clock event payload
     */
    private function validateAndroidEvent(array $androidEvent)
    {
        $validator = Validator::make($androidEvent, [
            'employee_id' => 'required|string',
            'employee_number' => 'required|string|exists:employees,employee_number',
            'event_type' => 'required|in:check-in,check-out',
            'timestamp' => 'required|numeric',
            'device_id' => 'nullable|string',
            'device_name' => 'nullable|string',
            'location_name' => 'nullable|string',
            'timezone' => 'nullable|string',
            'notes' => 'nullable|string',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        if ($validator->fails()) {
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * Convert Android event format to internal database format
     */
    private function convertAndroidToInternal(array $validatedEvent)
    {
        // Convert timestamp
        $timestamp = $this->convertAndroidTimestamp($validatedEvent['timestamp']);

        return [
            'employee_id' => $validatedEvent['employee_number'], // Store employee_number as employee_id
            'employee_number' => $validatedEvent['employee_number'],
            'attendance_type' => $validatedEvent['event_type'],
            'attendance_time' => $timestamp->format('Y-m-d H:i:s'),
            'attendance_date' => $timestamp->format('Y-m-d'),
            'device_id' => $validatedEvent['device_id'] ?? null,
            'device_name' => $validatedEvent['device_name'] ?? null,
            'location_name' => $validatedEvent['location_name'] ?? null,
            'timezone' => $validatedEvent['timezone'] ?? null,
            'notes' => $validatedEvent['notes'] ?? null,
            'latitude' => $this->convertToMicrodegrees($validatedEvent['latitude'] ?? null),
            'longitude' => $this->convertToMicrodegrees($validatedEvent['longitude'] ?? null),
            'raw_android_payload' => json_encode($validatedEvent), // Store original for debugging
        ];
    }

    /**
     * Process a single clock event (used by both single and batch)
     */
    private function processClockEvent(array $internalData, Request $request)
    {
        // Convert microdegrees to decimal for storage
        $lat = $internalData['latitude'] ? round($internalData['latitude'] / 1_000_000, 8) : null;
        $lng = $internalData['longitude'] ? round($internalData['longitude'] / 1_000_000, 8) : null;

        // Map to internal event type
        $eventType = $this->convertEventType($internalData['attendance_type']);
        $timestamp = $internalData['attendance_time'];

        // 🔑 IDEMPOTENCY CHECK: Prevent duplicate events
        // $existing = ClockEvent::where('employee_number', $internalData['employee_id'])
        $existing = ClockEvent::where('employee_id', $internalData['employee_id'])
            ->where('timestamp', $timestamp)
            ->where('event_type', $eventType)
            ->exists();

        if ($existing) {
            return [
                'status' => 'duplicate',
                'message' => 'Duplicate event ignored',
                'employee_number' => $internalData['employee_id'],
                'timestamp' => $timestamp,
                'event_type' => $eventType
            ];
        }

        // Save raw event with all fields
        $event = ClockEvent::create([
            'employee_id' => $internalData['employee_id'], // Stores employee_number
            // 'employee_number' => $internalData['employee_id'], // Also store in dedicated field
            'event_type' => $eventType,
            'timestamp' => $timestamp,
            'method' => 'device',
            'device_id' => $internalData['device_id'],
            'device_name' => $internalData['device_name'],
            'location_name' => $internalData['location_name'],
            'timezone' => $internalData['timezone'],
            // 'notes' => $internalData['notes'],
            'latitude' => $lat,
            'longitude' => $lng,
            'ip_address' => $request->ip(),
            'sync_status' => 'synced',
        ]);

        // Trigger daily aggregation CLIENT SEND employee_id AS A STRING EG EMP-2006-001
        $this->aggregator->recalculateForDay($internalData['employee_id'], $internalData['attendance_date']);

        return [
            'status' => 'created',
            'event_id' => $event->id,
            'message' => 'Clock event recorded',
            'employee_number' => $internalData['employee_id'],
            'timestamp' => $timestamp,
            'event_type' => $eventType
        ];
    }

    /**
     * SINGLE EVENT SYNC - Accepts same Android format as batch
     */
    public function store(Request $request)
    {
        try {
            // Validate the Android format payload
            $validatedAndroidEvent = $this->validateAndroidEvent($request->all());

            // Convert to internal format
            $internalData = $this->convertAndroidToInternal($validatedAndroidEvent);

            // Process the single event
            $result = $this->processClockEvent($internalData, $request);

            if ($result['status'] === 'duplicate') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'],
                    'status' => 'duplicate',
                    'data' => [
                        'employee_number' => $result['employee_number'],
                        'timestamp' => $result['timestamp'],
                        'event_type' => $result['event_type']
                    ]
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => $result['message'],
                'event_id' => $result['event_id'],
                'data' => [
                    'employee_number' => $result['employee_number'],
                    'timestamp' => $result['timestamp'],
                    'event_type' => $result['event_type']
                ]
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);

        } catch (\Exception $e) {
            \Log::error('Single clock event processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process clock event',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * BATCH EVENT SYNC - Accepts array of Android format events
     */
    public function batchStore(Request $request)
    {
        try {
            // Validate that request contains an array
            if (!is_array($request->all())) {
                return response()->json([
                    'success' => false,
                    'message' => 'Request must contain an array of events'
                ], 400);
            }

            $androidEvents = $request->all();
            $results = [
                'total' => count($androidEvents),
                'created' => 0,
                'duplicates' => 0,
                'failed' => 0,
                'events' => []
            ];

            foreach ($androidEvents as $index => $androidEvent) {
                try {
                    // Validate each Android event
                    $validatedAndroidEvent = $this->validateAndroidEvent($androidEvent);

                    // Convert to internal format
                    $internalData = $this->convertAndroidToInternal($validatedAndroidEvent);

                    // Process the event
                    $result = $this->processClockEvent($internalData, $request);

                    $results['events'][$index] = $result;

                    if ($result['status'] === 'created') {
                        $results['created']++;
                    } elseif ($result['status'] === 'duplicate') {
                        $results['duplicates']++;
                    }

                } catch (\Illuminate\Validation\ValidationException $e) {
                    $results['events'][$index] = [
                        'status' => 'validation_failed',
                        'message' => 'Validation failed',
                        'errors' => $e->errors(),
                        'event_data' => $androidEvent
                    ];
                    $results['failed']++;


                    \Log::error('Failed to process batch clock event at index ' . $index, [
                        'event' => $androidEvent,
                        'error' => $e->getMessage()
                    ]);

                } catch (\Exception $e) {
                    $results['events'][$index] = [
                        'status' => 'failed',
                        'message' => $e->getMessage(),
                        'event_data' => $androidEvent
                    ];
                    $results['failed']++;

                    \Log::error('Failed to process batch clock event at index ' . $index, [
                        'event' => $androidEvent,
                        'error' => $e->getMessage()
                    ]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Batch processing completed',
                'summary' => [
                    'total_processed' => $results['total'],
                    'successfully_created' => $results['created'],
                    'duplicates_ignored' => $results['duplicates'],
                    'failed' => $results['failed']
                ],
                'detailed_results' => $results['events']
            ], 201);

        } catch (\Exception $e) {
            \Log::error('Batch clock event processing failed', [
                'error' => $e->getMessage(),
                'payload' => $request->all(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Failed to process batch events',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
