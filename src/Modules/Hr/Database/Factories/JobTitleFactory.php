<?php

namespace App\Modules\Hr\Database\Factories;

use App\Modules\Admin\Models\JobTitle;
use Illuminate\Database\Eloquent\Factories\Factory;

class JobTitleFactory extends Factory
{
    protected $model = JobTitle::class;

    public function definition()
    {
        return [
            'title' => $this->faker->unique()->jobTitle(),
            'description' => $this->faker->optional(0.7)->paragraph(),
            // 'editable' => $this->faker->randomElement(['Yes', 'No']),
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    /**
     * Indicate that the job title is editable.
     */
    public function editable()
    {
        return $this->state(fn (array $attributes) => [
            'editable' => 'Yes',
        ]);
    }

    /**
     * Indicate that the job title is non-editable (system-protected).
     */
    public function nonEditable()
    {
        return $this->state(fn (array $attributes) => [
            'editable' => 'No',
        ]);
    }

    /**
     * Create a specific job title.
     */
    public function withTitle(string $title)
    {
        return $this->state(fn (array $attributes) => [
            'title' => $title,
        ]);
    }
}
