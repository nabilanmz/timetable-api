<?php

namespace Tests\Feature;

use App\Models\GeneratedTimetable;
use App\Models\TimetableChangeRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimetableChangeRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($this->user);
    }

    public function test_a_user_can_submit_a_change_request()
    {
        $generatedTimetable = GeneratedTimetable::factory()->create(['user_id' => $this->user->id]);

        $requestData = [
            'generated_timetable_id' => $generatedTimetable->id,
            'message' => 'I would like to request a change to my timetable.',
        ];

        $response = $this->postJson('/api/timetable-change-requests', $requestData);

        $response->assertStatus(201)
            ->assertJsonFragment(['message' => 'I would like to request a change to my timetable.']);

        $this->assertDatabaseHas('timetable_change_requests', [
            'user_id' => $this->user->id,
            'generated_timetable_id' => $generatedTimetable->id,
        ]);
    }

    public function test_an_admin_can_view_all_change_requests()
    {
        Sanctum::actingAs($this->admin);
        TimetableChangeRequest::factory()->count(3)->create();

        $response = $this->getJson('/api/timetable-change-requests');

        $response->assertStatus(200)
            ->assertJsonCount(3);
    }

    public function test_a_non_admin_cannot_view_all_change_requests()
    {
        TimetableChangeRequest::factory()->count(3)->create();

        $response = $this->getJson('/api/timetable-change-requests');

        $response->assertStatus(403);
    }

    public function test_an_admin_can_approve_a_change_request()
    {
        Sanctum::actingAs($this->admin);
        $changeRequest = TimetableChangeRequest::factory()->create();

        $updateData = [
            'status' => 'approved',
            'admin_response' => 'Your request has been approved.',
        ];

        $response = $this->putJson("/api/timetable-change-requests/{$changeRequest->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'approved']);

        $this->assertDatabaseHas('timetable_change_requests', [
            'id' => $changeRequest->id,
            'status' => 'approved',
        ]);
    }

    public function test_an_admin_can_reject_a_change_request()
    {
        Sanctum::actingAs($this->admin);
        $changeRequest = TimetableChangeRequest::factory()->create();

        $updateData = [
            'status' => 'rejected',
            'admin_response' => 'Sorry, we cannot accommodate your request at this time.',
        ];

        $response = $this->putJson("/api/timetable-change-requests/{$changeRequest->id}", $updateData);

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'rejected']);

        $this->assertDatabaseHas('timetable_change_requests', [
            'id' => $changeRequest->id,
            'status' => 'rejected',
        ]);
    }

    public function test_a_user_can_view_their_own_change_request()
    {
        $changeRequest = TimetableChangeRequest::factory()->create(['user_id' => $this->user->id]);

        $response = $this->getJson("/api/timetable-change-requests/{$changeRequest->id}");

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $changeRequest->id]);
    }

    public function test_a_user_cannot_view_another_users_change_request()
    {
        $otherUser = User::factory()->create();
        $changeRequest = TimetableChangeRequest::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->getJson("/api/timetable-change-requests/{$changeRequest->id}");

        $response->assertStatus(403);
    }
}
