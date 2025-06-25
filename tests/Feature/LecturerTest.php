<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Lecturer;
use Laravel\Sanctum\Sanctum;

class LecturerTest extends TestCase
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

    public function test_can_create_lecturer()
    {
        $lecturerData = [
            'name' => 'Dr. John Doe',
            'email' => 'john.doe@example.com',
            'phone' => '1234567890',
            'department' => 'Computer Science',
        ];

        $response = $this->postJson('/api/lecturers', $lecturerData);

        $response->assertStatus(201)
                 ->assertJsonFragment($lecturerData);

        $this->assertDatabaseHas('lecturers', $lecturerData);
    }

    public function test_can_get_all_lecturers()
    {
        Lecturer::factory()->count(3)->create();

        $response = $this->getJson('/api/lecturers');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_can_update_lecturer()
    {
        $lecturer = Lecturer::factory()->create();

        $updateData = [
            'name' => 'Updated Lecturer Name',
        ];

        $response = $this->putJson("/api/lecturers/{$lecturer->id}", $updateData);

        $response->assertStatus(200)
                 ->assertJsonFragment($updateData);

        $this->assertDatabaseHas('lecturers', $updateData);
    }

    public function test_can_delete_lecturer()
    {
        $lecturer = Lecturer::factory()->create();

        $response = $this->deleteJson("/api/lecturers/{$lecturer->id}");

        $response->assertStatus(204);

        $this->assertDatabaseMissing('lecturers', ['id' => $lecturer->id]);
    }
}
