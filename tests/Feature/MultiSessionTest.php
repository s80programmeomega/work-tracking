<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\SessionRevokedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\PersonalAccessToken;
use Tests\TestCase;

class MultiSessionTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_on_two_devices_keeps_both_tokens(): void
    {
        $user = User::factory()->create();

        $user->createToken('auth_token', ['*'], now()->addHours(24));
        $user->createToken('auth_token', ['*'], now()->addHours(24));

        $this->assertSame(2, $user->tokens()->where('name', 'auth_token')->count());
    }

    public function test_logout_revokes_only_current_token(): void
    {
        $user = User::factory()->create();

        $resultA = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $resultB = $user->createToken('auth_token', ['*'], now()->addHours(24));

        $tokenA = $resultA->plainTextToken;
        $idB = $resultB->accessToken->id;

        $this->withToken($tokenA)->postJson('/api/auth/logout')->assertOk();

        // Token A doit être supprimé, token B doit survivre
        $this->assertNull(PersonalAccessToken::find($resultA->accessToken->id));
        $this->assertNotNull(PersonalAccessToken::find($idB));
    }

    public function test_logout_all_revokes_all_tokens(): void
    {
        $user = User::factory()->create();

        $resultA = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $resultB = $user->createToken('auth_token', ['*'], now()->addHours(24));

        $tokenA = $resultA->plainTextToken;
        $idA = $resultA->accessToken->id;
        $idB = $resultB->accessToken->id;

        $this->withToken($tokenA)->postJson('/api/auth/logout-all')->assertOk();

        $this->assertNull(PersonalAccessToken::find($idA));
        $this->assertNull(PersonalAccessToken::find($idB));
    }

    public function test_revoke_other_session_works(): void
    {
        $user = User::factory()->create();

        $resultA = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $resultB = $user->createToken('auth_token', ['*'], now()->addHours(24));

        $tokenA = $resultA->plainTextToken;
        $idA = $resultA->accessToken->id;
        $idB = $resultB->accessToken->id;

        $this->withToken($tokenA)
            ->deleteJson("/api/auth/sessions/{$idB}")
            ->assertOk();

        $this->assertNull(PersonalAccessToken::find($idB));
        $this->assertNotNull(PersonalAccessToken::find($idA));
    }

    public function test_revoke_current_session_equivalent_to_logout(): void
    {
        $user = User::factory()->create();

        $result = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $token = $result->plainTextToken;
        $tokenId = $result->accessToken->id;

        $this->withToken($token)
            ->deleteJson("/api/auth/sessions/{$tokenId}")
            ->assertOk()
            ->assertJsonFragment(['is_current' => true]);

        $this->assertNull(PersonalAccessToken::find($tokenId));
    }

    public function test_cannot_revoke_another_users_session(): void
    {
        $userA = User::factory()->create();
        $userB = User::factory()->create();

        $tokenA = $userA->createToken('auth_token', ['*'], now()->addHours(24))->plainTextToken;
        $idB = $userB->createToken('auth_token', ['*'], now()->addHours(24))->accessToken->id;

        $this->withToken($tokenA)
            ->deleteJson("/api/auth/sessions/{$idB}")
            ->assertNotFound();
    }

    public function test_revoke_other_session_sends_notification(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $resultA = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $resultB = $user->createToken('auth_token', ['*'], now()->addHours(24));

        $tokenA = $resultA->plainTextToken;
        $idB = $resultB->accessToken->id;

        $this->withToken($tokenA)
            ->deleteJson("/api/auth/sessions/{$idB}")
            ->assertOk();

        Notification::assertSentTo($user, SessionRevokedNotification::class);
    }
}
