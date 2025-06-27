<?php

namespace Database\Factories;

use App\Models\Section;
use App\Models\Subject;
use App\Models\Lecturer;
use Illuminate\Database\Eloquent\Factories\Factory;

class SectionFactory extends Factory
{
    protected $model = Section::class;

    public function definition()
    {
        $startTime = $this->faker->time('H:i:s');
        $activities = ['Lecture', 'Tutorial', 'Lab'];
        $activity = $this->faker->randomElement($activities);
        
        // Generate section number based on activity
        $sectionNumber = match($activity) {
            'Lecture' => 'TC' . $this->faker->numberBetween(1, 6) . 'L',
            'Tutorial' => 'TT' . $this->faker->numberBetween(1, 20) . 'L',
            'Lab' => 'TL' . $this->faker->numberBetween(1, 10) . 'L',
            default => $this->faker->numberBetween(1, 10),
        };
        
        return [
            'subject_id' => Subject::factory(),
            'section_number' => $sectionNumber,
            'activity' => $activity,
            'lecturer_id' => Lecturer::factory(),
            'start_time' => $startTime,
            'end_time' => \Carbon\Carbon::parse($startTime)->addHour()->format('H:i:s'),
            'day_of_week' => $this->faker->dayOfWeek,
            'venue' => 'Room ' . $this->faker->numberBetween(100, 500),
            'capacity' => $this->faker->numberBetween(20, 50),
        ];
    }
}
