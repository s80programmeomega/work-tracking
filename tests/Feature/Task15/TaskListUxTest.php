<?php

declare(strict_types=1);

namespace Tests\Feature\Task15;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for Task 15 — Task List UX.
 *
 * Covers:
 *  - PATCH /api/taches/{id} accepts statut inline update
 *  - PATCH /api/taches/{id} accepts priorite inline update
 *  - PATCH /api/taches/{id} accepts echeance inline update
 *  - PATCH /api/taches/{id} returns 403 when user has no edit permission
 *  - GET /api/taches accepts activite_id query param (deep-link from ActiviteDetail)
 */
class TaskListUxTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function ownerWithTache(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $owner->id,
        ]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $owner->id,
        ]);
        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'a_faire',
            'priorite' => 'moyenne',
        ]);

        return compact('owner', 'workspace', 'activite', 'tache');
    }

    public function test_inline_edit_statut_updates_tache(): void
    {
        ['owner' => $owner, 'tache' => $tache] = $this->ownerWithTache();

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/taches/{$tache->id}", [
            'statut' => 'en_cours',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('taches', ['id' => $tache->id, 'statut' => 'en_cours']);
    }

    public function test_inline_edit_priorite_updates_tache(): void
    {
        ['owner' => $owner, 'tache' => $tache] = $this->ownerWithTache();

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/taches/{$tache->id}", [
            'priorite' => 'elevee',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('taches', ['id' => $tache->id, 'priorite' => 'elevee']);
    }

    public function test_inline_edit_echeance_updates_tache(): void
    {
        ['owner' => $owner, 'tache' => $tache] = $this->ownerWithTache();

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/taches/{$tache->id}", [
            'echeance' => '2026-12-31',
        ]);

        $response->assertStatus(200);
        $this->assertDatabaseHas('taches', ['id' => $tache->id, 'echeance' => '2026-12-31']);
    }

    public function test_inline_edit_blocked_for_unauthorized_user(): void
    {
        ['tache' => $tache] = $this->ownerWithTache();

        $stranger = User::factory()->create();
        Sanctum::actingAs($stranger);

        $response = $this->patchJson("/api/taches/{$tache->id}", [
            'statut' => 'termine',
        ]);

        $response->assertStatus(403);
    }

    public function test_taches_endpoint_accepts_activite_id_filter(): void
    {
        ['owner' => $owner, 'activite' => $activite] = $this->ownerWithTache();

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/taches?activite_id={$activite->id}");

        $response->assertStatus(200);
    }
}
