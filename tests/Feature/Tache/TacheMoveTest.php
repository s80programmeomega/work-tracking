<?php

declare(strict_types=1);

namespace Tests\Feature\Tache;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre l'endpoint de déplacement Kanban d'une tâche :
 * POST /{tache}/move (TacheController::move).
 */
class TacheMoveTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private Tache $tache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);

        $projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
            'visibility' => 'public',
        ]);

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
            'statut' => 'a_faire',
        ]);
    }

    // =========================================================================
    // MOVE
    // =========================================================================

    /** @test */
    public function authorized_user_can_move_tache_to_en_cours(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/move", [
                'statut' => 'en_cours',
                'position' => 0,
            ]);

        $response->assertOk()
            ->assertJsonPath('message', 'Tâche déplacée avec succès.');

        $this->assertDatabaseHas('taches', [
            'id' => $this->tache->id,
            'statut' => 'en_cours',
        ]);
    }

    /** @test */
    public function authorized_user_can_move_tache_to_termine(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/move", [
                'statut' => 'termine',
                'position' => 0,
            ]);

        $response->assertOk();

        $this->assertDatabaseHas('taches', [
            'id' => $this->tache->id,
            'statut' => 'termine',
        ]);
    }

    /** @test */
    public function move_validates_statut_values(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/move", [
                'statut' => 'invalide',
                'position' => 0,
            ])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['statut']);
    }

    /** @test */
    public function move_requires_statut_and_position(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/taches/{$this->tache->id}/move", [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['statut', 'position']);
    }

    /** @test */
    public function unauthorized_user_cannot_move_tache(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->postJson("/api/taches/{$this->tache->id}/move", [
                'statut' => 'en_cours',
                'position' => 0,
            ])
            ->assertForbidden();
    }
}
