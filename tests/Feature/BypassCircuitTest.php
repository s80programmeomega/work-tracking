<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Services\TacheResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class BypassCircuitTest extends TestCase
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
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $n1 = User::factory()->create();
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $n1->id,
        ]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $responsable = User::factory()->create();
        $intervenant = User::factory()->create();

        $this->attachWithRole($tache->assignees(), $responsable->id, 'collaborateur', [
            'is_responsable' => true,
            'can_edit' => true,
            'can_complete' => true,
            'can_validate' => true,
            'statut_individuel' => 'a_faire',
            'progression_individuelle' => 0,
        ]);

        $this->attachWithRole($tache->assignees(), $intervenant->id, 'collaborateur', [
            'is_responsable' => false,
            'can_edit' => false,
            'can_complete' => true,
            'can_validate' => false,
            'statut_individuel' => 'a_faire',
            'progression_individuelle' => 0,
        ]);

        // Result already returned by N0
        $resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $intervenant->id,
            'statut' => 'a_refaire',
            'soumis_le' => now()->subHours(2),
            'soumis_n0_le' => now()->subHours(2),
            'action_n0' => 'renvoye',
            'commentaire_n0' => 'Ce résultat ne respecte pas les indicateurs définis dans le cahier des charges.',
            'n0_actor_id' => $responsable->id,
            'action_n0_le' => now()->subHour(),
            'taux_realisation' => 75,
        ]);

        return compact('workspace', 'activite', 'tache', 'responsable', 'intervenant', 'resultat', 'n1');
    }

    private function validMotif(): string
    {
        return 'Mon travail respecte entièrement les indicateurs définis. Le renvoi ne contient aucune justification technique valable.';
    }

    /** @test */
    public function activer_bypass_sets_statut_en_validation_n1_and_writes_audit_log(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);

        $service->activerBypass($ctx['resultat'], $ctx['intervenant'], $this->validMotif());

        $ctx['resultat']->refresh();
        $this->assertSame('en_validation_n1', $ctx['resultat']->statut);
        $this->assertTrue($ctx['resultat']->bypass_active);
        $this->assertNotNull($ctx['resultat']->bypass_le);
        $this->assertSame($this->validMotif(), $ctx['resultat']->motif_bypass);

        $this->assertDatabaseHas('validation_audit_logs', [
            'tache_resultat_id' => $ctx['resultat']->id,
            'actor_id' => $ctx['intervenant']->id,
            'action' => 'bypass',
        ]);
    }

    /** @test */
    public function activer_bypass_via_http_returns_200_with_bypass_fields(): void
    {
        $ctx = $this->makeContext();

        $response = $this->actingAs($ctx['intervenant'])
            ->postJson("/api/taches/{$ctx['tache']->id}/resultats/{$ctx['resultat']->id}/activer-bypass", [
                'motif' => $this->validMotif(),
            ]);

        $response->assertOk()
            ->assertJsonPath('data.statut', 'en_validation_n1')
            ->assertJsonPath('data.bypass.active', true);
    }

    /** @test */
    public function activer_bypass_twice_throws_domain_exception_r3(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);

        $service->activerBypass($ctx['resultat'], $ctx['intervenant'], $this->validMotif());

        $this->expectException(\DomainException::class);
        $service->activerBypass($ctx['resultat']->fresh(), $ctx['intervenant'], $this->validMotif());
    }

    /** @test */
    public function activer_bypass_twice_via_http_returns_409(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);
        $service->activerBypass($ctx['resultat'], $ctx['intervenant'], $this->validMotif());

        $response = $this->actingAs($ctx['intervenant'])
            ->postJson("/api/taches/{$ctx['tache']->id}/resultats/{$ctx['resultat']->id}/activer-bypass", [
                'motif' => $this->validMotif(),
            ]);

        $response->assertStatus(409);
    }

    /** @test */
    public function activer_bypass_with_short_motif_returns_422_r5(): void
    {
        $ctx = $this->makeContext();

        $response = $this->actingAs($ctx['intervenant'])
            ->postJson("/api/taches/{$ctx['tache']->id}/resultats/{$ctx['resultat']->id}/activer-bypass", [
                'motif' => 'Trop court.',
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function activer_bypass_by_non_author_returns_403(): void
    {
        $ctx = $this->makeContext();
        $stranger = User::factory()->create();

        $response = $this->actingAs($stranger)
            ->postJson("/api/taches/{$ctx['tache']->id}/resultats/{$ctx['resultat']->id}/activer-bypass", [
                'motif' => $this->validMotif(),
            ]);

        $response->assertStatus(403);
    }

    /** @test */
    public function activer_bypass_when_statut_is_not_a_refaire_returns_422(): void
    {
        $ctx = $this->makeContext();
        $ctx['resultat']->update(['statut' => 'en_verification_n0']);

        $response = $this->actingAs($ctx['intervenant'])
            ->postJson("/api/taches/{$ctx['tache']->id}/resultats/{$ctx['resultat']->id}/activer-bypass", [
                'motif' => $this->validMotif(),
            ]);

        $response->assertStatus(422);
    }

    /** @test */
    public function invalider_bypass_n1_sets_escalades_abusives_after_3_consecutive(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);

        for ($i = 1; $i <= 3; $i++) {
            $resultat = TacheResultat::factory()->create([
                'tache_id' => $ctx['tache']->id,
                'user_id' => $ctx['intervenant']->id,
                'statut' => 'a_refaire',
                'soumis_le' => now(),
                'bypass_active' => false,
            ]);

            $service->activerBypass($resultat, $ctx['intervenant'], $this->validMotif());
            $service->invaliderBypassN1($resultat->fresh(), $ctx['n1']);
        }

        $pivot = $ctx['tache']->assignees()
            ->where('user_id', $ctx['intervenant']->id)
            ->first()?->pivot;

        $this->assertNotNull($pivot);
        $this->assertSame(3, (int) $pivot->bypass_count);
        $this->assertTrue((bool) $pivot->escalades_abusives);
    }

    /** @test */
    public function escalades_abusives_not_set_after_only_2_consecutive(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);

        for ($i = 1; $i <= 2; $i++) {
            $resultat = TacheResultat::factory()->create([
                'tache_id' => $ctx['tache']->id,
                'user_id' => $ctx['intervenant']->id,
                'statut' => 'a_refaire',
                'soumis_le' => now(),
                'bypass_active' => false,
            ]);

            $service->activerBypass($resultat, $ctx['intervenant'], $this->validMotif());
            $service->invaliderBypassN1($resultat->fresh(), $ctx['n1']);
        }

        $pivot = $ctx['tache']->assignees()
            ->where('user_id', $ctx['intervenant']->id)
            ->first()?->pivot;

        $this->assertSame(2, (int) $pivot->bypass_count);
        $this->assertFalse((bool) $pivot->escalades_abusives);
    }

    /** @test */
    public function invalider_bypass_n1_writes_audit_log(): void
    {
        $ctx = $this->makeContext();
        $service = app(TacheResultatService::class);

        $service->activerBypass($ctx['resultat'], $ctx['intervenant'], $this->validMotif());
        $service->invaliderBypassN1($ctx['resultat']->fresh(), $ctx['n1']);

        $this->assertDatabaseHas('validation_audit_logs', [
            'tache_resultat_id' => $ctx['resultat']->id,
            'actor_id' => $ctx['n1']->id,
            'action' => 'bypass_invalide',
        ]);
    }
}
