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
 * Task 10 — GET /api/workspace/taches
 *
 * Covers:
 *   - owner: 200 + paginated task list with correct structure
 *   - cadre: 403 (no EVALUATIONS_VIEW_WORKSPACE_TACHES permission)
 *   - manager: 403
 *   - collaborateur: 403
 *   - owner gets all workspace tasks (filter by statut works)
 */
class WorkspaceTachesPermissionTest extends TestCase
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
        $this->tache = Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'statut' => 'en_cours',
        ]);

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
    }

    private function user(string $role): User
    {
        return $this->users[$role];
    }

    /** @test */
    public function owner_gets_200_on_workspace_taches(): void
    {
        Sanctum::actingAs($this->user('owner'));

        $this->getJson('/api/workspace/taches')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure([
                'data' => [
                    '*' => ['id', 'titre', 'statut'],
                ],
                'meta' => ['current_page', 'last_page', 'total', 'per_page'],
            ]);
    }

    /** @test */
    public function owner_workspace_taches_includes_the_seeded_task(): void
    {
        Sanctum::actingAs($this->user('owner'));

        $response = $this->getJson('/api/workspace/taches')->assertOk();

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->tache->id));
    }

    /** @test */
    public function owner_can_filter_workspace_taches_by_statut(): void
    {
        // Create a second task with a different status and a unique titre.
        Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'statut' => 'a_faire',
            'titre' => 'Tâche filtre-test-unique-'.uniqid(),
        ]);

        Sanctum::actingAs($this->user('owner'));

        $response = $this->getJson('/api/workspace/taches?statut=en_cours')->assertOk();

        $statuts = collect($response->json('data'))->pluck('statut')->unique()->values();
        $this->assertSame(['en_cours'], $statuts->all());
    }

    /** @test */
    public function cadre_gets_403_on_workspace_taches(): void
    {
        Sanctum::actingAs($this->user('cadre'));

        $this->getJson('/api/workspace/taches')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function manager_gets_403_on_workspace_taches(): void
    {
        Sanctum::actingAs($this->user('manager'));

        $this->getJson('/api/workspace/taches')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }

    /** @test */
    public function collaborateur_gets_403_on_workspace_taches(): void
    {
        Sanctum::actingAs($this->user('collaborateur'));

        $this->getJson('/api/workspace/taches')
            ->assertStatus(403)
            ->assertJsonPath('success', false);
    }
}
