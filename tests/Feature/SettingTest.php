<?php

namespace Tests\Feature;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class SettingTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function it_can_fetch_all_settings()
    {
        Setting::factory()->count(3)->create();

        $response = $this->getJson('/api/settings');

        $response->assertOk();
        $response->assertJsonCount(3);
    }

    /** @test */
    public function it_can_update_settings()
    {
        Sanctum::actingAs(User::factory()->create());
        $settings = Setting::factory()->count(2)->create();

        $updatedSettings = [
            [
                'key' => $settings[0]->key,
                'value' => 'new-value-1',
            ],
            [
                'key' => $settings[1]->key,
                'value' => 'new-value-2',
            ],
        ];

        $response = $this->putJson('/api/settings', $updatedSettings);

        $response->assertOk();
        $this->assertDatabaseHas('settings', ['key' => $settings[0]->key, 'value' => 'new-value-1']);
        $this->assertDatabaseHas('settings', ['key' => $settings[1]->key, 'value' => 'new-value-2']);
    }
}
