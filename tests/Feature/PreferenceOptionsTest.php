<?php

namespace Tests\Feature;

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
        $lecturer1 = User::factory()->create(['name' => 'Dr. Smith']);
        $lecturer2 = User::factory()->create(['name' => 'Dr. Jones']);

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
}
