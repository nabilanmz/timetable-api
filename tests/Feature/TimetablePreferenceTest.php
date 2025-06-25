<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TimetablePreferenceTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function a_user_can_save_their_timetable_preferences()
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $preferences = [
            'preferences' => [
                'subjects' => ['Math', 'Physics'],
                'available_days' => ['Monday', 'Tuesday'],
                'available_times' => ['9:00-10:00', '11:00-12:00'],
                'preferred_lecturers' => ['Dr. Smith', 'Dr. Jones'],
            ]
        ];

        $response = $this->postJson('/api/timetable-preferences', $preferences);

        $response->assertStatus(201);
        $this->assertDatabaseHas('timetable_preferences', [
            'user_id' => $user->id,
            'preferences' => json_encode($preferences['preferences']),
        ]);
    }
}
