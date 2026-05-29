<?php

declare(strict_types=1);

namespace Tests\Feature\Projet;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de consultation de ProjetController :
 * getActivites, getTaches, getStatistics, dashboardStats,
 * performanceReport, accessibleTasks, accessible.
 */
class ProjetStatsTest extends TestCase
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
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

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
        $user->update(['current_workspace_id' => $this->workspace->id]);

        return $user;
    }

    private function addProjectMember(User $user, string $role = 'collaborateur'): void
    {
        $this->attachWithRole($this->projet->members(), $user->id, $role);
    }

    // =========================================================================
    // GET ACTIVITES
    // =========================================================================

    /** @test */
    public function project_member_can_get_activites(): void
    {
        Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/activites");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function outsider_cannot_get_activites_of_private_projet(): void
    {
        $privateProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        $outsider = $this->makeWsMember('collaborateur');

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$privateProjet->id}/activites")
            ->assertForbidden();
    }

    // =========================================================================
    // GET TACHES
    // =========================================================================

    /** @test */
    public function project_member_can_get_taches(): void
    {
        $activite = Activite::factory()->create([
            'projet_id' => $this->projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/taches");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function outsider_cannot_get_taches_of_private_projet(): void
    {
        $privateProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        $outsider = $this->makeWsMember('collaborateur');

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$privateProjet->id}/taches")
            ->assertForbidden();
    }

    // =========================================================================
    // GET STATISTICS
    // =========================================================================

    /** @test */
    public function project_member_can_get_statistics(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/statistics");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function outsider_cannot_get_statistics_of_private_projet(): void
    {
        $privateProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        $outsider = $this->makeWsMember('collaborateur');

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$privateProjet->id}/statistics")
            ->assertForbidden();
    }

    // =========================================================================
    // DASHBOARD STATS
    // =========================================================================

    /** @test */
    public function authenticated_user_can_get_dashboard_stats(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/dashboard-stats?workspace_id={$this->workspace->id}");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function dashboard_stats_returns_empty_structure_without_workspace(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->getJson('/api/projets/dashboard-stats');

        $response->assertOk()
            ->assertJsonPath('data.total_projets', 0);
    }

    // =========================================================================
    // PERFORMANCE REPORT
    // =========================================================================

    /** @test */
    public function project_member_can_get_performance_report(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/projets/{$this->projet->id}/performance-report");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function outsider_cannot_get_performance_report_of_private_projet(): void
    {
        $privateProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        $outsider = $this->makeWsMember('collaborateur');

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$privateProjet->id}/performance-report")
            ->assertForbidden();
    }

    // =========================================================================
    // ACCESSIBLE TASKS
    // =========================================================================

    /** @test */
    public function project_member_can_get_accessible_tasks(): void
    {
        $collaborateur = $this->makeWsMember('collaborateur');
        $this->addProjectMember($collaborateur, 'collaborateur');

        $response = $this->actingAs($collaborateur)
            ->getJson("/api/projets/{$this->projet->id}/accessible-tasks");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function outsider_cannot_get_accessible_tasks_of_private_projet(): void
    {
        $privateProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'private',
        ]);

        $outsider = $this->makeWsMember('collaborateur');

        $this->actingAs($outsider)
            ->getJson("/api/projets/{$privateProjet->id}/accessible-tasks")
            ->assertForbidden();
    }

    // =========================================================================
    // ACCESSIBLE (projets-accessibles)
    // =========================================================================

    /** @test */
    public function user_can_list_accessible_projets(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson('/api/projets/projets-accessibles');

        $response->assertOk();

        $ids = collect($response->json())->pluck('id');
        $this->assertTrue($ids->contains($this->projet->id));
    }

    /** @test */
    public function accessible_does_not_return_projets_from_other_workspaces(): void
    {
        $otherOwner = User::factory()->create();
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $otherOwner->id]);
        Projet::factory()->create([
            'workspace_id' => $otherWorkspace->id,
            'responsable_id' => $otherOwner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/projets/projets-accessibles');

        $response->assertOk();

        $wsIds = collect($response->json())->pluck('workspace_id')->unique();
        $this->assertFalse($wsIds->contains($otherWorkspace->id));
    }
}
