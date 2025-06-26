<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Lecturer;
use App\Models\Day;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Schema;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Section::truncate();
        Schema::enableForeignKeyConstraints();
        $subjects = Subject::all();
        $lecturers = Lecturer::all();
        $days = Day::all();
        $timeSlots = TimeSlot::all();

        if ($lecturers->isEmpty() || $days->isEmpty() || $timeSlots->isEmpty()) {
            // Handle cases where there are no lecturers, days, or time slots
            // You might want to log a message or skip seeding sections
            return;
        }

        foreach ($subjects as $subject) {
            $usedCombinations = [];
            // Create 2-3 sections for each subject to ensure variety
            for ($i = 0; $i < rand(2, 3); $i++) {
                $lecturer = $lecturers->random();
                $day = $days->random();
                $timeSlot = $timeSlots->random();

                // To avoid duplicate sections for the same subject
                $combination = $lecturer->id . '-' . $day->id . '-' . $timeSlot->id;
                if (in_array($combination, $usedCombinations)) {
                    continue;
                }
                $usedCombinations[] = $combination;

                Section::factory()->create([
                    'subject_id' => $subject->id,
                    'lecturer_id' => $lecturer->id,
                    'day_of_week' => $day->name,
                    'start_time' => $timeSlot->start_time,
                    'end_time' => $timeSlot->end_time,
                ]);
            }
        }
    }
}
