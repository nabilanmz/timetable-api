<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Subject;
use Laravel\Sanctum\Sanctum;

class SubjectTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Sanctum::actingAs(
            User::factory()->create(),
            ['*']
        );
    }

    /**
     * A basic feature test example.
     */
    public function test_example(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    public function test_can_create_subject()
    {
        $subjectData = [
            'name' => 'Test Subject',
            'code' => 'TS101',
            'description' => 'This is a test subject.',
        ];

        $response = $this->postJson('/api/subjects', $subjectData);

        $response->assertStatus(201)
                 ->assertJsonFragment($subjectData);

        $this->assertDatabaseHas('subjects', $subjectData);
    }

    public function test_can_get_all_subjects()
    {
        Subject::factory()->count(3)->create();

        $response = $this->getJson('/api/subjects');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_can_update_subject()
    {
        $subject = Subject::factory()->create();

        $updateData = [
            'name' => 'Updated Subject Name',
        ];

        $response = $this->putJson("/api/subjects/{$subject->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('subjects', $updateData);
    }

    public function test_can_delete_subject()
    {
        $subject = Subject::factory()->create();

        $response = $this->deleteJson("/api/subjects/{$subject->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('subjects', ['id' => $subject->id]);
    }
}
