<?php
namespace App\Modules\Hr\Database\Factories;

use App\Modules\Hr\Models\Shift;
use Illuminate\Database\Eloquent\Factories\Factory;

class ShiftFactory extends Factory
{
    protected $model = Shift::class;

    public function definition()
    {
        return [
            'name' => $this->faker->word . ' Shift',
            'code' => 'SHI-' . $this->faker->unique()->numberBetween(1000, 9999),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'is_active' => true,
        ];
    }
}