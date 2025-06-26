<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TimeSlot;
use Illuminate\Support\Facades\Schema;

class TimeSlotSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Schema::disableForeignKeyConstraints();
        TimeSlot::truncate();
        Schema::enableForeignKeyConstraints();
        $timeSlots = [
            ['start_time' => '08:00:00', 'end_time' => '09:00:00'],
            ['start_time' => '09:00:00', 'end_time' => '10:00:00'],
            ['start_time' => '10:00:00', 'end_time' => '11:00:00'],
            ['start_time' => '11:00:00', 'end_time' => '12:00:00'],
            ['start_time' => '12:00:00', 'end_time' => '13:00:00'],
            ['start_time' => '13:00:00', 'end_time' => '14:00:00'],
            ['start_time' => '14:00:00', 'end_time' => '15:00:00'],
            ['start_time' => '15:00:00', 'end_time' => '16:00:00'],
            ['start_time' => '16:00:00', 'end_time' => '17:00:00'],
        ];

        foreach ($timeSlots as $timeSlot) {
            TimeSlot::create($timeSlot);
        }
    }
}
