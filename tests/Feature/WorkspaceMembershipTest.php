<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class WorkspaceMembershipTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_add_member_attaches_user_with_role_id_from_spatie(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $workspace->addMember($user, 'manager');

        $managerRoleId = Role::where('name', 'manager')->where('guard_name', 'web')->value('id');
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role_id' => $managerRoleId,
        ]);
    }

    public function test_add_member_is_idempotent_for_existing_members(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();

        $workspace->addMember($user, 'collaborateur');
        $workspace->addMember($user, 'manager');

        $this->assertSame(
            1,
            $workspace->members()->where('user_id', $user->id)->count(),
            'addMember() must not duplicate rows for existing members.'
        );
    }

    public function test_update_member_role_changes_role_id(): void
    {
        $workspace = Workspace::factory()->create();
        $user = User::factory()->create();
        $workspace->addMember($user, 'collaborateur');

        $workspace->updateMemberRole($user, 'cadre');

        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $this->assertDatabaseHas('workspace_members', [
            'workspace_id' => $workspace->id,
            'user_id' => $user->id,
            'role_id' => $cadreRoleId,
        ]);
    }

    public function test_is_owner_or_admin_returns_true_for_manager_member(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $manager = User::factory()->create();
        $workspace->addMember($manager, 'manager');

        $this->assertTrue($workspace->isOwnerOrAdmin($manager));
    }

    public function test_is_owner_or_admin_returns_false_for_collaborateur_member(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $collaborateur = User::factory()->create();
        $workspace->addMember($collaborateur, 'collaborateur');

        $this->assertFalse($workspace->isOwnerOrAdmin($collaborateur));
    }

    /**
     * Régression : un collaborateur invité (membre, mais current_workspace_id null)
     * ne doit pas rester piégé sans workspace courant — ensureCurrentWorkspace() le
     * pointe vers son workspace membre. Sinon le SPA le bloque sur /workspaces/create
     * et il ne peut atteindre aucune page (ex. /profile).
     */
    public function test_ensure_current_workspace_backfills_member_pointer(): void
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

        $collaborateur = User::factory()->create(['current_workspace_id' => null]);
        $workspace->addMember($collaborateur, 'collaborateur');

        $resolved = $collaborateur->ensureCurrentWorkspace();

        $this->assertSame($workspace->id, $resolved);
        $this->assertSame($workspace->id, $collaborateur->fresh()->current_workspace_id);
    }

    public function test_ensure_current_workspace_prefers_owned_workspace(): void
    {
        $user = User::factory()->create(['current_workspace_id' => null]);
        $owned = Workspace::factory()->create(['owner_id' => $user->id]);

        $this->assertSame($owned->id, $user->ensureCurrentWorkspace());
    }

    public function test_ensure_current_workspace_leaves_workspaceless_user_null(): void
    {
        $user = User::factory()->create(['current_workspace_id' => null]);

        $this->assertNull($user->ensureCurrentWorkspace());
        $this->assertNull($user->fresh()->current_workspace_id);
    }

    public function test_ensure_current_workspace_does_not_override_existing_pointer(): void
    {
        $owner = User::factory()->create();
        $a = Workspace::factory()->create(['owner_id' => $owner->id]);
        $b = Workspace::factory()->create(['owner_id' => $owner->id]);
        $user = User::factory()->create(['current_workspace_id' => $b->id]);
        $a->addMember($user, 'collaborateur');

        $this->assertSame($b->id, $user->ensureCurrentWorkspace());
    }
}
