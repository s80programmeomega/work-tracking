<?php

declare(strict_types=1);

namespace Tests\Feature\Policies;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie que TachePolicy délègue correctement à ContextualPermissionGate
 * pour view, update, delete, validateN1, validateN2.
 * Couvre également le chemin de rôle virtuel task_responsable (is_responsable=true).
 */
class TachePolicyTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private User $owner;

    private Projet $projet;

    private Activite $activite;

    private Tache $tache;

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
        $this->tache = Tache::factory()->create([
            'activite_id' => $this->activite->id,
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function assignToTache(User $user, string $role, array $pivotExtra = []): void
    {
        $this->tache->assignees()->attach($user->id, array_merge(
            ['role_id' => $this->roleId($role)],
            $pivotExtra
        ));
    }

    // =========================================================================
    // VIEW
    // =========================================================================

    /** @test */
    public function workspace_owner_can_view_tache(): void
    {
        $this->assertTrue($this->owner->can('view', $this->tache));
    }

    /** @test */
    public function collaborateur_workspace_member_assigned_to_tache_can_view(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur');

        $this->assertTrue($collaborateur->can('view', $this->tache));
    }

    /** @test */
    public function user_with_no_membership_at_any_level_cannot_view_tache(): void
    {
        $outsider = User::factory()->create();

        $this->assertFalse($outsider->can('view', $this->tache));
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function cadre_workspace_member_can_update_tache(): void
    {
        // cadre a taches.edit via son rôle au niveau workspace
        $cadre = $this->makeWsMember('cadre');

        $this->assertTrue($cadre->can('update', $this->tache));
    }

    /** @test */
    public function collaborateur_workspace_member_not_assigned_to_tache_cannot_update(): void
    {
        // collaborateur sans assignation directe à la tâche n'a que le rôle workspace
        // collaborateur n'a pas taches.edit dans son rôle
        $collaborateur = $this->makeWsMember('collaborateur');

        $this->assertFalse($collaborateur->can('update', $this->tache));
    }

    /** @test */
    public function assignee_with_can_edit_false_cannot_update_tache(): void
    {
        // can_edit défaut=true dans tache_user — forcer à false pour vérifier le verrou
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur', ['can_edit' => false]);

        $this->assertFalse($collaborateur->can('update', $this->tache));
    }

    /** @test */
    public function assignee_with_can_edit_true_can_update_tache(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur', ['can_edit' => true]);

        // pivot override sur tache_user.can_edit accorde taches.edit
        $this->assertTrue($collaborateur->can('update', $this->tache));
    }

    // =========================================================================
    // DELETE
    // =========================================================================

    /** @test */
    public function manager_workspace_member_can_delete_tache(): void
    {
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($manager->can('delete', $this->tache));
    }

    /** @test */
    public function cadre_workspace_member_cannot_delete_tache(): void
    {
        // cadre a taches.edit mais pas taches.delete
        $cadre = $this->makeWsMember('cadre');

        $this->assertFalse($cadre->can('delete', $this->tache));
    }

    // =========================================================================
    // VALIDATE N1
    // =========================================================================

    /** @test */
    public function cadre_workspace_member_can_validate_n1(): void
    {
        $cadre = $this->makeWsMember('cadre');

        $this->assertTrue($cadre->can('validateN1', $this->tache));
    }

    /** @test */
    public function collaborateur_without_pivot_override_cannot_validate_n1(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur');

        $this->assertFalse($collaborateur->can('validateN1', $this->tache));
    }

    /** @test */
    public function collaborateur_with_can_validate_pivot_flag_can_validate_n1(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur', ['can_validate' => true]);

        // pivot override sur tache_user.can_validate accorde taches.validate_n1
        $this->assertTrue($collaborateur->can('validateN1', $this->tache));
    }

    // =========================================================================
    // VALIDATE N2
    // =========================================================================

    /** @test */
    public function manager_workspace_member_can_validate_n2(): void
    {
        $manager = $this->makeWsMember('manager');

        $this->assertTrue($manager->can('validateN2', $this->tache));
    }

    /** @test */
    public function cadre_workspace_member_cannot_validate_n2(): void
    {
        // cadre a taches.validate_n1 mais pas taches.validate_n2
        $cadre = $this->makeWsMember('cadre');

        $this->assertFalse($cadre->can('validateN2', $this->tache));
    }

    // =========================================================================
    // VIRTUAL ROLE task_responsable (is_responsable flag)
    // =========================================================================

    /** @test */
    public function assignee_with_is_responsable_flag_gets_approve_n0_via_virtual_role(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        // is_responsable=true ajoute le rôle virtuel task_responsable
        $this->assignToTache($collaborateur, 'collaborateur', ['is_responsable' => true]);

        // task_responsable accorde taches.approve_n0
        $this->assertTrue($collaborateur->can('approveN0', $this->tache));
    }

    /** @test */
    public function assignee_without_is_responsable_flag_cannot_approve_n0(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->assignToTache($collaborateur, 'collaborateur', ['is_responsable' => false]);

        // sans le flag, collaborateur n'a pas taches.approve_n0
        $this->assertFalse($collaborateur->can('approveN0', $this->tache));
    }
}
