<?php

namespace App\Modules\Admin\Database\Factories;

use App\Modules\Admin\Models\Role;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

class RoleFactory extends Factory
{
    protected $model = Role::class;

    public function definition(): array
    {
        return [
            //'id' => (string) Str::uuid(), // Important for UUID-based primary keys
            'name' => $this->faker->unique()->jobTitle,
            'description' => $this->faker->sentence,
            'guard_name' => 'web', // Required by Spatie\Role
            //'editable' => true,
            //'team_id' => null,
        ];
    }
}
