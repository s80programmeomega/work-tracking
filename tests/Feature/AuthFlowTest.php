<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\Role;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests for authentication flows:
 * - Registration creates utilisateur role, no workspace
 * - Workspace creation assigns directeur role
 */
class AuthFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** @test */
    public function new_user_gets_utilisateur_role_on_registration(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'nom'                  => 'Test',
            'prenom'               => 'User',
            'email'                => 'test@example.com',
            'password'             => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNotNull($user);
        // Clear Spatie's static permission cache before checking roles
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->assertTrue($user->hasRole(Role::UTILISATEUR->value, 'web'));
    }

    /** @test */
    public function new_user_has_no_workspace_after_registration(): void
    {
        $this->postJson('/api/auth/register', [
            'nom'                  => 'Test',
            'prenom'               => 'User',
            'email'                => 'test@example.com',
            'password'             => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $user = User::where('email', 'test@example.com')->first();
        $this->assertNull($user->current_workspace_id);
        $this->assertEquals(0, Workspace::where('owner_id', $user->id)->count());
    }

    /** @test */
    public function user_becomes_directeur_after_creating_workspace(): void
    {
        // Create and login a utilisateur
        $user = User::factory()->create();
        $user->assignRole(Role::UTILISATEUR->value);

        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/workspaces', [
                'nom'         => 'My Workspace',
                'description' => 'Test',
            ]);

        $response->assertStatus(201);

        $user->refresh();
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();
        $this->assertTrue($user->hasRole(Role::DIRECTEUR->value));
        $this->assertNotNull($user->current_workspace_id);
    }

    /** @test */
    public function unauthenticated_user_cannot_access_protected_routes(): void
    {
        $this->getJson('/api/workspaces')->assertStatus(401);
        $this->getJson('/api/auth/me')->assertStatus(401);
    }
}
