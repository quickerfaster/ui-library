<?php

namespace App\Modules\Hr\Database\Seeders;



use App\Models\WorkDay;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class WorkShiftSeeder extends Seeder
{
    public function run()
    {
        /*DB::table('shifts')->insert([
            ['name' => 'Morning', 'start_time' => '06:00:00', 'end_time' => '14:00:00', 'is_overnight' => false, 'is_active' => true],
            ['name' => 'Afternoon', 'start_time' => '14:00:00', 'end_time' => '22:00:00', 'is_overnight' => false, 'is_active' => true],
            ['name' => 'Night', 'start_time' => '22:00:00', 'end_time' => '06:00:00', 'is_overnight' => false, 'is_active' => true],
        ]);*/
    }
}