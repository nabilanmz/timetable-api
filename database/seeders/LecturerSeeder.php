<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Lecturer;

class LecturerSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $lecturers = [
            [
                'name' => 'Dr. Alan Turing',
                'email' => 'alan.turing@university.com',
                'department' => 'Computer Science'
            ],
            [
                'name' => 'Dr. Ada Lovelace',
                'email' => 'ada.lovelace@university.com',
                'department' => 'Computer Science'
            ],
            [
                'name' => 'Dr. Isaac Newton',
                'email' => 'isaac.newton@university.com',
                'department' => 'Mathematics'
            ],
            [
                'name' => 'Dr. Sigmund Freud',
                'email' => 'sigmund.freud@university.com',
                'department' => 'Psychology'
            ],
        ];

        foreach ($lecturers as $lecturer) {
            Lecturer::create($lecturer);
        }
    }
}
