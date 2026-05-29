<?php

declare(strict_types=1);

namespace Tests\Feature\Activite;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de lecture d'ActiviteController/TacheController :
 * kanban (forActivite), getTaches, forProjet.
 */
class ActiviteKanbanTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

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
            'visibility' => 'public',
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

    // =========================================================================
    // KANBAN (TacheController::forActivite)
    // =========================================================================

    /** @test */
    public function project_member_can_view_kanban_for_activite(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/taches/activite/{$this->activite->id}/kanban");

        $response->assertOk()
            ->assertJsonStructure(['a_faire', 'en_cours', 'termine', 'stats']);
    }

    /** @test */
    public function workspace_member_can_view_kanban_of_public_projet(): void
    {
        $member = $this->makeWsMember('collaborateur');

        $response = $this->actingAs($member)
            ->getJson("/api/taches/activite/{$this->activite->id}/kanban");

        $response->assertOk()
            ->assertJsonStructure(['a_faire', 'en_cours', 'termine', 'stats']);
    }

    /** @test */
    public function outsider_cannot_view_kanban(): void
    {
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)
            ->getJson("/api/taches/activite/{$this->activite->id}/kanban");

        $response->assertForbidden();
    }

    /** @test */
    public function kanban_stats_reflect_tache_counts(): void
    {
        Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'responsable_id' => $this->owner->id,
            'statut' => 'a_faire',
            'titre' => 'Tâche à faire',
        ]);

        Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'responsable_id' => $this->owner->id,
            'statut' => 'en_cours',
            'titre' => 'Tâche en cours',
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/taches/activite/{$this->activite->id}/kanban");

        $response->assertOk()
            ->assertJsonPath('stats.a_faire', 1)
            ->assertJsonPath('stats.en_cours', 1)
            ->assertJsonPath('stats.termine', 0)
            ->assertJsonPath('stats.total', 2);
    }

    // =========================================================================
    // GET TACHES (ActiviteController::getTaches)
    // =========================================================================

    /** @test */
    public function project_member_can_get_taches_for_activite(): void
    {
        Tache::factory()->create([
            'activite_id' => $this->activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson("/api/activites/{$this->activite->id}/taches");

        $response->assertOk()
            ->assertJsonStructure(['data'])
            ->assertJsonCount(1, 'data');
    }

    /** @test */
    public function outsider_cannot_get_taches_for_activite(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/activites/{$this->activite->id}/taches")
            ->assertForbidden();
    }

    // =========================================================================
    // FOR PROJET (ActiviteController::forProjet)
    // =========================================================================

    /** @test */
    public function project_member_can_list_activites_for_project(): void
    {
        $response = $this->actingAs($this->owner)
            ->getJson("/api/activites/projet/{$this->projet->id}");

        $response->assertOk()
            ->assertJsonStructure(['data']);

        $ids = collect($response->json('data'))->pluck('id');
        $this->assertTrue($ids->contains($this->activite->id));
    }
}
