<?php

declare(strict_types=1);

namespace Tests\Feature\Projet;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre la gestion des membres de projet :
 * getMembers, addMember, updateMember, removeMember,
 * getMemberRemovalImpact, removeMemberWithTransfer.
 */
class ProjetMembersTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Projet $projet;

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
            'visibility' => 'public',
        ]);
    }

    private function makeWsMember(string $role): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function addProjectMember(User $user, string $role = 'collaborateur'): void
    {
        $this->attachWithRole($this->projet->members(), $user->id, $role);
    }

    private function membersUrl(): string
    {
        return "/api/projets/{$this->projet->id}/members";
    }

    // =========================================================================
    // GET MEMBERS
    // =========================================================================

    /** @test */
    public function project_viewer_can_list_members(): void
    {
        $manager = $this->makeWsMember('manager');
        $this->addProjectMember($manager, 'manager');

        $response = $this->actingAs($this->owner)
            ->getJson($this->membersUrl());

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // ADD MEMBER
    // =========================================================================

    /** @test */
    public function manager_can_add_workspace_member_to_projet(): void
    {
        $manager = $this->makeWsMember('manager');
        $newMember = $this->makeWsMember('collaborateur');

        $this->actingAs($manager)
            ->postJson($this->membersUrl(), [
                'user_id' => $newMember->id,
                'role' => 'collaborateur',
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Membre ajouté avec succès.');

        $this->assertTrue(
            $this->projet->members()->where('user_id', $newMember->id)->exists()
        );
    }

    /** @test */
    public function adding_non_workspace_member_returns_422(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson($this->membersUrl(), [
                'user_id' => $outsider->id,
                'role' => 'collaborateur',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', "L'utilisateur doit d'abord être membre du workspace.");
    }

    /** @test */
    public function adding_already_existing_project_member_returns_422(): void
    {
        $member = $this->makeWsMember('collaborateur');
        $this->addProjectMember($member, 'collaborateur');

        $this->actingAs($this->owner)
            ->postJson($this->membersUrl(), [
                'user_id' => $member->id,
                'role' => 'collaborateur',
            ])
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Cet utilisateur est déjà membre du projet.');
    }

    // =========================================================================
    // UPDATE MEMBER
    // =========================================================================

    /** @test */
    public function manager_can_update_project_member_role(): void
    {
        $member = $this->makeWsMember('cadre');
        $manager = $this->makeWsMember('manager');
        $this->addProjectMember($member, 'cadre');

        $this->actingAs($manager)
            ->putJson("{$this->membersUrl()}/{$member->id}", ['role' => 'manager'])
            ->assertOk()
            ->assertJsonPath('message', 'Permissions mises à jour avec succès.');
    }

    /** @test */
    public function updating_non_project_member_returns_404(): void
    {
        $outsider = $this->makeWsMember('cadre');

        $this->actingAs($this->owner)
            ->putJson("{$this->membersUrl()}/{$outsider->id}", ['role' => 'manager'])
            ->assertNotFound();
    }

    // =========================================================================
    // REMOVE MEMBER
    // =========================================================================

    /** @test */
    public function manager_can_remove_project_member(): void
    {
        $member = $this->makeWsMember('collaborateur');
        $manager = $this->makeWsMember('manager');
        $this->addProjectMember($member, 'collaborateur');

        $this->actingAs($manager)
            ->deleteJson("{$this->membersUrl()}/{$member->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Membre retiré avec succès. Tous ses accès ont été révoqués.');

        $this->assertFalse(
            $this->projet->members()->where('user_id', $member->id)->exists()
        );
    }

    // =========================================================================
    // REMOVAL IMPACT
    // =========================================================================

    /** @test */
    public function get_member_removal_impact_returns_structured_data(): void
    {
        $member = $this->makeWsMember('cadre');
        $this->addProjectMember($member, 'cadre');

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/members/{$member->id}/removal-impact");

        $response->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'is_project_responsable',
                    'activities_count',
                    'activities',
                    'responsable_tasks_count',
                    'assigned_tasks_count',
                    'requires_transfer',
                    'candidates',
                ],
            ]);
    }

    // =========================================================================
    // REMOVE WITH TRANSFER
    // =========================================================================

    /** @test */
    public function remove_member_with_transfer_reassigns_activity_responsable(): void
    {
        $member = $this->makeWsMember('cadre');
        $successor = $this->makeWsMember('cadre');
        $this->addProjectMember($member, 'cadre');
        $this->addProjectMember($successor, 'cadre');

        // L'activité est sous la responsabilité du membre à retirer
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $member->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/projets/{$this->projet->id}/members/{$member->id}/remove", [
                'transfer_to_user_id' => $successor->id,
            ])
            ->assertOk();

        // L'activité a été transférée au successeur
        $this->assertDatabaseHas('activites', [
            'id' => $activite->id,
            'responsable_id' => $successor->id,
        ]);

        // Le membre a été retiré du projet
        $this->assertFalse(
            $this->projet->members()->where('user_id', $member->id)->exists()
        );
    }

    /** @test */
    public function remove_member_with_responsibilities_without_transfer_returns_422(): void
    {
        $member = $this->makeWsMember('cadre');
        $this->addProjectMember($member, 'cadre');

        // L'activité rend le transfert obligatoire
        Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $member->id,
        ]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/projets/{$this->projet->id}/members/{$member->id}/remove")
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Le transfert des responsabilités est obligatoire avant le retrait de ce membre.');
    }
}
