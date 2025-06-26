<?php

namespace Tests\Feature;

use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SectionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        Sanctum::actingAs($user);
    }

    /** @test */
    public function it_can_list_all_sections_with_key_details()
    {
        $subject = Subject::factory()->create(['name' => 'Test Subject', 'code' => 'TEST101']);
        $lecturer = User::factory()->create(['name' => 'Dr. Test']);
        $section = Section::factory()->create([
            'subject_id' => $subject->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 1,
            'capacity' => 20,
        ]);
        Enrollment::factory()->count(15)->create(['section_id' => $section->id]);

        $response = $this->getJson('/api/sections');

        $response->assertStatus(200)
            ->assertJsonFragment([
                'section_number' => '1',
                'capacity' => 20,
                'enrolled_students' => 15,
                'status' => 'Available',
            ])
            ->assertJsonPath('0.subject.name', 'Test Subject')
            ->assertJsonPath('0.subject.code', 'TEST101')
            ->assertJsonPath('0.lecturer.name', 'Dr. Test');
    }

    /** @test */
    public function it_can_filter_sections_by_subject_name()
    {
        $subject1 = Subject::factory()->create(['name' => 'Alpha Course']);
        $subject2 = Subject::factory()->create(['name' => 'Beta Course']);
        Section::factory()->create(['subject_id' => $subject1->id]);
        Section::factory()->create(['subject_id' => $subject2->id]);

        $response = $this->getJson('/api/sections?search=Alpha');

        $response->assertStatus(200)
            ->assertJsonCount(1)
            ->assertJsonPath('0.subject.name', 'Alpha Course');
    }

    /** @test */
    public function it_shows_full_status_when_capacity_is_reached()
    {
        $section = Section::factory()->create(['capacity' => 10]);
        Enrollment::factory()->count(10)->create(['section_id' => $section->id]);

        $response = $this->getJson('/api/sections');

        $response->assertStatus(200)
            ->assertJsonFragment(['status' => 'Full']);
    }

    /** @test */
    public function it_can_show_a_single_section_details()
    {
        $section = Section::factory()->create();

        $response = $this->getJson('/api/sections/' . $section->id);

        $response->assertStatus(200)
            ->assertJsonFragment(['id' => $section->id]);
    }

    /** @test */
    public function a_non_admin_cannot_create_a_section()
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);

        $subject = Subject::factory()->create();
        $lecturer = User::factory()->create();

        $response = $this->postJson('/api/sections', [
            'subject_id' => $subject->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 1,
            'capacity' => 30,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function an_admin_can_create_a_section()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);

        $subject = Subject::factory()->create();
        $lecturer = User::factory()->create();

        $response = $this->postJson('/api/sections', [
            'subject_id' => $subject->id,
            'lecturer_id' => $lecturer->id,
            'section_number' => 1,
            'capacity' => 30,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00',
            'day_of_week' => 'Monday',
            'venue' => 'Room 101',
        ]);

        $response->assertStatus(201)
            ->assertJsonFragment(['section_number' => 1]);

        $this->assertDatabaseHas('sections', ['section_number' => 1]);
    }

    /** @test */
    public function a_non_admin_cannot_update_a_section()
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);
        $section = Section::factory()->create();

        $response = $this->putJson('/api/sections/' . $section->id, [
            'capacity' => 50,
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function an_admin_can_update_a_section()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);
        $section = Section::factory()->create([
            'capacity' => 30,
            'start_time' => '09:00:00',
            'end_time' => '10:00:00',
        ]);

        $response = $this->putJson('/api/sections/' . $section->id, [
            'subject_id' => $section->subject_id,
            'lecturer_id' => $section->lecturer_id,
            'section_number' => $section->section_number,
            'capacity' => 50,
            'start_time' => '09:00:00',
            'end_time' => '11:00:00', // Ensure end_time is after start_time
            'day_of_week' => $section->day_of_week,
            'venue' => $section->venue,
        ]);

        $response->assertStatus(200)
            ->assertJsonFragment(['capacity' => 50]);

        $this->assertDatabaseHas('sections', ['id' => $section->id, 'capacity' => 50, 'end_time' => '11:00:00']);
    }

    /** @test */
    public function a_non_admin_cannot_delete_a_section()
    {
        $user = User::factory()->create(['is_admin' => false]);
        Sanctum::actingAs($user);
        $section = Section::factory()->create();

        $response = $this->deleteJson('/api/sections/' . $section->id);

        $response->assertStatus(403);
    }

    /** @test */
    public function an_admin_can_delete_a_section()
    {
        $admin = User::factory()->create(['is_admin' => true]);
        Sanctum::actingAs($admin);
        $section = Section::factory()->create();

        $response = $this->deleteJson('/api/sections/' . $section->id);

        $response->assertStatus(204);

        $this->assertDatabaseMissing('sections', ['id' => $section->id]);
    }
}
