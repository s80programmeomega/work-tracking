<?php

declare(strict_types=1);

namespace Tests\Feature\Projet;

use App\Models\Projet;
use App\Models\Team;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Testing\TestResponse;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Vérifie l'application des règles de visibilité des projets :
 * - public   → tout membre du workspace
 * - team     → membres du projet ou d'une équipe liée
 * - private  → rôle manager ou supérieur + membre du workspace
 */
class ProjetVisibilityTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private User $workspaceOwner;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->workspaceOwner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->workspaceOwner->id]);
    }

    // =========================================================================
    // HELPERS
    // =========================================================================

    private function makeProjet(string $visibility, ?User $responsable = null): Projet
    {
        $responsable ??= User::factory()->create();

        return Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $responsable->id,
            'visibility' => $visibility,
        ]);
    }

    private function makeWorkspaceMember(string $workspaceRole = 'collaborateur', string $userRole = 'cadre'): User
    {
        $user = User::factory()->create();
        $user->role = $userRole;
        $user->save();
        $this->workspace->addMember($user, $workspaceRole);

        return $user;
    }

    private function attachProjectMember(Projet $projet, User $user, string $role = 'collaborateur'): void
    {
        $this->attachWithRole($projet->members(), $user->id, $role);
    }

    private function makeLinkedTeam(Projet $projet): Team
    {
        return Team::create([
            'name' => 'Équipe test',
            'owner_id' => $this->workspaceOwner->id,
            'project_id' => $projet->id,
            'visibility' => 'private',
        ]);
    }

    private function listUrl(): string
    {
        return '/api/projets/mes-projets?workspace_id='.$this->workspace->id;
    }

    private function assertInList(TestResponse $response, Projet $projet, string $msg = ''): void
    {
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue(
            $ids->contains($projet->id),
            $msg ?: "Projet {$projet->id} (visibility={$projet->visibility}) devrait apparaître dans la liste."
        );
    }

    private function assertNotInList(TestResponse $response, Projet $projet, string $msg = ''): void
    {
        $ids = collect($response->json('data'))->pluck('id');
        $this->assertFalse(
            $ids->contains($projet->id),
            $msg ?: "Projet {$projet->id} (visibility={$projet->visibility}) ne devrait PAS apparaître dans la liste."
        );
    }

    // =========================================================================
    // PUBLIC VISIBILITY
    // =========================================================================

    /** @test */
    public function public_project_is_listed_for_any_workspace_member(): void
    {
        $projet = $this->makeProjet('public');
        $member = $this->makeWorkspaceMember();

        $response = $this->actingAs($member)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertInList($response, $projet);
    }

    /** @test */
    public function public_project_show_is_accessible_by_workspace_member(): void
    {
        $projet = $this->makeProjet('public');
        $member = $this->makeWorkspaceMember();

        $this->actingAs($member)->getJson("/api/projets/{$projet->id}")->assertOk();
    }

    /** @test */
    public function public_project_is_not_listed_for_non_workspace_member(): void
    {
        $projet = $this->makeProjet('public');
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertNotInList($response, $projet);
    }

    /** @test */
    public function public_project_show_is_denied_for_non_workspace_member(): void
    {
        $projet = $this->makeProjet('public');
        $outsider = User::factory()->create();

        $this->actingAs($outsider)->getJson("/api/projets/{$projet->id}")->assertForbidden();
    }

    // =========================================================================
    // TEAM (MEMBER) VISIBILITY
    // =========================================================================

    /** @test */
    public function team_project_is_listed_for_project_member(): void
    {
        $projet = $this->makeProjet('team');
        $member = $this->makeWorkspaceMember();
        $this->attachProjectMember($projet, $member);

        $response = $this->actingAs($member)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertInList($response, $projet);
    }

    /** @test */
    public function team_project_show_is_accessible_by_project_member(): void
    {
        $projet = $this->makeProjet('team');
        $member = $this->makeWorkspaceMember();
        $this->attachProjectMember($projet, $member);

        $this->actingAs($member)->getJson("/api/projets/{$projet->id}")->assertOk();
    }

    /** @test */
    public function team_project_is_listed_for_linked_team_member(): void
    {
        $projet = $this->makeProjet('team');
        $teamMember = $this->makeWorkspaceMember();
        $team = $this->makeLinkedTeam($projet);
        $team->members()->attach($teamMember->id, ['role' => 'member']);

        $response = $this->actingAs($teamMember)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertInList($response, $projet);
    }

    /** @test */
    public function team_project_show_is_accessible_by_linked_team_member(): void
    {
        $projet = $this->makeProjet('team');
        $teamMember = $this->makeWorkspaceMember();
        $team = $this->makeLinkedTeam($projet);
        $team->members()->attach($teamMember->id, ['role' => 'member']);

        $this->actingAs($teamMember)->getJson("/api/projets/{$projet->id}")->assertOk();
    }

    /** @test */
    public function team_project_is_not_listed_for_workspace_member_with_no_membership(): void
    {
        $projet = $this->makeProjet('team');
        $bystander = $this->makeWorkspaceMember();

        $response = $this->actingAs($bystander)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertNotInList($response, $projet);
    }

    /** @test */
    public function team_project_show_is_denied_for_workspace_member_with_no_membership(): void
    {
        $projet = $this->makeProjet('team');
        $bystander = $this->makeWorkspaceMember();

        $this->actingAs($bystander)->getJson("/api/projets/{$projet->id}")->assertForbidden();
    }

    // =========================================================================
    // PRIVATE VISIBILITY
    // =========================================================================

    /** @test */
    public function private_project_is_listed_for_manager_level_workspace_member(): void
    {
        $projet = $this->makeProjet('private');
        $manager = $this->makeWorkspaceMember('manager', 'manager');

        $response = $this->actingAs($manager)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertInList($response, $projet);
    }

    /** @test */
    public function private_project_show_is_accessible_by_manager_level_workspace_member(): void
    {
        $projet = $this->makeProjet('private');
        $manager = $this->makeWorkspaceMember('manager', 'manager');

        $this->actingAs($manager)->getJson("/api/projets/{$projet->id}")->assertOk();
    }

    /** @test */
    public function private_project_is_not_listed_for_below_manager_workspace_member(): void
    {
        $projet = $this->makeProjet('private');
        $cadre = $this->makeWorkspaceMember('cadre', 'cadre');

        $response = $this->actingAs($cadre)->getJson($this->listUrl());

        $response->assertOk();
        $this->assertNotInList($response, $projet);
    }

    /** @test */
    public function private_project_show_is_denied_for_below_manager_workspace_member(): void
    {
        $projet = $this->makeProjet('private');
        $cadre = $this->makeWorkspaceMember('cadre', 'cadre');

        $this->actingAs($cadre)->getJson("/api/projets/{$projet->id}")->assertForbidden();
    }

    /** @test */
    public function private_project_responsable_can_always_see_it_regardless_of_role(): void
    {
        $responsable = $this->makeWorkspaceMember('cadre', 'cadre');
        $projet = $this->makeProjet('private', $responsable);

        $listResponse = $this->actingAs($responsable)->getJson($this->listUrl());
        $listResponse->assertOk();
        $this->assertInList($listResponse, $projet);

        $this->actingAs($responsable)->getJson("/api/projets/{$projet->id}")->assertOk();
    }

    // =========================================================================
    // BYPASS CASES
    // =========================================================================

    /** @test */
    public function super_admin_without_membership_does_not_see_projects(): void
    {
        // Superadmin scoping: platform operators are not members of customer workspaces.
        // A superadmin with no workspace membership sees an empty project list and gets
        // 403/404 on individual project endpoints they don't own.
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        foreach (['public', 'team', 'private'] as $visibility) {
            $projet = $this->makeProjet($visibility);

            $listResponse = $this->actingAs($superAdmin)->getJson($this->listUrl());
            $listResponse->assertOk();
            $this->assertNotInList($listResponse, $projet, "super_admin sans membership ne devrait pas voir le projet {$visibility}");
        }
    }

    /** @test */
    public function workspace_owner_sees_all_visibility_types_in_their_workspace(): void
    {
        $otherResponsable = User::factory()->create();

        foreach (['public', 'team', 'private'] as $visibility) {
            $projet = Projet::factory()->create([
                'workspace_id' => $this->workspace->id,
                'responsable_id' => $otherResponsable->id,
                'visibility' => $visibility,
            ]);

            $listResponse = $this->actingAs($this->workspaceOwner)->getJson($this->listUrl());
            $listResponse->assertOk();
            $this->assertInList($listResponse, $projet, "owner du workspace devrait voir le projet {$visibility}");

            $this->actingAs($this->workspaceOwner)->getJson("/api/projets/{$projet->id}")->assertOk();
        }
    }
}
