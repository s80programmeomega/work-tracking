<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Jobs\TransmettreResultatAuN1Job;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Models\Workspace;
use App\Services\TacheResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class N0ValidationCircuitTest extends TestCase
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
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $responsable = User::factory()->create();
        $intervenant = User::factory()->create();

        // Responsable: is_responsable = true on tache_user
        $this->attachWithRole($tache->assignees(), $responsable->id, 'collaborateur', [
            'is_responsable' => true,
            'can_edit' => true,
            'can_complete' => true,
            'can_validate' => true,
            'statut_individuel' => 'a_faire',
            'progression_individuelle' => 0,
        ]);

        // Intervenant: regular assignee
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
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
            'taux_realisation' => 80,
        ]);

        return compact('workspace', 'tache', 'responsable', 'intervenant', 'resultat');
    }

    /** @test */
    public function soumettre_dispatches_job_with_workspace_timeout(): void
    {
        Queue::fake();

        ['tache' => $tache, 'intervenant' => $intervenant] = $this->makeContext();

        $resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $intervenant->id,
            'statut' => 'brouillon',
            'taux_realisation' => 60,
        ]);

        $service = app(TacheResultatService::class);
        $service->soumettre($resultat, $intervenant);

        Queue::assertPushed(TransmettreResultatAuN1Job::class, function ($job) use ($resultat) {
            return $job->tacheResultatId === $resultat->id;
        });

        $this->assertEquals('en_verification_n0', $resultat->fresh()->statut);
    }

    /** @test */
    public function approuver_n0_sets_statut_and_writes_audit_log(): void
    {
        ['responsable' => $responsable, 'resultat' => $resultat] = $this->makeContext();

        $service = app(TacheResultatService::class);
        $service->approuverN0($resultat, $responsable);

        $this->assertEquals('en_validation_n1', $resultat->fresh()->statut);
        $this->assertEquals('approuve', $resultat->fresh()->action_n0);
        $this->assertDatabaseHas('validation_audit_logs', [
            'tache_resultat_id' => $resultat->id,
            'actor_id' => $responsable->id,
            'action' => 'approuve',
        ]);
    }

    /** @test */
    public function renvoyer_n0_with_short_comment_throws_422(): void
    {
        ['responsable' => $responsable, 'resultat' => $resultat] = $this->makeContext();

        $response = $this->actingAs($responsable, 'sanctum')
            ->postJson("/api/taches/{$resultat->tache_id}/resultats/{$resultat->id}/renvoyer-n0", [
                'commentaire' => 'Trop court',
            ]);

        $response->assertStatus(422);
        $this->assertEquals('en_verification_n0', $resultat->fresh()->statut);
    }

    /** @test */
    public function renvoyer_n0_with_valid_comment_sets_statut_a_refaire(): void
    {
        ['responsable' => $responsable, 'resultat' => $resultat] = $this->makeContext();

        $service = app(TacheResultatService::class);
        $service->renvoyerN0($resultat, $responsable, 'Ce résultat est insuffisant, veuillez détailler davantage les points bloquants rencontrés.');

        $this->assertEquals('a_refaire', $resultat->fresh()->statut);
        $this->assertEquals('renvoye', $resultat->fresh()->action_n0);
        $this->assertDatabaseHas('validation_audit_logs', [
            'tache_resultat_id' => $resultat->id,
            'actor_id' => $responsable->id,
            'action' => 'renvoye',
        ]);
    }

    /** @test */
    public function transmettre_au_n1_is_skipped_when_n0_already_acted(): void
    {
        ['resultat' => $resultat] = $this->makeContext();

        // Simulate N0 already approved
        $resultat->update(['action_n0' => 'approuve', 'statut' => 'en_validation_n1']);

        $service = app(TacheResultatService::class);
        $service->transmettreAuN1($resultat);

        // Statut must stay unchanged, no new audit log with action=timeout
        $this->assertEquals('en_validation_n1', $resultat->fresh()->statut);
        $this->assertDatabaseMissing('validation_audit_logs', [
            'tache_resultat_id' => $resultat->id,
            'action' => 'timeout',
        ]);
    }

    /** @test */
    public function unauthorized_user_gets_403_on_approuver_n0(): void
    {
        ['intervenant' => $intervenant, 'resultat' => $resultat] = $this->makeContext();

        // Intervenant is NOT the is_responsable — should be blocked
        $response = $this->actingAs($intervenant, 'sanctum')
            ->postJson("/api/taches/{$resultat->tache_id}/resultats/{$resultat->id}/approuver-n0");

        $response->assertStatus(403);
        $this->assertEquals('en_verification_n0', $resultat->fresh()->statut);
    }

    /** @test */
    public function audit_log_has_no_updated_at_column(): void
    {
        ['responsable' => $responsable, 'resultat' => $resultat] = $this->makeContext();

        $service = app(TacheResultatService::class);
        $service->approuverN0($resultat, $responsable);

        $log = ValidationAuditLog::where('tache_resultat_id', $resultat->id)->first();
        $this->assertNotNull($log);
        $this->assertFalse($log->timestamps); // immutable — no updated_at
    }
}
