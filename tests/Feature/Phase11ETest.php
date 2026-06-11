<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phase 11E — Activity tabs + Users management.
 *
 * Couvre :
 *  - GET /api/users/{user}/activity (B5)
 *  - GET /api/workspaces/{workspace}/users (B6b)
 */
class Phase11ETest extends TestCase
{
    use RefreshDatabase;

    // ── Helpers ───────────────────────────────────────────────────────────────

    private function makeSuperAdmin(): User
    {
        $this->seed(RolePermissionSeeder::class);
        $user = User::factory()->create(['is_super_admin' => true]);
        $user->forceFill(['role' => 'super_admin'])->save();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function makeOwnerWithWorkspace(): array
    {
        $this->seed(RolePermissionSeeder::class);
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }

    private function makeWorkspaceMember(Workspace $workspace, string $role = 'collaborateur'): User
    {
        $member = User::factory()->create();
        $roleModel = Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        $workspace->members()->attach($member->id, ['role_id' => $roleModel->id]);

        return $member;
    }

    // ── B5: GET /api/users/{user}/activity ────────────────────────────────────

    public function test_user_can_get_own_activity(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->getJson("/api/users/{$superAdmin->id}/activity");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_user_cannot_get_other_users_activity(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $other = $this->makeWorkspaceMember($workspace);

        $response = $this->actingAs($other, 'sanctum')
            ->getJson("/api/users/{$owner->id}/activity");

        $response->assertStatus(403);
    }

    public function test_super_admin_can_get_any_user_activity(): void
    {
        $superAdmin = $this->makeSuperAdmin();
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();

        $response = $this->actingAs($superAdmin, 'sanctum')
            ->getJson("/api/users/{$owner->id}/activity");

        $response->assertStatus(200)
            ->assertJsonStructure(['success', 'data']);
    }

    public function test_unauthenticated_cannot_access_activity_endpoint(): void
    {
        $user = User::factory()->create();

        $this->getJson("/api/users/{$user->id}/activity")
            ->assertStatus(401);
    }

    // ── B6b: GET /api/workspaces/{workspace}/users ────────────────────────────

    public function test_owner_can_list_workspace_users(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $this->makeWorkspaceMember($workspace);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users");

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_workspace_users_response_contains_member_fields(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $this->makeWorkspaceMember($workspace);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users");

        $response->assertStatus(200);
        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertArrayHasKey('id', $data[0]);
        $this->assertArrayHasKey('nom', $data[0]);
        $this->assertArrayHasKey('email', $data[0]);
        $this->assertArrayHasKey('workspace_role', $data[0]);
    }

    public function test_workspace_users_only_returns_members_of_that_workspace(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $member = $this->makeWorkspaceMember($workspace);

        // Utilisateur dans un autre workspace — ne doit pas apparaître
        $otherOwner = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherOwner->id]);
        $outsider = $this->makeWorkspaceMember($otherWorkspace);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users");

        $response->assertStatus(200);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertContains($member->id, $ids);
        $this->assertNotContains($outsider->id, $ids);
    }

    public function test_non_member_cannot_list_workspace_users(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $stranger = User::factory()->create();

        $this->actingAs($stranger, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users")
            ->assertStatus(403);
    }

    public function test_collaborateur_cannot_list_workspace_users(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $collaborateur = $this->makeWorkspaceMember($workspace, 'collaborateur');

        // Le rôle collaborateur n'a pas WORKSPACES_VIEW_MEMBERS
        $this->actingAs($collaborateur, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users")
            ->assertStatus(403);
    }

    public function test_workspace_users_search_filter_works(): void
    {
        [$owner, $workspace] = $this->makeOwnerWithWorkspace();
        $member = User::factory()->create(['nom' => 'Alice Durand']);
        $roleModel = Role::firstOrCreate(['name' => 'collaborateur', 'guard_name' => 'web']);
        $workspace->members()->attach($member->id, ['role_id' => $roleModel->id]);

        $response = $this->actingAs($owner, 'sanctum')
            ->getJson("/api/workspaces/{$workspace->id}/users?search=Alice");

        $response->assertStatus(200);
        $names = collect($response->json('data'))->pluck('nom')->all();
        $this->assertContains('Alice Durand', $names);
    }

    public function test_unauthenticated_cannot_list_workspace_users(): void
    {
        $workspace = Workspace::factory()->create();

        $this->getJson("/api/workspaces/{$workspace->id}/users")
            ->assertStatus(401);
    }
}
