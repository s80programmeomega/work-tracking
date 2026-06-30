<?php

declare(strict_types=1);

namespace Tests\Feature\Team;

use App\Models\Projet;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Phase 2 — Team integration at project level.
 * Couvre : use_teams toggle, link/unlink, auto-sync membres, candidates endpoint.
 */
class TeamProjectIntegrationTest extends TestCase
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
        $this->workspace->addMember($this->owner, 'owner');
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($this->projet->members(), $this->owner->id, 'manager');
    }

    private function makeTeam(): Team
    {
        return Team::factory()->create([
            'owner_id' => $this->owner->id,
            'workspace_id' => $this->workspace->id,
        ]);
    }

    private function makeCollaborateur(): User
    {
        $user = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($user, 'collaborateur');

        return $user;
    }

    private function makeManager(): User
    {
        $user = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($user, 'manager');

        return $user;
    }

    // =========================================================================
    // USE_TEAMS TOGGLE
    // =========================================================================

    /** @test */
    public function owner_can_toggle_use_teams(): void
    {
        $this->actingAs($this->owner)
            ->patchJson("/api/projets/{$this->projet->id}/use-teams", ['use_teams' => true])
            ->assertOk()
            ->assertJsonPath('use_teams', true);

        $this->assertDatabaseHas('projets', [
            'id' => $this->projet->id,
            'use_teams' => true,
        ]);
    }

    /** @test */
    public function manager_can_toggle_use_teams(): void
    {
        $manager = $this->makeManager();

        $this->actingAs($manager)
            ->patchJson("/api/projets/{$this->projet->id}/use-teams", ['use_teams' => true])
            ->assertOk();
    }

    /** @test */
    public function collaborateur_cannot_toggle_use_teams(): void
    {
        $collab = $this->makeCollaborateur();
        $this->attachWithRole($this->projet->members(), $collab->id, 'collaborateur');

        $this->actingAs($collab)
            ->patchJson("/api/projets/{$this->projet->id}/use-teams", ['use_teams' => true])
            ->assertForbidden();
    }

    // =========================================================================
    // LINK TEAM
    // =========================================================================

    /** @test */
    public function owner_can_link_team_to_projet(): void
    {
        $team = $this->makeTeam();

        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/teams", ['team_id' => $team->id])
            ->assertOk()
            ->assertJsonPath('message', 'Équipe liée au projet avec succès.');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'project_id' => $this->projet->id,
        ]);
    }

    /** @test */
    public function cannot_link_team_already_linked_to_same_project(): void
    {
        $team = $this->makeTeam();
        $team->update(['project_id' => $this->projet->id]);

        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/teams", ['team_id' => $team->id])
            ->assertStatus(422);
    }

    /** @test */
    public function cannot_link_team_already_linked_to_other_project(): void
    {
        $otherProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $team = $this->makeTeam();
        $team->update(['project_id' => $otherProjet->id]);

        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/teams", ['team_id' => $team->id])
            ->assertStatus(422);
    }

    /** @test */
    public function linking_team_adds_team_members_to_projet_user(): void
    {
        $team = $this->makeTeam();
        $member = $this->makeCollaborateur();
        $team->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);

        $this->actingAs($this->owner)
            ->postJson("/api/projets/{$this->projet->id}/teams", ['team_id' => $team->id])
            ->assertOk();

        $this->assertDatabaseHas('projet_user', [
            'projet_id' => $this->projet->id,
            'user_id' => $member->id,
        ]);
    }

    // =========================================================================
    // UNLINK TEAM
    // =========================================================================

    /** @test */
    public function owner_can_unlink_team_from_projet(): void
    {
        $team = $this->makeTeam();
        $team->update(['project_id' => $this->projet->id]);

        $this->actingAs($this->owner)
            ->deleteJson("/api/projets/{$this->projet->id}/teams/{$team->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Équipe déliée du projet avec succès.');

        $this->assertDatabaseHas('teams', [
            'id' => $team->id,
            'project_id' => null,
        ]);
    }

    /** @test */
    public function unlink_removes_team_only_members_from_projet_user(): void
    {
        $team = $this->makeTeam();
        $member = $this->makeCollaborateur();

        // Lier l'équipe — cela ajoute le membre au projet via l'observer
        $team->members()->attach($member->id, ['role' => 'member', 'joined_at' => now()]);
        $team->update(['project_id' => $this->projet->id]);

        // Ajouter manuellement dans projet_user (simule ce que l'observer ferait)
        $this->attachWithRole($this->projet->members(), $member->id, 'collaborateur');

        $this->actingAs($this->owner)
            ->deleteJson("/api/projets/{$this->projet->id}/teams/{$team->id}")
            ->assertOk();

        // Le membre devrait être retiré du projet (il n'est que dans cette équipe)
        $this->assertDatabaseMissing('projet_user', [
            'projet_id' => $this->projet->id,
            'user_id' => $member->id,
        ]);
    }

    // =========================================================================
    // CANDIDATES ENDPOINT
    // =========================================================================

    /** @test */
    public function candidates_returns_workspace_members_when_use_teams_false(): void
    {
        $this->assertFalse((bool) $this->projet->use_teams);

        $member = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/candidates")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($member->id));
    }

    /** @test */
    public function candidates_returns_team_members_when_use_teams_true(): void
    {
        $this->projet->update(['use_teams' => true]);

        $team = $this->makeTeam();
        $team->update(['project_id' => $this->projet->id]);

        $teamMember = $this->makeCollaborateur();
        $team->members()->attach($teamMember->id, ['role' => 'member', 'joined_at' => now()]);

        $outsider = $this->makeCollaborateur();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/candidates")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($teamMember->id));
        $this->assertFalse($ids->contains($outsider->id));
    }

    // =========================================================================
    // LIST LINKED TEAMS
    // =========================================================================

    /** @test */
    public function owner_can_list_linked_teams(): void
    {
        $team = $this->makeTeam();
        $team->update(['project_id' => $this->projet->id]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/teams")
            ->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($team->id));
    }
}
