<?php

namespace Tests\Feature;

use App\Models\Lecturer;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PreferenceOptionsTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_returns_available_preference_options()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $subject1 = Subject::factory()->create(['name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['name' => 'Physics']);
        $lecturer1 = Lecturer::factory()->create(['name' => 'Dr. Smith']);
        $lecturer2 = Lecturer::factory()->create(['name' => 'Dr. Jones']);

        Section::factory()->create(['subject_id' => $subject1->id, 'lecturer_id' => $lecturer1->id]);
        Section::factory()->create(['subject_id' => $subject2->id, 'lecturer_id' => $lecturer2->id]);
        Section::factory()->create(['subject_id' => $subject1->id, 'lecturer_id' => $lecturer2->id]);

        $response = $this->getJson('/api/preference-options');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'subjects',
                'lecturers',
            ])
            ->assertJsonCount(2, 'subjects')
            ->assertJsonCount(2, 'lecturers')
            ->assertJsonFragment(['name' => 'Mathematics'])
            ->assertJsonFragment(['name' => 'Physics'])
            ->assertJsonFragment(['name' => 'Dr. Smith'])
            ->assertJsonFragment(['name' => 'Dr. Jones']);
    }

    /** @test */
    public function it_returns_available_timeslots_for_selected_subjects()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $subject1 = Subject::factory()->create(['name' => 'Mathematics']);
        $subject2 = Subject::factory()->create(['name' => 'Physics']);
        $subject3 = Subject::factory()->create(['name' => 'Chemistry']);

        // Create sections with different time slots
        Section::factory()->create([
            'subject_id' => $subject1->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00'
        ]);
        Section::factory()->create([
            'subject_id' => $subject1->id,
            'start_time' => '11:00:00',
            'end_time' => '12:00:00'
        ]);
        Section::factory()->create([
            'subject_id' => $subject2->id,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00'
        ]);
        Section::factory()->create([
            'subject_id' => $subject2->id,
            'start_time' => '14:00:00',
            'end_time' => '15:00:00'
        ]);
        // Subject 3 section - shouldn't appear in filtered results
        Section::factory()->create([
            'subject_id' => $subject3->id,
            'start_time' => '16:00:00',
            'end_time' => '17:00:00'
        ]);

        $response = $this->getJson("/api/available-timeslots?subject_ids={$subject1->id},{$subject2->id}");

        $response->assertStatus(200)
            ->assertJsonStructure([
                '*' => [
                    'id',
                    'start_time',
                    'end_time'
                ]
            ])
            ->assertJsonCount(3) // Should only return 3 unique time slots
            ->assertJsonFragment(['start_time' => '09:00:00', 'end_time' => '10:00:00'])
            ->assertJsonFragment(['start_time' => '11:00:00', 'end_time' => '12:00:00'])
            ->assertJsonFragment(['start_time' => '14:00:00', 'end_time' => '15:00:00']);
        
        // Should not contain the chemistry time slot
        $response->assertJsonMissing(['start_time' => '16:00:00', 'end_time' => '17:00:00']);
    }

    /** @test */
    public function it_requires_subject_ids_parameter()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/available-timeslots');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'subject_ids parameter is required',
                'message' => 'Please provide a comma-separated list of subject IDs'
            ]);
    }

    /** @test */
    public function it_validates_subject_ids_format()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/available-timeslots?subject_ids=invalid,abc');

        $response->assertStatus(400)
            ->assertJson([
                'error' => 'Invalid subject_ids format',
                'message' => 'subject_ids must be a comma-separated list of numeric IDs'
            ]);
    }

    /** @test */
    public function it_returns_empty_array_for_nonexistent_subjects()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/available-timeslots?subject_ids=999999');

        $response->assertStatus(200)
            ->assertJson([]);
    }

    /** @test */
    public function it_requires_authentication_for_available_timeslots()
    {
        $response = $this->getJson('/api/available-timeslots?subject_ids=1,2,3');

        $response->assertStatus(401)
            ->assertJson([
                'message' => 'Unauthenticated.',
                'error' => 'Authentication required'
            ]);
    }
}
