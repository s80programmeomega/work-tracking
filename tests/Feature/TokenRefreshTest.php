<?php

namespace Tests\Feature;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TokenRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_refresh_returns_new_token_and_200(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/refresh');

        $response->assertOk()
            ->assertJsonPath('data.token_type', 'Bearer')
            ->assertJsonStructure(['data' => ['token', 'token_type', 'expires_at']]);

        $this->assertNotSame($token, $response->json('data.token'));
    }

    public function test_refresh_preserves_remember_me_duration(): void
    {
        $user = User::factory()->create();

        // Token "Se souvenir" — expire dans 15 jours
        $token = $user->createToken('auth_token', ['*'], now()->addDays(15))->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/refresh');

        $response->assertOk();

        $expiresAt = $response->json('data.expires_at');
        $expiry = Carbon::parse($expiresAt);

        $this->assertGreaterThan(now()->addDays(14), $expiry);
    }

    public function test_refresh_gives_24h_for_normal_login(): void
    {
        $user = User::factory()->create();

        // Token normal — expire dans 24 h
        $token = $user->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;

        $response = $this->withToken($token)->postJson('/api/auth/refresh');

        $response->assertOk();

        $expiresAt = $response->json('data.expires_at');
        $expiry = Carbon::parse($expiresAt);

        // Doit expirer entre 23 h et 25 h à partir de maintenant
        $this->assertGreaterThan(now()->addHours(23), $expiry);
        $this->assertLessThan(now()->addHours(25), $expiry);
    }

    public function test_refresh_with_expired_token_returns_401(): void
    {
        $user = User::factory()->create();

        // Token expiré (expiry dans le passé)
        $result = $user->createToken('auth_token', ['*'], now()->subHour());
        $token = $result->plainTextToken;

        // Forcer l'expiration en base
        $result->accessToken->update(['expires_at' => now()->subHour()]);

        $this->withToken($token)->postJson('/api/auth/refresh')->assertUnauthorized();
    }
}
