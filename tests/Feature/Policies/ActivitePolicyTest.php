<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que ActivitePolicy délègue correctement à ContextualPermissionGate
 * pour les méthodes view, update et delete.
 * Couvre également les overrides booléens du pivot activite_user.
 */
class ActivitePolicyTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Projet $projet;

    private Activite $activite;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function attachToActivite(User $user, string $role, array $pivotExtra = []): void
    {
        $this->activite->members()->attach($user->id, array_merge(
            ['role_id' => $this->roleId($role)],
            $pivotExtra
        ));
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function workspace_member_with_activites_view_can_view_activite(): void
    {
        // tous les rôles workspace ont activites.view — test avec cadre
        $cadre = $this->makeWsMember('cadre');

        $this->assertTrue($cadre->can('view', $this->activite));
    }

    /** @test */
    public function user_without_any_membership_cannot_view_activite(): void
    {
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('view', $this->activite));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function activite_responsable_inherits_owner_role_and_can_update(): void
    {
        $responsable = $this->makeWsMember('collaborateur');
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $responsable->id,
        ]);

        // responsable_id déclenche le rôle owner dans ContextualPermissionGate
        $this->assertTrue($responsable->can('update', $activite));
    }

    /** @test */
    public function cadre_workspace_member_can_update_activite(): void
    {
        // cadre a activites.edit
        $cadre = $this->makeWsMember('cadre');

        $this->assertTrue($cadre->can('update', $this->activite));
    }

    /** @test */
    public function collaborateur_workspace_member_cannot_update_activite(): void
    {
        // collaborateur n'a pas activites.edit
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->assertFalse($collaborateur->can('update', $this->activite));
    }

    /** @test */
    public function collaborateur_with_can_edit_activity_pivot_can_update_activite(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->attachToActivite($collaborateur, 'collaborateur', ['can_edit_activity' => true]);

        // override pivot activite_user.can_edit_activity accorde activites.edit
        $this->assertTrue($collaborateur->can('update', $this->activite));
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function manager_workspace_member_can_delete_activite(): void
    {
        // manager a activites.delete
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($manager->can('delete', $this->activite));
    }

    /** @test */
    public function cadre_workspace_member_cannot_delete_activite(): void
    {
        // cadre a activites.edit mais pas activites.delete
        $cadre = $this->makeWsMember('cadre');

        $this->assertFalse($cadre->can('delete', $this->activite));
    }

    /** @test */
    public function collaborateur_with_can_delete_activity_pivot_can_delete_activite(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->attachToActivite($collaborateur, 'collaborateur', ['can_delete_activity' => true]);

        // override pivot activite_user.can_delete_activity accorde activites.delete
        $this->assertTrue($collaborateur->can('delete', $this->activite));
    }
}
