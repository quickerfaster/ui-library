<?php

namespace App\Modules\Hr\Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;


class DailyEarningsSeeder extends Seeder
{


    public function run()
    {
        // Truncate existing records
        /*DB::table('daily_earnings')->truncate();

        // Get all attendance records with both check-in and check-out
        $attendanceRecords = DB::table('daily_attendances')
            ->select('employee_id', 'attendance_date', 'attendance_type', 'attendance_time')
            ->orderBy('attendance_time')
            ->get()
            ->groupBy(['employee_id', 'attendance_date']);

        $earnings = [];
        $hourlyRates = [];

        foreach ($attendanceRecords as $employeeId => $dates) {
            // Assign random hourly rate per employee (8-30 USD)
            if (!isset($hourlyRates[$employeeId])) {
                $hourlyRates[$employeeId] = mt_rand(800, 3000) / 100; // 8.00 to 30.00
            }
            
            foreach ($dates as $date => $records) {
                // We need exactly one check-in and one check-out
                $checkIn = $records->where('attendance_type', 'check-in')->first();
                $checkOut = $records->where('attendance_type', 'check-out')->first();

                if (!$checkIn || !$checkOut) continue;

                $start = Carbon::parse($checkIn->attendance_time);
                $end = Carbon::parse($checkOut->attendance_time);
                
                // Calculate hours worked (max 14 hours, min 0.5 hours)
                $hoursWorked = max(0.5, min(14, $end->diffInMinutes($start) / 60));
                $hoursWorked = round($hoursWorked, 2);
                
                // Calculate amount earned
                $amountEarned = round($hoursWorked * $hourlyRates[$employeeId], 2);

                $earnings[] = [
                    'employee_id' => $employeeId,
                    'work_date' => $date,
                    'hours_worked' => $hoursWorked,
                    'amount_earned' => $amountEarned,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }*/

        // Insert records in chunks
        /*foreach (array_chunk($earnings, 500) as $chunk) {
            DB::table('daily_earnings')->insert($chunk);
        }*/
    }




}



