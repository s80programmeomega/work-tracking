<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que ProjetPolicy délègue correctement à ContextualPermissionGate
 * pour les méthodes view, update, delete et manageMembers.
 */
class ProjetPolicyTest extends TestCase
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

    private function makePublicProjet(?User $responsable = null): Projet
    {
        return Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $responsable?->id ?? $this->owner->id,
            'visibility' => 'public',
        ]);
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function workspace_owner_can_view_any_projet(): void
    {
        $projet = $this->makePublicProjet();

        $this->assertTrue($this->owner->can('view', $projet));
    }

    /** @test */
    public function projet_responsable_can_view_their_projet(): void
    {
        $responsable = $this->makeWsMember('cadre');
        $projet = $this->makePublicProjet($responsable);

        $this->assertTrue($responsable->can('view', $projet));
    }

    /** @test */
    public function manager_workspace_member_can_view_public_projet(): void
    {
        $manager = $this->makeWsMember('manager');
        $projet = $this->makePublicProjet();

        $this->assertTrue($manager->can('view', $projet));
    }

    /** @test */
    public function collaborateur_with_project_membership_can_view_team_projet(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'team',
        ]);
        $this->attachWithRole($projet->members(), $collaborateur->id, 'collaborateur');

        $this->assertTrue($collaborateur->can('view', $projet));
    }

    /** @test */
    public function cadre_workspace_member_without_project_membership_cannot_view_private_projet(): void
    {
        $cadre = $this->makeWsMember('cadre');
        $projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        // cadre a projets.view mais est en-dessous du seuil de visibilité manager
        $this->assertFalse($cadre->can('view', $projet));
    }

    /** @test */
    public function super_admin_can_view_any_projet(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $projet = $this->makePublicProjet();

        $this->assertTrue($superAdmin->can('view', $projet));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function workspace_owner_can_update_projet(): void
    {
        $projet = $this->makePublicProjet();

        $this->assertTrue($this->owner->can('update', $projet));
    }

    /** @test */
    public function manager_workspace_member_can_update_projet(): void
    {
        $manager = $this->makeWsMember('manager');
        $projet = $this->makePublicProjet();

        $this->assertTrue($manager->can('update', $projet));
    }

    /** @test */
    public function cadre_workspace_member_cannot_update_projet(): void
    {
        // cadre a projets.view et projets.manage_members, mais pas projets.edit
        $cadre = $this->makeWsMember('cadre');
        $projet = $this->makePublicProjet();

        $this->assertFalse($cadre->can('update', $projet));
    }

    /** @test */
    public function projet_responsable_inherits_owner_role_and_can_update(): void
    {
        $responsable = $this->makeWsMember('cadre');
        $projet = $this->makePublicProjet($responsable);

        // responsable_id déclenche le rôle owner dans ContextualPermissionGate
        $this->assertTrue($responsable->can('update', $projet));
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function workspace_owner_can_delete_projet(): void
    {
        $projet = $this->makePublicProjet();

        $this->assertTrue($this->owner->can('delete', $projet));
    }

    /** @test */
    public function manager_workspace_member_can_delete_projet(): void
    {
        $manager = $this->makeWsMember('manager');
        $projet = $this->makePublicProjet();

        $this->assertTrue($manager->can('delete', $projet));
    }

    /** @test */
    public function collaborateur_workspace_member_cannot_delete_projet(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $projet = $this->makePublicProjet();

        $this->assertFalse($collaborateur->can('delete', $projet));
    }

    // =========================================================================
    // MANAGE MEMBERS
    // =========================================================================

    /** @test */
    public function owner_manager_and_cadre_can_manage_projet_members(): void
    {
        $manager = $this->makeWsMember('manager');
        $cadre = $this->makeWsMember('cadre');
        $projet = $this->makePublicProjet();

        $this->assertTrue($this->owner->can('manageMembers', $projet));
        $this->assertTrue($manager->can('manageMembers', $projet));
        $this->assertTrue($cadre->can('manageMembers', $projet));
    }

    /** @test */
    public function collaborateur_and_observateur_cannot_manage_projet_members(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $observateur = $this->makeWsMember('observateur');
        $projet = $this->makePublicProjet();

        $this->assertFalse($collaborateur->can('manageMembers', $projet));
        $this->assertFalse($observateur->can('manageMembers', $projet));
    }
}
