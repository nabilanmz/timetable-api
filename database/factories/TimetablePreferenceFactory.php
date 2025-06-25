<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimetablePreference>
 */
class TimetablePreferenceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'preferences' => [
                'courses' => $this->faker->words(3),
                'available_days' => $this->faker->words(2),
                'available_times' => $this->faker->words(2),
                'preferred_lecturers' => $this->faker->words(2),
            ],
        ];
    }
}
