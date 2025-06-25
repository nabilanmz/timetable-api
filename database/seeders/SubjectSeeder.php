<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Subject;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $subjects = [
            [
                'name' => 'Introduction to Computer Science',
                'code' => 'CS101',
                'description' => 'Fundamentals of computer science, including algorithms, data structures, and programming concepts.'
            ],
            [
                'name' => 'Calculus I',
                'code' => 'MATH101',
                'description' => 'An introduction to differential calculus.'
            ],
            [
                'name' => 'Introduction to Psychology',
                'code' => 'PSY101',
                'description' => 'A survey of the major areas of psychology.'
            ],
            [
                'name' => 'Linear Algebra',
                'code' => 'MATH201',
                'description' => 'A course on vector spaces, linear transformations, and matrices.'
            ],
            [
                'name' => 'Data Structures and Algorithms',
                'code' => 'CS201',
                'description' => 'A deeper dive into data structures and algorithms.'
            ],
        ];

        foreach ($subjects as $subject) {
            Subject::create($subject);
        }
    }
}
