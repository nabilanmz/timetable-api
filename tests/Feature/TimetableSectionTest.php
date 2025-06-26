<?php

namespace Tests\Feature;

use App\Models\Section;
use App\Models\Timetable;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimetableSectionTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_add_section_to_timetable()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $timetable = Timetable::factory()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($admin)->postJson("/api/timetables/{$timetable->id}/sections", [
            'section_id' => $section->id,
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('section_timetable', [
            'timetable_id' => $timetable->id,
            'section_id' => $section->id,
        ]);
    }

    public function test_admin_can_remove_section_from_timetable()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        $timetable = Timetable::factory()->create();
        $section = Section::factory()->create();
        $timetable->sections()->attach($section->id);

        $response = $this->actingAs($admin)->deleteJson("/api/timetables/{$timetable->id}/sections/{$section->id}");

        $response->assertStatus(200);
        $this->assertDatabaseMissing('section_timetable', [
            'timetable_id' => $timetable->id,
            'section_id' => $section->id,
        ]);
    }

    public function test_non_admin_cannot_add_section_to_timetable()
    {
        $user = User::factory()->create();
        $timetable = Timetable::factory()->create();
        $section = Section::factory()->create();

        $response = $this->actingAs($user)->postJson("/api/timetables/{$timetable->id}/sections", [
            'section_id' => $section->id,
        ]);

        $response->assertStatus(403);
    }

    public function test_non_admin_cannot_remove_section_from_timetable()
    {
        $user = User::factory()->create();
        $timetable = Timetable::factory()->create();
        $section = Section::factory()->create();
        $timetable->sections()->attach($section->id);

        $response = $this->actingAs($user)->deleteJson("/api/timetables/{$timetable->id}/sections/{$section->id}");

        $response->assertStatus(403);
    }
}
