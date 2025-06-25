<?php

namespace Tests\Feature;

use App\Models\Day;
use App\Models\Lecturer;
use App\Models\Subject;
use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Timetable;
use App\Models\TimetableEntry;
use Laravel\Sanctum\Sanctum;

class TimetableTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        Sanctum::actingAs(
            $this->user,
            ['*']
        );
    }

    public function test_can_create_timetable_with_entries()
    {
        $subject = Subject::factory()->create();
        $lecturer = Lecturer::factory()->create();
        $day = Day::factory()->create();
        $timeSlot = TimeSlot::factory()->create();

        $timetableData = [
            'name' => 'Test Timetable',
            'description' => 'This is a test timetable.',
            'entries' => [
                [
                    'subject_id' => $subject->id,
                    'lecturer_id' => $lecturer->id,
                    'day_id' => $day->id,
                    'time_slot_id' => $timeSlot->id,
                ]
            ]
        ];

        $response = $this->postJson('/api/timetables', $timetableData);

        $response->assertStatus(201)
            ->assertJsonFragment(['name' => 'Test Timetable']);

        $this->assertDatabaseHas('timetables', ['name' => 'Test Timetable', 'created_by' => $this->user->id]);
        $this->assertDatabaseHas('timetable_entries', ['subject_id' => $subject->id]);
    }

    public function test_can_get_all_timetables_for_user()
    {
        Timetable::factory()->count(3)->create(['created_by' => $this->user->id]);
        Timetable::factory()->count(2)->create(); // Timetables for another user

        $response = $this->getJson('/api/timetables');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_can_get_a_specific_timetable()
    {
        $timetable = Timetable::factory()->create(['created_by' => $this->user->id]);
        TimetableEntry::factory()->create(['timetable_id' => $timetable->id]);

        $response = $this->getJson("/api/timetables/{$timetable->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => $timetable->name])
            ->assertJsonStructure(['timetable_entries']);
    }

    public function test_cannot_get_a_timetable_of_another_user()
    {
        $otherUser = User::factory()->create();
        $timetable = Timetable::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->getJson("/api/timetables/{$timetable->id}");

        $response->assertStatus(403);
    }

    public function test_can_update_a_timetable()
    {
        $timetable = Timetable::factory()->create(['created_by' => $this->user->id]);
        $subject = Subject::factory()->create();
        $lecturer = Lecturer::factory()->create();
        $day = Day::factory()->create();
        $timeSlot = TimeSlot::factory()->create();

        $updateData = [
            'name' => 'Updated Timetable Name',
            'entries' => [
                [
                    'subject_id' => $subject->id,
                    'lecturer_id' => $lecturer->id,
                    'day_id' => $day->id,
                    'time_slot_id' => $timeSlot->id,
                ]
            ]
        ];

        $response = $this->putJson("/api/timetables/{$timetable->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['name' => 'Updated Timetable Name']);

        $this->assertDatabaseHas('timetables', ['name' => 'Updated Timetable Name']);
        $this->assertDatabaseHas('timetable_entries', ['subject_id' => $subject->id]);
    }
    
    public function test_cannot_update_a_timetable_of_another_user()
    {
        $otherUser = User::factory()->create();
        $timetable = Timetable::factory()->create(['created_by' => $otherUser->id]);

        $updateData = [
            'name' => 'Updated Timetable Name',
            'entries' => []
        ];

        $response = $this->putJson("/api/timetables/{$timetable->id}", $updateData);

        $response->assertStatus(403);
    }

    public function test_can_delete_a_timetable()
    {
        $timetable = Timetable::factory()->create(['created_by' => $this->user->id]);

        $response = $this->deleteJson("/api/timetables/{$timetable->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('timetables', ['id' => $timetable->id]);
    }

    public function test_cannot_delete_a_timetable_of_another_user()
    {
        $otherUser = User::factory()->create();
        $timetable = Timetable::factory()->create(['created_by' => $otherUser->id]);

        $response = $this->deleteJson("/api/timetables/{$timetable->id}");

        $response->assertStatus(403);
    }
}
