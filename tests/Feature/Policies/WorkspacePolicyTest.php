<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que WorkspacePolicy délègue correctement à ContextualPermissionGate
 * pour view, manageSettings, manageMembers et createProject.
 */
class WorkspacePolicyTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function workspace_owner_can_view_workspace(): void
    {
        $this->assertTrue($this->owner->can('view', $this->workspace));
    }

    /** @test */
    public function any_workspace_member_can_view_workspace(): void
    {
        // tous les rôles ont workspaces.view
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->assertTrue($collaborateur->can('view', $this->workspace));
    }

    /** @test */
    public function non_member_cannot_view_workspace(): void
    {
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('view', $this->workspace));
    }

    // =========================================================================
    // MANAGE SETTINGS
    // =========================================================================

    /** @test */
    public function workspace_owner_can_manage_settings(): void
    {
        $this->assertTrue($this->owner->can('manageSettings', $this->workspace));
    }

    /** @test */
    public function manager_workspace_member_can_manage_settings(): void
    {
        // manager a workspaces.manage_settings
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($manager->can('manageSettings', $this->workspace));
    }

    /** @test */
    public function cadre_workspace_member_cannot_manage_settings(): void
    {
        // cadre n'a pas workspaces.manage_settings
        $cadre = $this->makeWsMember('cadre');

        $this->assertFalse($cadre->can('manageSettings', $this->workspace));
    }

    // =========================================================================
    // MANAGE MEMBERS (invite_member OR remove_member)
    // =========================================================================

    /** @test */
    public function owner_and_manager_can_manage_workspace_members(): void
    {
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($this->owner->can('manageMembers', $this->workspace));
        $this->assertTrue($manager->can('manageMembers', $this->workspace));
    }

    /** @test */
    public function cadre_can_manage_members_via_invite_permission(): void
    {
        // cadre a workspaces.invite_member → manageMembers retourne true (logique OR)
        $cadre = $this->makeWsMember('cadre');

        $this->assertTrue($cadre->can('manageMembers', $this->workspace));
    }

    /** @test */
    public function collaborateur_cannot_manage_workspace_members(): void
    {
        // collaborateur n'a ni invite_member ni remove_member
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->assertFalse($collaborateur->can('manageMembers', $this->workspace));
    }

    // =========================================================================
    // CREATE PROJECT
    // =========================================================================

    /** @test */
    public function manager_workspace_member_can_create_project(): void
    {
        // manager a workspaces.create_project
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($manager->can('createProject', $this->workspace));
    }

    /** @test */
    public function collaborateur_cannot_create_project(): void
    {
        // collaborateur n'a pas workspaces.create_project
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->assertFalse($collaborateur->can('createProject', $this->workspace));
    }
}
