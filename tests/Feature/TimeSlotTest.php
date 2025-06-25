<?php

namespace Tests\Feature;

use App\Models\TimeSlot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TimeSlotTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_all_time_slots()
    {
        TimeSlot::factory()->count(3)->create();

        $response = $this->getJson('/api/times');

        $response->assertOk();
        $response->assertJsonCount(3);
    }
}
