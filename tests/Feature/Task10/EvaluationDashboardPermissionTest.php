<?php

declare(strict_types=1);

namespace Tests\Feature\Task10;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 10 — GET /api/evaluations/tableau-de-bord
 *
 * Covers:
 *   - owner: 200 + full workspace scope
 *   - manager: 200 + scoped to their project
 *   - cadre: 200 + scoped to their activity
 *   - collaborateur/stagiaire: 403
 *   - manager only sees members of their project (scope isolation)
 */
class EvaluationDashboardPermissionTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private Projet $projet;

    private Activite $activite;

    private Tache $tache;

    /** @var array<string, User> */
    private array $users = [];

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->buildWorld();
    }

    private function buildWorld(): void
    {
        $owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $this->activite = Activite::factory()->create(['projet_id' => $this->projet->id]);
        $this->tache = Tache::factory()->create(['activite_id' => $this->activite->id]);

        $this->attachWithRole($this->workspace->members(), $owner->id, 'owner');
        $this->attachWithRole($this->projet->members(), $owner->id, 'owner');
        $this->attachWithRole($this->activite->members(), $owner->id, 'cadre', [
            'can_edit_activity' => true, 'can_create_tasks' => true, 'can_delete_tasks' => true,
        ]);
        $owner->update(['current_workspace_id' => $this->workspace->id]);
        $this->users['owner'] = $owner;

        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $manager->id, 'manager');
        $this->attachWithRole($this->projet->members(), $manager->id, 'manager');
        $this->attachWithRole($this->activite->members(), $manager->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->users['manager'] = $manager;

        $cadre = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $cadre->id, 'cadre');
        $this->attachWithRole($this->activite->members(), $cadre->id, 'cadre', [
            'can_edit_activity' => false, 'can_create_tasks' => true, 'can_delete_tasks' => false,
        ]);
        $this->users['cadre'] = $cadre;

        $collaborateur = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $collaborateur->id, 'collaborateur');
        $this->users['collaborateur'] = $collaborateur;

        $stagiaire = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $stagiaire->id, 'stagiaire');
        $this->users['stagiaire'] = $stagiaire;
    }

    private function user(string $role): User
    {
        return $this->users[$role];
    }

    /** @test */
    public function owner_gets_200_on_evaluation_dashboard(): void
    {
        Sanctum::actingAs($this->user('owner'));

        $this->getJson('/api/evaluations/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    'top_performers',
                    'scores',
                    'alerts' => [
                        'escalades_abusives',
                        'high_inaction_rate',
                    ],
                ],
            ]);
    }

    /** @test */
    public function manager_gets_200_on_evaluation_dashboard(): void
    {
        Sanctum::actingAs($this->user('manager'));

        $this->getJson('/api/evaluations/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function cadre_gets_200_on_evaluation_dashboard(): void
    {
        Sanctum::actingAs($this->user('cadre'));

        $this->getJson('/api/evaluations/tableau-de-bord')
            ->assertOk()
            ->assertJsonPath('success', true);
    }

    /** @test */
    public function collaborateur_gets_403_on_evaluation_dashboard(): void
    {
        Sanctum::actingAs($this->user('collaborateur'));

        $this->getJson('/api/evaluations/tableau-de-bord')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function stagiaire_gets_403_on_evaluation_dashboard(): void
    {
        Sanctum::actingAs($this->user('stagiaire'));

        $this->getJson('/api/evaluations/tableau-de-bord')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function manager_only_sees_their_project_scope_in_dashboard(): void
    {
        // Add a second project with its own user — manager must NOT see this user.
        $outsideProjet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->user('owner')->id,
        ]);
        $outsideActivite = Activite::factory()->create(['projet_id' => $outsideProjet->id]);
        $outsideMember = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->attachWithRole($this->workspace->members(), $outsideMember->id, 'collaborateur');
        $this->attachWithRole($outsideActivite->members(), $outsideMember->id, 'collaborateur', [
            'can_edit_activity' => false, 'can_create_tasks' => false, 'can_delete_tasks' => false,
        ]);

        Sanctum::actingAs($this->user('manager'));

        $response = $this->getJson('/api/evaluations/tableau-de-bord')->assertOk();

        $scores = collect($response->json('data.scores'));
        $topPerformers = collect($response->json('data.top_performers'));

        // outsideMember is NOT in the manager's project scope — must not appear
        $this->assertFalse(
            $scores->pluck('user_id')->contains($outsideMember->id),
            'manager dashboard must not expose members from outside their project scope'
        );
        $this->assertFalse(
            $topPerformers->pluck('user_id')->contains($outsideMember->id),
            'manager top_performers must not expose members from outside their project scope'
        );
    }
}
