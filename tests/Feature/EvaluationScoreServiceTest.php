<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ScoreUpdatedNotification;
use App\Services\EvaluationScoreService;
use App\Services\TacheResultatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class EvaluationScoreServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        Notification::fake();
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
        ]);
        $this->attachWithRole($tache->assignees(), $intervenant->id, 'collaborateur', [
            'is_responsable' => false,
        ]);

        return compact('workspace', 'tache', 'responsable', 'intervenant', 'n1');
    }

    /** @test */
    public function validated_despite_return_creates_penalty_row(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);

        $service = app(EvaluationScoreService::class);
        $score = $service->calculerImpactN1($resultat, 'validated_despite_return', $ctx['n1']);

        $this->assertNotNull($score);
        $this->assertSame(EvaluationScoreService::PENALTY, (float) $score->valeur);
        $this->assertSame(EvaluationScoreService::CRITERE_VALIDATED_DESPITE_RETURN, $score->critere);
        $this->assertSame($ctx['responsable']->id, $score->user_id);
        $this->assertDatabaseHas('validation_audit_logs', [
            'tache_resultat_id' => $resultat->id,
            'action' => 'n1_validated_despite_return',
        ]);
        Notification::assertSentTo($ctx['responsable'], ScoreUpdatedNotification::class);
    }

    /** @test */
    public function confirmed_return_creates_bonus_row(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);

        $service = app(EvaluationScoreService::class);
        $score = $service->calculerImpactN1($resultat, 'confirmed_return', $ctx['n1']);

        $this->assertSame(EvaluationScoreService::BONUS, (float) $score->valeur);
        $this->assertSame(EvaluationScoreService::CRITERE_CONFIRMED_RETURN, $score->critere);
    }

    /** @test */
    public function no_impact_returns_null_and_writes_nothing(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);

        $service = app(EvaluationScoreService::class);
        $result = $service->calculerImpactN1($resultat, 'no_impact', $ctx['n1']);

        $this->assertNull($result);
        $this->assertDatabaseCount('evaluation_scores', 0);
    }

    /** @test */
    public function unknown_decision_throws_invalid_argument_exception(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);

        $service = app(EvaluationScoreService::class);

        $this->expectException(\InvalidArgumentException::class);
        $service->calculerImpactN1($resultat, 'invalid_decision', $ctx['n1']);
    }

    /** @test */
    public function total_for_user_sums_deltas_across_decisions(): void
    {
        $ctx = $this->makeContext();
        $service = app(EvaluationScoreService::class);

        $r1 = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);
        $service->calculerImpactN1($r1, 'confirmed_return', $ctx['n1']);

        $r2 = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);
        $service->calculerImpactN1($r2, 'confirmed_return', $ctx['n1']);

        $r3 = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
        ]);
        $service->calculerImpactN1($r3, 'validated_despite_return', $ctx['n1']);

        // +1 +1 -1 = +1
        $this->assertSame(1.0, $service->totalForUser($ctx['responsable']));
    }

    /** @test */
    public function valider_n1_on_returned_result_creates_penalty_via_controller(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->renvoyeParN0($ctx['responsable']->id)->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHour(),
        ]);

        $service = app(TacheResultatService::class);
        $service->validerN1($resultat, $ctx['n1'], 'OK pour moi');

        $this->assertDatabaseHas('evaluation_scores', [
            'user_id' => $ctx['responsable']->id,
            'critere' => EvaluationScoreService::CRITERE_VALIDATED_DESPITE_RETURN,
        ]);
    }

    /** @test */
    public function valider_n1_on_normal_result_writes_no_score_row(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHour(),
        ]);

        $service = app(TacheResultatService::class);
        $service->validerN1($resultat, $ctx['n1']);

        $this->assertDatabaseCount('evaluation_scores', 0);
    }

    /** @test */
    public function rejeter_n1_on_bypassed_result_increments_bypass_count_and_writes_bonus(): void
    {
        $ctx = $this->makeContext();
        $resultat = TacheResultat::factory()->bypassActive()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
            'soumis_le' => now()->subHour(),
        ]);

        $service = app(TacheResultatService::class);
        $service->rejeterN1($resultat, $ctx['n1'], 'Renvoi confirmé');

        $pivot = $ctx['tache']->assignees()->where('user_id', $ctx['intervenant']->id)->first()->pivot;
        $this->assertSame(1, (int) $pivot->bypass_count);

        $this->assertDatabaseHas('evaluation_scores', [
            'user_id' => $ctx['responsable']->id,
            'critere' => EvaluationScoreService::CRITERE_CONFIRMED_RETURN,
        ]);
    }

    /** @test */
    public function pending_validations_dashboard_returns_403_without_permission(): void
    {
        $ctx = $this->makeContext();

        // Intervenant has no EVALUATIONS_VIEW_PENDING
        $ctx['intervenant']->update(['current_workspace_id' => $ctx['workspace']->id]);

        $response = $this->actingAs($ctx['intervenant'])
            ->getJson('/api/evaluations/validations/en-attente');

        $response->assertStatus(403);
    }

    /** @test */
    public function pending_validations_dashboard_returns_data_for_workspace_owner(): void
    {
        $ctx = $this->makeContext();
        // Make n1 the workspace owner — owner contextual role has EVALUATIONS_VIEW_PENDING.
        $ctx['workspace']->update(['owner_id' => $ctx['n1']->id]);
        $ctx['n1']->update(['current_workspace_id' => $ctx['workspace']->id]);

        // Create a pending N1 result
        TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['intervenant']->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHours(2),
            'action_n0_le' => now()->subHour(),
        ]);

        $response = $this->actingAs($ctx['n1'])
            ->getJson('/api/evaluations/validations/en-attente');

        $response->assertOk()
            ->assertJsonPath('data.counts.n1', 1)
            ->assertJsonStructure(['data' => ['pending_n1', 'pending_n2', 'counts', 'timeout_hours']]);
    }
}
