<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\GeneratedTimetable>
 */
class GeneratedTimetableFactory extends Factory
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
            'timetable' => json_encode([
                'Monday' => [
                    ['subject' => 'Math', 'time' => '9:00-10:00', 'lecturer' => 'Dr. Smith'],
                ],
                'Tuesday' => [
                    ['subject' => 'Physics', 'time' => '11:00-12:00', 'lecturer' => 'Dr. Jones'],
                ]
            ]),
            'active' => true,
        ];
    }
}
