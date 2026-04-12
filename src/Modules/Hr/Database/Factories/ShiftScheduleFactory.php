<?php
namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\ShiftSchedule;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftScheduleFactory extends Factory
{
    protected $model = ShiftSchedule::class;

    public function definition()
    {
        return [
            'start_time_override' => '08:00:00',
            'end_time_override' => '17:00:00',
            'status' => 'scheduled',

            'schedule_type' => 'regular',
            'is_published' => true,

            // actual_start_time'
            // 'actual_end_time'
        ];
        
    }
}