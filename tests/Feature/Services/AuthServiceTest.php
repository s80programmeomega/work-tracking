<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\User;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre AuthService : register, login, logout.
 */
class AuthServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->authService = app(AuthService::class);
    }

    // =========================================================================
    // REGISTER
    // =========================================================================

    /** @test */
    public function register_creates_user_with_utilisateur_role(): void
    {
        $user = $this->authService->register([
            'nom' => 'Dupont',
            'prenom' => 'Jean',
            'email' => 'jean.dupont@example.com',
            'password' => 'password123',
        ]);

        $this->assertInstanceOf(User::class, $user);
        $this->assertDatabaseHas('users', ['email' => 'jean.dupont@example.com']);
        $this->assertDatabaseHas('model_has_roles', ['model_id' => $user->id, 'model_type' => User::class]);
    }

    /** @test */
    public function register_builds_nom_complet_from_prenom_and_nom(): void
    {
        $user = $this->authService->register([
            'nom' => 'Martin',
            'prenom' => 'Marie',
            'email' => 'marie.martin@example.com',
            'password' => 'password123',
        ]);

        $this->assertStringContainsString('Martin', $user->nom_complet);
    }

    // =========================================================================
    // LOGIN
    // =========================================================================

    /** @test */
    public function login_returns_token_for_valid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('secret123'),
            'is_active' => true,
        ]);

        $result = $this->authService->login([
            'email' => 'user@example.com',
            'password' => 'secret123',
        ]);

        // Depuis l'introduction du MFA, login() retourne uniquement l'utilisateur.
        // Le token Sanctum est émis séparément via issueToken() (ou après challenge MFA).
        $this->assertArrayHasKey('user', $result);
        $this->assertArrayHasKey('requires_mfa', $result);
        $this->assertFalse($result['requires_mfa']);
        $this->assertArrayNotHasKey('token', $result);
    }

    /** @test */
    public function login_throws_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'user@example.com',
            'password' => bcrypt('correct'),
            'is_active' => true,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Invalid credentials');

        $this->authService->login([
            'email' => 'user@example.com',
            'password' => 'wrong',
        ]);
    }

    /** @test */
    public function login_throws_for_inactive_account(): void
    {
        User::factory()->create([
            'email' => 'inactive@example.com',
            'password' => bcrypt('password'),
            'is_active' => false,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Account is inactive');

        $this->authService->login([
            'email' => 'inactive@example.com',
            'password' => 'password',
        ]);
    }

    // =========================================================================
    // LOGOUT (via HTTP to ensure Sanctum currentAccessToken is set)
    // =========================================================================

    /** @test */
    public function logout_via_http_revokes_sanctum_token(): void
    {
        $user = User::factory()->create(['is_active' => true]);
        $plainToken = $user->createToken('test')->plainTextToken;

        $this->withHeaders(['Authorization' => "Bearer {$plainToken}"])
            ->postJson('/api/auth/logout')
            ->assertOk();

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }
}
