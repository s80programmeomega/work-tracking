<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class SousTacheApiTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    private function makeContext(): array
    {
        $workspace = Workspace::factory()->create();
        $owner = User::factory()->create();
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        return compact('workspace', 'owner', 'activite', 'tache');
    }

    /** @test */
    public function authorized_user_can_list_sous_taches(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();
        SousTache::factory()->count(3)->create(['tache_id' => $tache->id]);

        $response = $this->actingAs($owner)->getJson("/api/taches/{$tache->id}/sous-taches");

        $response->assertOk()->assertJsonCount(3, 'data');
    }

    /** @test */
    public function authorized_user_can_create_sous_tache(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();

        $response = $this->actingAs($owner)->postJson("/api/taches/{$tache->id}/sous-taches", [
            'titre' => 'Ma sous-tâche',
            'poids' => 40,
        ]);

        $response->assertCreated()->assertJsonPath('data.titre', 'Ma sous-tâche');
        $this->assertDatabaseHas('sous_taches', ['tache_id' => $tache->id, 'titre' => 'Ma sous-tâche']);
    }

    /** @test */
    public function creating_sous_tache_with_exceeding_weights_returns_422(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();

        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 60]);
        SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 30]);

        // 60 + 30 + 20 = 110 → 422
        $response = $this->actingAs($owner)->postJson("/api/taches/{$tache->id}/sous-taches", [
            'titre' => 'Trop lourde',
            'poids' => 20,
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function authorized_user_can_update_sous_tache(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();
        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);

        $response = $this->actingAs($owner)->putJson("/api/sous-taches/{$sousTache->id}", [
            'titre' => 'Titre modifié',
            'statut' => 'en_cours',
        ]);

        $response->assertOk()->assertJsonPath('data.titre', 'Titre modifié');
    }

    /** @test */
    public function authorized_user_can_delete_sous_tache(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();
        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);

        $response = $this->actingAs($owner)->deleteJson("/api/sous-taches/{$sousTache->id}");

        $response->assertOk();
        $this->assertSoftDeleted('sous_taches', ['id' => $sousTache->id]);
    }

    /** @test */
    public function parent_task_taux_realisation_recalculates_when_sous_tache_updated(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();

        // 3 weighted sous-tâches: 40/35/25
        $st1 = SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 40, 'progression' => 100]);
        $st2 = SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 35, 'progression' => 0]);
        $st3 = SousTache::factory()->create(['tache_id' => $tache->id, 'poids' => 25, 'progression' => 0]);

        // Update st2 to 100%
        $this->actingAs($owner)->putJson("/api/sous-taches/{$st2->id}", ['progression' => 100]);

        // Expected: (40*100/100) + (35*100/100) + (25*0/100) = 75
        $tache->refresh();
        $this->assertEquals(75, $tache->taux_realisation);
    }

    /** @test */
    public function parent_task_auto_set_to_termine_when_all_sous_taches_done(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();

        $st1 = SousTache::factory()->create(['tache_id' => $tache->id, 'statut' => 'termine', 'progression' => 100]);
        $st2 = SousTache::factory()->create(['tache_id' => $tache->id, 'statut' => 'en_cours', 'progression' => 50]);

        // Complete the last one
        $this->actingAs($owner)->putJson("/api/sous-taches/{$st2->id}", [
            'statut' => 'termine',
            'progression' => 100,
        ]);

        $tache->refresh();
        $this->assertEquals('termine', $tache->statut->value);
    }

    /** @test */
    public function parent_task_auto_set_to_en_retard_when_sous_tache_overdue(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();

        $st1 = SousTache::factory()->create(['tache_id' => $tache->id, 'statut' => 'a_faire']);

        $this->actingAs($owner)->putJson("/api/sous-taches/{$st1->id}", ['statut' => 'en_retard']);

        $tache->refresh();
        $this->assertEquals('en_retard', $tache->statut->value);
    }

    /** @test */
    public function manual_statut_update_on_tache_with_sous_taches_is_blocked(): void
    {
        ['tache' => $tache, 'owner' => $owner] = $this->makeContext();
        SousTache::factory()->create(['tache_id' => $tache->id]);

        $response = $this->actingAs($owner)->putJson("/api/taches/{$tache->id}", [
            'statut' => 'termine',
        ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function unauthorized_user_gets_403_on_create(): void
    {
        ['tache' => $tache] = $this->makeContext();
        $outsider = User::factory()->create();

        $response = $this->actingAs($outsider)->postJson("/api/taches/{$tache->id}/sous-taches", [
            'titre' => 'Pas autorisé',
        ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function authorized_user_can_assign_intervenant_to_sous_tache(): void
    {
        ['workspace' => $workspace, 'tache' => $tache, 'owner' => $owner] = $this->makeContext();
        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);
        $member = User::factory()->create();
        $this->attachWithRole($workspace->members(), $member->id, 'cadre');

        $response = $this->actingAs($owner)->postJson("/api/sous-taches/{$sousTache->id}/intervenants", [
            'user_id' => $member->id,
        ]);

        $response->assertOk();
        $this->assertDatabaseHas('sous_tache_user', [
            'sous_tache_id' => $sousTache->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function authorized_user_can_remove_intervenant_from_sous_tache(): void
    {
        ['workspace' => $workspace, 'tache' => $tache, 'owner' => $owner] = $this->makeContext();
        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);
        $member = User::factory()->create();
        $this->attachWithRole($workspace->members(), $member->id, 'cadre');
        $sousTache->intervenants()->attach($member->id);

        $response = $this->actingAs($owner)->deleteJson("/api/sous-taches/{$sousTache->id}/intervenants/{$member->id}");

        $response->assertOk();
        $this->assertDatabaseMissing('sous_tache_user', [
            'sous_tache_id' => $sousTache->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function unauthorized_user_cannot_remove_intervenant_from_sous_tache(): void
    {
        ['workspace' => $workspace, 'tache' => $tache] = $this->makeContext();
        $sousTache = SousTache::factory()->create(['tache_id' => $tache->id]);
        $member = User::factory()->create();
        $outsider = User::factory()->create();
        $this->attachWithRole($workspace->members(), $member->id, 'cadre');
        $sousTache->intervenants()->attach($member->id);

        $response = $this->actingAs($outsider)->deleteJson("/api/sous-taches/{$sousTache->id}/intervenants/{$member->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('sous_tache_user', [
            'sous_tache_id' => $sousTache->id,
            'user_id' => $member->id,
        ]);
    }

    /** @test */
    public function creating_sous_tache_with_responsable_stores_responsable_id(): void
    {
        ['workspace' => $workspace, 'tache' => $tache, 'owner' => $owner] = $this->makeContext();
        $member = User::factory()->create();
        $this->attachWithRole($workspace->members(), $member->id, 'cadre');

        $response = $this->actingAs($owner)->postJson("/api/taches/{$tache->id}/sous-taches", [
            'titre' => 'Sous-tâche avec responsable',
            'responsable_id' => $member->id,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('sous_taches', [
            'tache_id' => $tache->id,
            'titre' => 'Sous-tâche avec responsable',
            'responsable_id' => $member->id,
        ]);
    }
}
