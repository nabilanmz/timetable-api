<?php

namespace Tests\Feature;

use App\Models\GeneratedTimetable;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Lecturer;
use App\Models\Day;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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

        // Create required data
        $subject1 = Subject::factory()->create(['name' => 'Introduction to Programming', 'code' => 'CS101']);
        $lecturer = Lecturer::factory()->create(['name' => 'Dr. Smith']);
        
        // Create days
        $monday = Day::factory()->create(['name' => 'Monday']);
        $tuesday = Day::factory()->create(['name' => 'Tuesday']);

        // Create sections for the subject with proper activity types
        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 'TC1L',
            'activity' => 'Lecture',
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Hall A',
            'tied_to' => ['TT1L'],
        ]);

        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 'TT1L',
            'activity' => 'Tutorial',
            'day_of_week' => 'Tuesday',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'venue' => 'Room B',
            'tied_to' => [],
        ]);

        // Send request with new API format
        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject1->id],
                'days' => [$monday->id, $tuesday->id],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'yes',
                'lecturers' => [],
                'mode' => 1, // compact
            ]
        ]);

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
