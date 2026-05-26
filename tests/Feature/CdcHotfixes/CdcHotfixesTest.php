<?php

declare(strict_types=1);

namespace Tests\Feature\CdcHotfixes;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Models\Workspace;
use App\Services\TacheResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * CDC Hotfixes — R7 + audit-log endpoint + agent sheet §5
 *
 * Covers:
 *   - R7: soumission bloquée si sous-tâche obligatoire non terminée
 *   - R7: soumission autorisée si toutes les sous-tâches obligatoires sont terminées
 *   - R7: sous-tâches non obligatoires n'empêchent pas la soumission
 *   - Audit-log endpoint : 200 pour un assigné de la tâche
 *   - Audit-log endpoint : 403 pour un utilisateur sans accès
 *   - Agent sheet §5 : submitted_results retourne les résultats soumis avec statut
 */
class CdcHotfixesTest extends TestCase
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

        $resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $intervenant->id,
            'statut' => 'brouillon',
            'taux_realisation' => 80,
        ]);

        return compact('workspace', 'activite', 'tache', 'responsable', 'intervenant', 'resultat', 'n1');
    }

    // =========================================================================
    // R7 — sous-tâches obligatoires bloquantes
    // =========================================================================

    public function test_r7_soumettre_bloque_si_sous_tache_obligatoire_non_terminee(): void
    {
        $ctx = $this->makeContext();

        // Sous-tâche obligatoire en cours — doit bloquer
        SousTache::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'statut' => 'en_cours',
            'validation_n0_required' => true,
        ]);

        $service = app(TacheResultatService::class);

        $this->expectException(\InvalidArgumentException::class);

        $service->soumettre($ctx['resultat'], $ctx['intervenant']);
    }

    public function test_r7_soumettre_autorise_si_sous_taches_obligatoires_terminees(): void
    {
        $ctx = $this->makeContext();

        // Sous-tâche obligatoire terminée — ne doit pas bloquer
        SousTache::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'statut' => 'termine',
            'validation_n0_required' => true,
        ]);

        $service = app(TacheResultatService::class);

        // Ne doit pas lever d'exception
        $service->soumettre($ctx['resultat'], $ctx['intervenant']);

        // Le job de timeout s'exécute en sync dans les tests — soumis_le prouve que soumettre() a bien tourné
        $this->assertDatabaseHas('tache_resultats', [
            'id' => $ctx['resultat']->id,
        ]);
        $this->assertNotNull($ctx['resultat']->fresh()->soumis_le);
    }

    public function test_r7_sous_tache_non_obligatoire_nempêche_pas_soumission(): void
    {
        $ctx = $this->makeContext();

        // Sous-tâche NON obligatoire en cours — ne doit pas bloquer
        SousTache::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'statut' => 'en_cours',
            'validation_n0_required' => false,
        ]);

        $service = app(TacheResultatService::class);

        $service->soumettre($ctx['resultat'], $ctx['intervenant']);

        $this->assertNotNull($ctx['resultat']->fresh()->soumis_le);
    }

    // =========================================================================
    // Audit-log endpoint — GET /api/audit-logs/validation/{tache}
    // =========================================================================

    public function test_audit_log_endpoint_retourne_200_pour_assigné(): void
    {
        $ctx = $this->makeContext();

        // Crée quelques entrées d'audit
        ValidationAuditLog::create([
            'tache_resultat_id' => $ctx['resultat']->id,
            'actor_id' => $ctx['intervenant']->id,
            'action' => 'soumis',
            'context' => ['taux_realisation' => 80],
            'created_at' => now(),
        ]);

        Sanctum::actingAs($ctx['intervenant']);

        $response = $this->getJson('/api/audit-logs/validation/'.$ctx['tache']->id);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    '*' => ['id', 'tache_resultat_id', 'action', 'context', 'created_at'],
                ],
            ])
            ->assertJsonPath('success', true)
            ->assertJsonCount(1, 'data');
    }

    public function test_audit_log_endpoint_retourne_403_pour_utilisateur_sans_accès(): void
    {
        $ctx = $this->makeContext();
        $outsider = User::factory()->create();

        Sanctum::actingAs($outsider);

        $response = $this->getJson('/api/audit-logs/validation/'.$ctx['tache']->id);

        $response->assertStatus(403);
    }

    // =========================================================================
    // Agent sheet §5 — submitted_results
    // =========================================================================

    public function test_agent_sheet_section_submitted_results_retourne_resultats_soumis(): void
    {
        $ctx = $this->makeContext();

        // Marque le résultat comme soumis
        $ctx['resultat']->update([
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
        ]);

        // Attache le workspace à l'intervenant pour la permission
        $ctx['workspace']->members()->attach($ctx['intervenant']->id, [
            'role_id' => $this->roleId('collaborateur'),
        ]);
        $ctx['intervenant']->update(['current_workspace_id' => $ctx['workspace']->id]);

        Sanctum::actingAs($ctx['intervenant']);

        $response = $this->getJson('/api/evaluations/personnel/'.$ctx['intervenant']->id.'/historique?'.http_build_query([
            'section' => 'submitted_results',
            'start' => now()->subDay()->toDateString(),
            'end' => now()->toDateString(),
        ]));

        $response->assertStatus(200)
            ->assertJsonPath('success', true)
            ->assertJsonPath('section', 'submitted_results')
            ->assertJsonCount(1, 'data');
    }
}
