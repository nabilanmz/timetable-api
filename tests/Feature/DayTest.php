<?php

namespace Tests\Feature;

use App\Models\Day;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DayTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_all_days()
    {
        Day::factory()->count(3)->create();

        $response = $this->getJson('/api/days');

        $response->assertOk();
        $response->assertJsonCount(3);
    }
}
