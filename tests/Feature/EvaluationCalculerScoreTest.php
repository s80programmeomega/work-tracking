<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\EvaluationScore;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Services\EvaluationScoreService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 9 — couverture unitaire de EvaluationScoreService::calculerScore().
 *
 * Une assertion ciblée par critère pour faciliter le diagnostic en cas
 * de drift de formule (vs un mega-test multi-assertions). Tous les
 * helpers de contexte sont privés à ce fichier pour ne pas polluer la
 * surface de tests existante.
 */
class EvaluationCalculerScoreTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private EvaluationScoreService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->service = app(EvaluationScoreService::class);
    }

    /** @test */
    public function completion_rate_uses_subtask_coefficient_of_half(): void
    {
        // 2 tâches assignées dont 1 terminée + 4 sous-tâches dont 2 terminées
        // Pondéré: (1 + 2 × 0.5) / (2 + 4 × 0.5) = 2 / 4 = 0.5
        $user = $this->makeAssignedUser(tachesTotal: 2, tachesDone: 1, sousTotal: 4, sousDone: 2);

        $score = $this->service->calculerScore($user);

        $this->assertEqualsWithDelta(
            0.5,
            $score['criteria'][EvaluationScoreService::CRITERION_COMPLETION]['raw'],
            0.001
        );
    }

    /** @test */
    public function deadline_respect_counts_results_submitted_before_echeance(): void
    {
        $ctx = $this->makeBaseContext();
        // Tâche avec échéance hier, résultat soumis aujourd'hui → en retard
        $tacheLate = $this->makeTache($ctx, ['echeance' => now()->subDay()]);
        $this->attachWithRole($tacheLate->assignees(), $ctx['user']->id, 'collaborateur');
        TacheResultat::factory()->create([
            'tache_id' => $tacheLate->id,
            'user_id' => $ctx['user']->id,
            'soumis_le' => now(),
        ]);

        // Tâche avec échéance demain, résultat soumis aujourd'hui → à temps
        $tacheOnTime = $this->makeTache($ctx, ['echeance' => now()->addDay()]);
        $this->attachWithRole($tacheOnTime->assignees(), $ctx['user']->id, 'collaborateur');
        TacheResultat::factory()->create([
            'tache_id' => $tacheOnTime->id,
            'user_id' => $ctx['user']->id,
            'soumis_le' => now(),
        ]);

        $score = $this->service->calculerScore($ctx['user']);

        // 1 livraison à temps / 2 soumissions = 0.5
        $this->assertEqualsWithDelta(
            0.5,
            $score['criteria'][EvaluationScoreService::CRITERION_DEADLINE]['raw'],
            0.001
        );
    }

    /** @test */
    public function result_quality_uses_n1_validation_rate_as_proxy(): void
    {
        $ctx = $this->makeBaseContext();
        // 3 résultats soumis, 2 validés N1 → 2/3.
        // valide_par_n1 est un booléen (flag de validation N1), pas un id.
        for ($i = 0; $i < 3; $i++) {
            $tache = $this->makeTache($ctx);
            $this->attachWithRole($tache->assignees(), $ctx['user']->id, 'collaborateur');
            TacheResultat::factory()->create([
                'tache_id' => $tache->id,
                'user_id' => $ctx['user']->id,
                'soumis_le' => now(),
                'valide_par_n1' => $i < 2,
            ]);
        }

        $score = $this->service->calculerScore($ctx['user']);

        $this->assertEqualsWithDelta(
            2 / 3,
            $score['criteria'][EvaluationScoreService::CRITERION_QUALITY]['raw'],
            0.001
        );
    }

    /** @test */
    public function first_pass_validation_excludes_returned_and_bypass_paths(): void
    {
        $ctx = $this->makeBaseContext();
        // valide_par_n1 est un booléen — on passe `true` (la table n'a
        // pas de colonne pour l'auteur de la validation N1).
        $this->createResultat($ctx, ['valide_par_n1' => true, 'action_n0' => 'approuve']);   // 1st pass
        $this->createResultat($ctx, ['valide_par_n1' => true, 'action_n0' => 'renvoye']);    // pas 1st pass
        $this->createResultat($ctx, ['valide_par_n1' => true, 'bypass_active' => true]);     // pas 1st pass

        $score = $this->service->calculerScore($ctx['user']);

        // 1 first-pass / 3 validés N1 = 0.333…
        $this->assertEqualsWithDelta(
            1 / 3,
            $score['criteria'][EvaluationScoreService::CRITERION_FIRST_PASS]['raw'],
            0.001
        );
    }

    /** @test */
    public function justified_returns_reads_from_evaluation_scores_rows(): void
    {
        $ctx = $this->makeBaseContext();
        // 3 décisions N1 enregistrées contre ce responsable
        EvaluationScore::factory()->forUser($ctx['user'])->bonus()->create();    // confirmed
        EvaluationScore::factory()->forUser($ctx['user'])->bonus()->create();    // confirmed
        EvaluationScore::factory()->forUser($ctx['user'])->penalty()->create();  // unjustified

        // Période = mois courant pour englober les lignes EvaluationScore
        // créées par la factory (periode_start..end = startOfMonth..endOfMonth).
        $score = $this->service->calculerScore(
            $ctx['user'],
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        // 2 confirmés / 3 décisions = 0.666…
        $this->assertEqualsWithDelta(
            2 / 3,
            $score['criteria'][EvaluationScoreService::CRITERION_JUSTIFIED_RETURNS]['raw'],
            0.001
        );
    }

    /** @test */
    public function inactions_penalises_n0_timeouts(): void
    {
        $ctx = $this->makeBaseContext();
        // Crée 4 résultats sur des tâches où user est responsable, dont 1 en timeout
        $tache = $this->makeTache($ctx);
        $this->attachWithRole($tache->assignees(), $ctx['user']->id, 'collaborateur', [
            'is_responsable' => true,
        ]);
        foreach (['approuve', 'approuve', 'approuve', 'timeout'] as $action) {
            TacheResultat::factory()->create([
                'tache_id' => $tache->id,
                'user_id' => User::factory()->create()->id,
                'action_n0' => $action,
                'action_n0_le' => now(),
            ]);
        }

        $score = $this->service->calculerScore($ctx['user']);

        // 1 timeout / 4 décisions → raw = 1 - 0.25 = 0.75
        $this->assertEqualsWithDelta(
            0.75,
            $score['criteria'][EvaluationScoreService::CRITERION_INACTIONS]['raw'],
            0.001
        );
    }

    /** @test */
    public function work_volume_normalises_by_ceiling(): void
    {
        // 10 tâches + 20 sous-tâches → 10 + 20×0.5 = 20 → 20/50 = 0.4
        $user = $this->makeAssignedUser(tachesTotal: 10, tachesDone: 0, sousTotal: 20, sousDone: 0);

        $score = $this->service->calculerScore($user);

        $this->assertEqualsWithDelta(
            0.4,
            $score['criteria'][EvaluationScoreService::CRITERION_VOLUME]['raw'],
            0.001
        );
    }

    /** @test */
    public function team_coordination_averages_completion_of_co_assignees(): void
    {
        $ctx = $this->makeBaseContext();
        // Tâche dont user est responsable, avec 2 co-assignés dont 1 terminé → 0.5
        $tache = $this->makeTache($ctx);
        $this->attachWithRole($tache->assignees(), $ctx['user']->id, 'collaborateur', [
            'is_responsable' => true,
        ]);
        $this->attachWithRole($tache->assignees(), User::factory()->create()->id, 'collaborateur', [
            'statut_individuel' => 'termine',
        ]);
        $this->attachWithRole($tache->assignees(), User::factory()->create()->id, 'collaborateur', [
            'statut_individuel' => 'a_faire',
        ]);

        $score = $this->service->calculerScore($ctx['user']);

        $this->assertEqualsWithDelta(
            0.5,
            $score['criteria'][EvaluationScoreService::CRITERION_COORDINATION]['raw'],
            0.001
        );
    }

    /** @test */
    public function global_score_is_weighted_sum_of_all_criteria(): void
    {
        // Sanity check: somme des poids = 1.0 et score global est bien la
        // somme pondérée des raw values renvoyées.
        $ctx = $this->makeBaseContext();

        $score = $this->service->calculerScore($ctx['user']);

        $this->assertEqualsWithDelta(1.0, array_sum(EvaluationScoreService::CRITERIA_WEIGHTS), 0.001);

        $expectedGlobal = 0.0;
        foreach ($score['criteria'] as $c) {
            $expectedGlobal += $c['raw'] * $c['weight'];
        }
        $this->assertEqualsWithDelta($expectedGlobal, $score['score_global'], 0.001);
    }

    /** @test */
    public function unjustified_return_indicator_fires_above_40_percent(): void
    {
        $ctx = $this->makeBaseContext();
        // 3 unjustified / 5 total = 60% > 40% → alert true
        EvaluationScore::factory()->forUser($ctx['user'])->penalty()->count(3)->create();
        EvaluationScore::factory()->forUser($ctx['user'])->bonus()->count(2)->create();

        $score = $this->service->calculerScore(
            $ctx['user'],
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        );

        $this->assertEqualsWithDelta(0.6, $score['indicators']['unjustified_return_rate'], 0.001);
        $this->assertTrue($score['indicators']['unjustified_alert']);
    }

    // ──────────────────────────────────────────────────────────────────
    //  Helpers
    // ──────────────────────────────────────────────────────────────────

    private function makeBaseContext(): array
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $user = User::factory()->create();

        return compact('workspace', 'projet', 'activite', 'user');
    }

    /**
     * Crée une tâche avec un titre unique pour contourner la contrainte
     * taches_activite_id_titre_unique quand un test crée plusieurs tâches
     * sous la même activité.
     */
    private function makeTache(array $ctx, array $overrides = []): Tache
    {
        return Tache::factory()->create(array_merge([
            'activite_id' => $ctx['activite']->id,
            'titre' => 'T-'.uniqid(),
        ], $overrides));
    }

    private function createResultat(array $ctx, array $overrides = []): TacheResultat
    {
        $tache = $this->makeTache($ctx);
        $this->attachWithRole($tache->assignees(), $ctx['user']->id, 'collaborateur');

        return TacheResultat::factory()->create(array_merge([
            'tache_id' => $tache->id,
            'user_id' => $ctx['user']->id,
            'soumis_le' => now(),
        ], $overrides));
    }

    /**
     * Construit un utilisateur avec un nombre précis de tâches/sous-tâches
     * assignées et terminées — utile pour les critères de complétion + volume.
     */
    private function makeAssignedUser(int $tachesTotal, int $tachesDone, int $sousTotal, int $sousDone): User
    {
        $ctx = $this->makeBaseContext();
        $user = $ctx['user'];

        for ($i = 0; $i < $tachesTotal; $i++) {
            // Titre unique par itération — la contrainte
            // taches_activite_id_titre_unique peut sinon collisionner
            // quand le test crée beaucoup de tâches sous la même activité.
            $tache = Tache::factory()->create([
                'activite_id' => $ctx['activite']->id,
                'titre' => "Test tache {$i}-".uniqid(),
            ]);
            $this->attachWithRole($tache->assignees(), $user->id, 'collaborateur', [
                'statut_individuel' => $i < $tachesDone ? 'termine' : 'a_faire',
            ]);
        }

        // Sous-tâches: une tâche parent unique (rattachée à l'activité
        // existante) sert de racine pour toutes les sous-tâches du test —
        // évite de recréer une nouvelle Tache + Activite à chaque itération.
        $parentTache = $this->makeTache($ctx);
        for ($i = 0; $i < $sousTotal; $i++) {
            $sousTache = SousTache::factory()->create(['tache_id' => $parentTache->id]);
            DB::table('sous_tache_user')->insert([
                'sous_tache_id' => $sousTache->id,
                'user_id' => $user->id,
                'statut_individuel' => $i < $sousDone ? 'termine' : 'a_faire',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $user;
    }
}
