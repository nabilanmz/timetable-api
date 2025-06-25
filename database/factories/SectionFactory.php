<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition()
    {
        $startTime = $this->faker->time('H:i:s');
        return [
            'subject_id' => Subject::factory(),
            'section_number' => $this->faker->unique()->numberBetween(1, 10),
            'lecturer_id' => User::factory(),
            'start_time' => $startTime,
            'end_time' => \Carbon\Carbon::parse($startTime)->addHour()->format('H:i:s'),
            'day_of_week' => $this->faker->dayOfWeek,
            'venue' => 'Room ' . $this->faker->numberBetween(100, 500),
            'capacity' => $this->faker->numberBetween(20, 50),
        ];
    }
}
