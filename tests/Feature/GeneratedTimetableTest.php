<?php

namespace Tests\Feature;

use App\Models\Day;
use App\Models\GeneratedTimetable;
use App\Models\Lecturer;
use App\Models\Subject;
use App\Models\TimeSlot;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use App\Models\TimetablePreference;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class GeneratedTimetableTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_generate_a_timetable()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create subjects that will be used in preferences and entries
        $subject1 = Subject::factory()->create(['name' => 'Introduction to Programming', 'code' => 'CS101']);

        TimetablePreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'subjects' => [$subject1->name], // Use real subject name
                'schedule_style' => 'compact',
                'preferred_days' => ['Monday', 'Wednesday'],
                'preferred_start' => '09:00:00',
                'preferred_end' => '17:00:00',
                'enforce_ties' => true,
                'preferred_lecturers' => [],
            ]
        ]);

        // Create a timetable with entries for the user
        $timetable = Timetable::factory()->create(['created_by' => $user->id]);
        $lecturer = Lecturer::factory()->create();
        $day = Day::factory()->create(['name' => 'Monday']);
        $timeSlot1 = TimeSlot::factory()->create(['start_time' => '09:00:00', 'end_time' => '11:00:00']);
        $timeSlot2 = TimeSlot::factory()->create(['start_time' => '11:00:00', 'end_time' => '12:00:00']);

        // Entries for Subject 1
        TimetableEntry::factory()->create([
            'timetable_id' => $timetable->id,
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'day_id' => $day->id,
            'time_slot_id' => $timeSlot1->id,
            'activity' => 'Lecture',
            'section' => '1',
            'venue' => 'Hall A',
            'tied_to' => ['T1'], // This lecture is tied to tutorial section T1
        ]);

        TimetableEntry::factory()->create([
            'timetable_id' => $timetable->id,
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'day_id' => $day->id,
            'time_slot_id' => $timeSlot2->id,
            'activity' => 'Tutorial',
            'section' => 'T1', // This is tutorial section T1
            'venue' => 'Room B',
            'tied_to' => null,
        ]);

        $response = $this->postJson('/api/generate-timetable');

        $response->assertStatus(201);
        $this->assertDatabaseHas('generated_timetables', [
            'user_id' => $user->id,
            'active' => true,
        ]);
    }

    /** @test */
    public function a_user_can_view_their_generated_timetable()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);
        $timetable = GeneratedTimetable::factory()->create(['user_id' => $user->id]);

        $response = $this->getJson('/api/my-timetable');

        $response->assertStatus(200);
        $response->assertJsonFragment(['id' => $timetable->id]);
    }
}
