<?php

declare(strict_types=1);

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PreferredLocaleTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function user_can_save_language_preference_via_api(): void
    {
        $user = User::factory()->create(['language' => 'fr']);

        $response = $this->actingAs($user)->putJson('/api/users/profile', [
            'language' => 'en',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals('en', $user->language);
    }

    /** @test */
    public function invalid_language_code_is_rejected(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/users/profile', [
            'language' => 'xx',
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['language']);
    }

    /** @test */
    public function user_can_save_timezone_preference(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->putJson('/api/users/profile', [
            'timezone' => 'America/New_York',
        ]);

        $response->assertOk();
        $user->refresh();
        $this->assertEquals('America/New_York', $user->timezone);
    }
}
