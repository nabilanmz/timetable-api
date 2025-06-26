<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Subject;
use App\Models\Lecturer;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Schema;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        Schema::disableForeignKeyConstraints();
        User::truncate();
        Subject::truncate();
        Lecturer::truncate();
        Schema::enableForeignKeyConstraints();

        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);

        User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
            'is_admin' => true,
        ]);

        // Create a specific number of subjects and lecturers
        Subject::factory()->count(10)->create();
        Lecturer::factory()->count(5)->create();

        $this->call([
            DaySeeder::class,
            TimeSlotSeeder::class,
            SectionSeeder::class, // This will now use the created subjects and lecturers
        ]);
    }
}
