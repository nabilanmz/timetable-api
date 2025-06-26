<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Day;
use Illuminate\Support\Facades\Schema;

class DaySeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        Day::truncate();
        Schema::enableForeignKeyConstraints();
        $days = [
            ['name' => 'Monday'],
            ['name' => 'Tuesday'],
            ['name' => 'Wednesday'],
            ['name' => 'Thursday'],
            ['name' => 'Friday'],
        ];

        foreach ($days as $day) {
            Day::create($day);
        }
    }
}
