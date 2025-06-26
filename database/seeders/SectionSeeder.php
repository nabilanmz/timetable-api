<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;

class SectionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $csvFile = fopen(base_path("database/seeders/classes.csv"), "r");

        $firstline = true;
        while (($data = fgetcsv($csvFile, 2000, ",")) !== FALSE) {

                // Use firstOrCreate with the unique key (`code`) as the first argument
                $subject = Subject::firstOrCreate(
                    ['code' => $data[1]],
                    ['name' => $data[0]]
                );

                $lecturer = User::firstOrCreate(
                    ['name' => $data[4]],
                    [
                        'email' => strtolower(str_replace(' ', '.', $data[4])) . '@example.com',
                        'password' => bcrypt('password')
                    ]
                );

                Section::create([
                    'subject_id' => $subject->id,
                    'section_number' => (int)$data[2],
                    'lecturer_id' => $lecturer->id,
                    'start_time' => date("H:i:s", strtotime($data[5])),
                    'end_time' => date("H:i:s", strtotime($data[6])),
                    'day_of_week' => $data[4],
                    'venue' => $data[8],
                    'capacity' => !empty($data[9]) && is_numeric($data[9]) ? (int)$data[9] : 50, // Default capacity
                ]);
            }
            $firstline = false;
        }

}
