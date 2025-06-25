<?php

namespace Database\Factories;

use App\Models\Day;
use App\Models\Lecturer;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\Timetable;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TimetableEntry>
 */
class TimetableEntryFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'timetable_id' => Timetable::factory(),
            'subject_id' => Subject::factory(),
            'lecturer_id' => Lecturer::factory(),
            'day_id' => Day::factory(),
            'time_slot_id' => TimeSlot::factory(),
        ];
    }
}
