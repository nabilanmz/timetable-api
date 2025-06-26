<?php

namespace Tests\Feature;

use App\Models\GeneratedTimetable;
use App\Models\Section;
use App\Models\Subject;
use App\Models\TimetablePreference;
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

        // Create subjects that will be used in preferences and entries
        $subject1 = Subject::factory()->create(['name' => 'Introduction to Programming', 'code' => 'CS101']);
        $lecturer = User::factory()->create();

        TimetablePreference::factory()->create([
            'user_id' => $user->id,
            'preferences' => [
                'subjects' => [$subject1->name], // Use real subject name
                'schedule_style' => 'compact',
                'preferred_days' => ['Monday', 'Wednesday'],
                'preferred_start' => '09:00:00',
                'preferred_end' => '17:00:00',
                'enforce_ties' => false, // Disable tie enforcement as it's not supported by the Section model
                'preferred_lecturers' => [],
            ]
        ]);

        // Create sections for the subject
        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => '1',
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Hall A',
        ]);

        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 'T1',
            'day_of_week' => 'Monday',
            'start_time' => '11:00:00',
            'end_time' => '12:00:00',
            'venue' => 'Room B',
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
