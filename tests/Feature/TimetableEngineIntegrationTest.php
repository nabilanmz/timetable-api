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

class TimetableEngineIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create base data needed for all tests
        $this->createBaseData();
    }

    private function createBaseData()
    {
        // Create days
        Day::factory()->create(['id' => 1, 'name' => 'Monday']);
        Day::factory()->create(['id' => 2, 'name' => 'Tuesday']);
        Day::factory()->create(['id' => 3, 'name' => 'Wednesday']);
        Day::factory()->create(['id' => 4, 'name' => 'Thursday']);
        Day::factory()->create(['id' => 5, 'name' => 'Friday']);

        // Create lecturers with specific IDs
        Lecturer::factory()->create(['id' => 1, 'name' => 'Dr. Smith']);
        Lecturer::factory()->create(['id' => 2, 'name' => 'Prof. Johnson']);
    }

    /** @test */
    public function it_generates_timetable_with_independent_sections()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create subjects
        $subject1 = Subject::factory()->create(['name' => 'Programming', 'code' => 'CS101']);
        $subject2 = Subject::factory()->create(['name' => 'Mathematics', 'code' => 'MATH101']);

        // Create independent sections (no tied relationships)
        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => 1,
            'section_number' => 'L01',
            'activity' => 'Lecture',
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Hall A',
            'tied_to' => null,
        ]);

        Section::factory()->create([
            'subject_id' => $subject1->id,
            'lecturer_id' => 1,
            'section_number' => 'T01',
            'activity' => 'Tutorial',
            'day_of_week' => 'Tuesday',
            'start_time' => '14:00:00',
            'end_time' => '15:00:00',
            'venue' => 'Room B',
            'tied_to' => null,
        ]);

        Section::factory()->create([
            'subject_id' => $subject2->id,
            'lecturer_id' => 2,
            'section_number' => 'L01',
            'activity' => 'Lecture',
            'day_of_week' => 'Wednesday',
            'start_time' => '10:00:00',
            'end_time' => '12:00:00',
            'venue' => 'Hall C',
            'tied_to' => null,
        ]);

        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject1->id, $subject2->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no', // Test independent mode
                'lecturers' => [],
                'mode' => 1, // compact
            ]
        ]);

        $response->assertStatus(201);
        
        $timetable = $response->json();
        $this->assertArrayHasKey('timetable', $timetable);
        
        // Verify that classes are scheduled
        $scheduledClasses = collect($timetable['timetable'])->flatten(1);
        $this->assertGreaterThan(0, $scheduledClasses->count());
        
        // Verify subject coverage
        $scheduledSubjects = $scheduledClasses->pluck('subject')->unique();
        $this->assertContains('Programming', $scheduledSubjects);
        $this->assertContains('Mathematics', $scheduledSubjects);
    }

    /** @test */
    public function it_respects_time_constraints()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $subject = Subject::factory()->create(['name' => 'Early Class', 'code' => 'EARLY101']);

        // Create a section outside the requested time range
        Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => 1,
            'section_number' => 'L01',
            'activity' => 'Lecture',
            'day_of_week' => 'Monday',
            'start_time' => '07:00:00', // Before 08:00
            'end_time' => '08:00:00',
            'venue' => 'Hall A',
        ]);

        // Create a section within the requested time range
        Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => 1,
            'section_number' => 'L02',
            'activity' => 'Lecture',
            'day_of_week' => 'Tuesday',
            'start_time' => '10:00:00', // Within 08:00-18:00
            'end_time' => '12:00:00',
            'venue' => 'Hall B',
        ]);

        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1,
            ]
        ]);

        $response->assertStatus(201);
        
        $timetable = $response->json();
        $scheduledClasses = collect($timetable['timetable'])->flatten(1);
        
        // Verify all scheduled classes are within time constraints
        foreach ($scheduledClasses as $class) {
            $startTime = $class['start_time'];
            $endTime = $class['end_time'];
            
            $this->assertGreaterThanOrEqual('08:00:00', $startTime);
            $this->assertLessThanOrEqual('18:00:00', $endTime);
        }
    }

    /** @test */
    public function it_handles_compact_vs_spaced_out_scheduling_styles()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $subject = Subject::factory()->create(['name' => 'Scheduling Test', 'code' => 'SCHED101']);

        // Create multiple sections on different days
        Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => 1,
            'section_number' => 'L01',
            'activity' => 'Lecture',
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Hall A',
        ]);

        Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => 1,
            'section_number' => 'L02',
            'activity' => 'Lecture',
            'day_of_week' => 'Wednesday',
            'start_time' => '14:00:00',
            'end_time' => '16:00:00',
            'venue' => 'Hall B',
        ]);

        // Test compact mode
        $compactResponse = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1, // compact
            ]
        ]);

        $compactResponse->assertStatus(201);

        // Test spaced out mode
        $spacedResponse = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 2, // spaced_out
            ]
        ]);

        $spacedResponse->assertStatus(201);

        // Both should succeed (algorithm should handle both styles)
        $this->assertTrue(true);
    }

    /** @test */
    public function it_validates_required_preferences()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Test missing subjects - should fail validation
        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1,
            ]
        ]);

        $response->assertStatus(422); // Should fail validation

        // Test invalid time range
        $subject = Subject::factory()->create();
        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '18:00',
                'end_time' => '08:00', // End before start
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1,
            ]
        ]);

        $response->assertStatus(422); // Should fail validation
    }

    /** @test */
    public function it_handles_no_available_sections_gracefully()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $subject = Subject::factory()->create(['name' => 'No Sections', 'code' => 'NONE101']);
        // Don't create any sections for this subject

        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1,
            ]
        ]);

        $response->assertStatus(422);
        // Should return some kind of error message (exact format may vary)
        $this->assertTrue(
            $response->getStatusCode() === 422,
            'Should return validation error when no sections are available'
        );
    }

    /** @test */
    public function it_deactivates_previous_timetables_when_generating_new_one()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        // Create an existing active timetable
        $oldTimetable = GeneratedTimetable::factory()->create([
            'user_id' => $user->id,
            'active' => true,
        ]);

        $subject = Subject::factory()->create(['name' => 'Test Subject', 'code' => 'TEST101']);
        Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => 1,
            'section_number' => 'L01',
            'activity' => 'Lecture',
            'day_of_week' => 'Monday',
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'venue' => 'Hall A',
        ]);

        $response = $this->postJson('/api/generate-timetable', [
            'preferences' => [
                'subjects' => [$subject->id],
                'days' => [1, 2, 3, 4, 5],
                'start_time' => '08:00',
                'end_time' => '18:00',
                'enforce_ties' => 'no',
                'lecturers' => [],
                'mode' => 1,
            ]
        ]);

        $response->assertStatus(201);

        // Check that old timetable is deactivated
        $this->assertDatabaseHas('generated_timetables', [
            'id' => $oldTimetable->id,
            'active' => false,
        ]);

        // Check that new timetable is active
        $this->assertDatabaseHas('generated_timetables', [
            'user_id' => $user->id,
            'active' => true,
        ]);

        // Should have exactly one active timetable
        $activeTimetables = GeneratedTimetable::where('user_id', $user->id)
            ->where('active', true)
            ->count();
        $this->assertEquals(1, $activeTimetables);
    }
}
