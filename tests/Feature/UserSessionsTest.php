<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 11C — sessions endpoint.
 *
 * GET /api/users/sessions returns the current user's active Sanctum tokens.
 * Since AuthService::issueToken() revokes all previous auth_token tokens on
 * login, there is at most one active token at any time.
 */
class UserSessionsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_unauthenticated_request_returns_401(): void
    {
        $response = $this->getJson('/api/users/sessions');

        $response->assertUnauthorized();
    }

    public function test_authenticated_user_can_fetch_their_sessions(): void
    {
        $user = User::factory()->create();
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/users/sessions');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
            ])
            ->assertJson(['success' => true]);
    }

    public function test_sessions_list_contains_current_token(): void
    {
        $user = User::factory()->create();
        // Émet un vrai token Sanctum pour que currentAccessToken() soit défini.
        $token = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($token)->getJson('/api/users/sessions');

        $response->assertOk();
        $data = $response->json('data');

        $this->assertNotEmpty($data, 'La liste des sessions ne doit pas être vide');

        $currentSession = collect($data)->firstWhere('is_current', true);
        $this->assertNotNull($currentSession, 'La session courante doit être marquée is_current = true');
        $this->assertSame('auth_token', $currentSession['name']);
        $this->assertArrayHasKey('created_at', $currentSession);
        $this->assertArrayHasKey('expires_at', $currentSession);
    }

    public function test_sessions_list_does_not_return_other_users_tokens(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        // Crée un token pour userB
        $userB->createToken('auth_token');

        // Authentifie userA
        Sanctum::actingAs($userA);

        $response = $this->getJson('/api/users/sessions');

        $response->assertOk();
        $data = $response->json('data');

        // userA n'a pas de token actif → liste vide (pas de fuite vers userB)
        $this->assertEmpty($data, 'Les tokens des autres utilisateurs ne doivent pas apparaître');
    }

    public function test_sessions_only_returns_auth_token_named_tokens(): void
    {
        $user = User::factory()->create();

        // Crée un token avec un nom différent (ex. clé API)
        $user->createToken('api_key');
        $authToken = $user->createToken('auth_token')->plainTextToken;

        $response = $this->withToken($authToken)->getJson('/api/users/sessions');

        $response->assertOk();
        $data = $response->json('data');

        // Seul le token "auth_token" doit apparaître
        $this->assertCount(1, $data);
        $this->assertSame('auth_token', $data[0]['name']);
    }
}
