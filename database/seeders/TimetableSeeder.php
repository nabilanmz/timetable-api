<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;
use App\Models\Lecturer;
use App\Models\Day;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\User;
use Carbon\Carbon;

class TimetableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $user = User::first();
        $csvFile = fopen(base_path("database/seeders/classes.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {
            if (!$firstline) {
                // Create or find Subject
                $subject = Subject::firstOrCreate(
                    ['code' => $data[0]],
                    ['name' => $data[1]]
                );

                // Create or find Lecturer
                $lecturer = Lecturer::firstOrCreate(
                    ['name' => $data[9]],
                    ['email' => strtolower(str_replace(' ', '.', $data[9])) . '@university.com',
                     'department' => 'FCI']
                );

                // Find Day
                $day = Day::where('name', $data[4])->first();

                // Create or find TimeSlot
                $startTime = Carbon::parse($data[5])->format('H:i:s');
                $endTime = Carbon::parse($data[6])->format('H:i:s');
                $timeSlot = TimeSlot::firstOrCreate(
                    ['start_time' => $startTime, 'end_time' => $endTime]
                );

                // Create Timetable if it doesn't exist
                $timetable = Timetable::firstOrCreate(
                    ['name' => 'FCI Timetable'],
                    [
                        'description' => 'Faculty of Computing and Informatics Timetable',
                        'created_by' => $user->id
                    ]
                );

                // Create Timetable Entry
                $tied_to_value = null;
                if (!empty($data[8])) {
                    // Split the string by comma or space and filter out empty values
                    $codes = preg_split('/[\s,]+/', $data[8], -1, PREG_SPLIT_NO_EMPTY);
                    if (!empty($codes)) {
                        $tied_to_value = json_encode($codes);
                    }
                }

                TimetableEntry::create([
                    'timetable_id' => $timetable->id,
                    'subject_id' => $subject->id,
                    'lecturer_id' => $lecturer->id,
                    'day_id' => $day->id,
                    'time_slot_id' => $timeSlot->id,
                    'activity' => $data[2],
                    'section' => $data[3],
                    'venue' => $data[7],
                    'tied_to' => $tied_to_value,
                ]);
            }
            $firstline = false;
        }

        fclose($csvFile);
    }
}
