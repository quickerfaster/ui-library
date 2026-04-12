<?php

namespace App\Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class DailyAttendanceSeeder extends Seeder
{



  public function run()
{
    /*$records = [];
    $devices = ['DEVICE001', 'DEVICE002', 'DEVICE003'];
    $locations = [
        ['lat' => 1203, 'lon' => 8843],
        ['lat' => 1205, 'lon' => 8845],
        ['lat' => 1207, 'lon' => 8847]
    ];
    
    // Employee shifts - 0:Morning, 1:Afternoon, 2:Night
    $employeeShifts = [
        'EMP-20250628-001' => 0, // Morning
        'EMP-20250628-002' => 0, // Morning
        'EMP-20250628-003' => 2, // Night
        'EMP-20250628-004' => 2, // Night
        'EMP-20250628-005' => 0, // Morning
        'EMP-20250628-006' => 1, // Afternoon
        'EMP-20250628-007' => 2, // Night
        'EMP-20250628-008' => 0, // Morning
        'EMP-20250628-009' => 1, // Afternoon
        'EMP-20250628-010' => 0, // Morning
        'EMP-20250628-011' => 2, // Night
        'EMP-20250628-012' => 1, // Afternoon
        'EMP-20250628-013' => 0, // Morning
        'EMP-20250628-014' => 1, // Afternoon
        'EMP-20250628-015' => 2, // Night
        'EMP-20250628-016' => 0, // Morning
        'EMP-20250628-017' => 1, // Afternoon
        'EMP-20250628-018' => 2, // Night
        'EMP-20250628-019' => 0, // Morning
        'EMP-20250628-020' => 1, // Afternoon
    ];

    // Shift configurations
    $shiftTimes = [
        // Morning: 6am-2pm
        0 => ['start' => '06:00:00', 'end' => '14:00:00'],
        // Afternoon: 2pm-10pm
        1 => ['start' => '14:00:00', 'end' => '22:00:00'],
        // Night: 10pm-6am (next day)
        2 => ['start' => '22:00:00', 'end' => '06:00:00']
    ];

    // Sample date range (30 days: May 30 - June 28, 2025)
    $startDate = \Carbon\Carbon::create(2025, 5, 30);
    $endDate = \Carbon\Carbon::create(2025, 6, 28);

    // Add sample records first
    $sampleRecords = [
        // Sample data provided in the question
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-001',
            'attendance_type' => 'check-in',
            'attendance_time' => '2025-06-21 08:05:00',
            'scheduled_start' => '2025-06-21 06:00:00',
            'check_status' => 'late',
            'minutes_difference' => 125,
            'scheduled_end' => '2025-06-21 14:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-005',
            'attendance_type' => 'check-in',
            'attendance_time' => '2025-06-21 08:08:00',
            'scheduled_start' => '2025-06-21 06:00:00',
            'check_status' => 'late',
            'minutes_difference' => 128,
            'scheduled_end' => '2025-06-21 14:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-003',
            'attendance_type' => 'check-in',
            'attendance_time' => '2025-06-21 07:50:00',
            'scheduled_start' => '2025-06-21 22:00:00',
            'check_status' => 'early',
            'minutes_difference' => -850,
            'scheduled_end' => '2025-06-22 06:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-004',
            'attendance_type' => 'check-in',
            'attendance_time' => '2025-06-21 22:04:00',
            'scheduled_start' => '2025-06-21 22:00:00',
            'check_status' => 'on_time',
            'minutes_difference' => 4,
            'scheduled_end' => '2025-06-22 06:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-005',
            'attendance_type' => 'check-out',
            'attendance_time' => '2025-06-21 22:04:00',
            'scheduled_start' => '2025-06-21 06:00:00',
            'check_status' => 'late_checkout',
            'minutes_difference' => 484,
            'scheduled_end' => '2025-06-21 14:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
        [
            'attendance_date' => '2025-06-21',
            'employee_id' => 'EMP-20250628-001',
            'attendance_type' => 'check-out',
            'attendance_time' => '2025-06-21 14:01:00',
            'scheduled_start' => '2025-06-21 06:00:00',
            'check_status' => 'on_time_checkout',
            'minutes_difference' => 1,
            'scheduled_end' => '2025-06-21 14:00:00',
            'device_id' => 'DEVICE001',
            'latitude' => 1203,
            'longitude' => 8843,
            'sync_status' => 'pending',
            'sync_attempts' => 0,
        ],
    ];

    foreach ($sampleRecords as $record) {
        $records[] = array_merge($record, [
            'created_at' => now(),
            'updated_at' => now()
        ]);
    }*/

    // Generate data for 30 days
    /*
    for ($date = $startDate; $date <= $endDate; $date->addDay()) {
        $dateStr = $date->format('Y-m-d');
        
        // Skip sample date since we already have sample data
        if ($dateStr === '2025-06-21') continue;
        
        foreach ($employeeShifts as $empId => $shiftType) {
            // 10% chance of absence
            if (rand(1, 10) === 1) continue;
            
            $device = $devices[array_rand($devices)];
            $location = $locations[array_rand($locations)];
            $shift = $shiftTimes[$shiftType];
            
            // Handle night shift end date (next day)
            $endDateObj = $date->copy();
            if ($shiftType === 2) {
                $endDateObj->addDay();
            }
            
            $scheduledStart = "$dateStr {$shift['start']}";
            $scheduledEnd = "{$endDateObj->format('Y-m-d')} {$shift['end']}";
            
            // Generate check-in record
            $checkInStatus = 'on_time';
            $checkInDiff = 0;
            
            // 20% chance of being late
            if (rand(1, 5) === 1) {
                $checkInStatus = 'late';
                $checkInDiff = rand(5, 120); // 5-120 minutes late
            } 
            // 10% chance of being early
            elseif (rand(1, 10) === 1) {
                $checkInStatus = 'early';
                $checkInDiff = -rand(5, 60); // 5-60 minutes early
            }
            
            $checkInTime = date('Y-m-d H:i:s', strtotime("$scheduledStart +$checkInDiff minutes"));
            
            $records[] = [
                'attendance_date' => $dateStr,
                'employee_id' => $empId,
                'attendance_type' => 'check-in',
                'attendance_time' => $checkInTime,
                'scheduled_start' => $scheduledStart,
                'check_status' => $checkInStatus,
                'minutes_difference' => $checkInDiff,
                'scheduled_end' => $scheduledEnd,
                'device_id' => $device,
                'latitude' => $location['lat'],
                'longitude' => $location['lon'],
                'sync_status' => (rand(1, 5) === 1) ? 'pending' : 'synced',
                'sync_attempts' => (rand(1, 10) === 1) ? rand(1, 3) : 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
            
            // Generate check-out record
            $checkOutStatus = 'on_time_checkout';
            $checkOutDiff = 0;
            
            // 15% chance of early checkout
            if (rand(1, 7) === 1) {
                $checkOutStatus = 'early_checkout';
                $checkOutDiff = -rand(5, 60); // 5-60 minutes early
            } 
            // 20% chance of late checkout
            elseif (rand(1, 5) === 1) {
                $checkOutStatus = 'late_checkout';
                $checkOutDiff = rand(5, 180); // 5-180 minutes late
            }
            
            $checkOutTime = date('Y-m-d H:i:s', strtotime("$scheduledEnd +$checkOutDiff minutes"));
            
            $records[] = [
                'attendance_date' => $dateStr,
                'employee_id' => $empId,
                'attendance_type' => 'check-out',
                'attendance_time' => $checkOutTime,
                'scheduled_start' => $scheduledStart,
                'check_status' => $checkOutStatus,
                'minutes_difference' => $checkOutDiff,
                'scheduled_end' => $scheduledEnd,
                'device_id' => $device,
                'latitude' => $location['lat'],
                'longitude' => $location['lon'],
                'sync_status' => (rand(1, 5) === 1) ? 'pending' : 'synced',
                'sync_attempts' => (rand(1, 10) === 1) ? rand(1, 3) : 0,
                'created_at' => now(),
                'updated_at' => now()
            ];
        }
    }
        */

    // DB::table('daily_attendances')->insert($records);
}



}