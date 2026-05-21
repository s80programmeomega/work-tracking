<?php

declare(strict_types=1);

namespace Tests\Browser\Validation;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour Task 5 — circuit de validation N0 (surface UI).
 *
 * Ces tests vérifient que les résultats N0 du circuit sont correctement
 * exposés par l'API et reflétés dans le compteur de l'onglet « Résultats »
 * de la page de détail d'une tâche. Cela couvre la chaîne complète
 * backend → resource → frontend pour les statuts introduits par Task 5
 * (en_verification_n0, a_refaire).
 *
 * Note d'historique : Task 5 a été livrée sans couverture Dusk. Ce
 * test fait partie de la mise à niveau « chore/dusk-catchup » (Guide 18).
 *
 * Limite connue (non bloquante, à corriger ailleurs) :
 *   `tache.my_result` et `tache.all_results` ne sont pas populés dans la
 *   réponse de `TacheResource` même quand l'utilisateur authentifié est
 *   bien l'auteur d'un résultat existant. Le compteur stats.resultats_count
 *   reflète bien la donnée. C'est un écart de forme dans la resource —
 *   à traiter dans un PR dédié, pas dans le rattrapage Dusk.
 */
class N0ValidationTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * Helper — construit le contexte minimal et retourne l'owner avec sa tâche.
     *
     * @return array{owner: User, tache: Tache}
     */
    private function makeOwnerWithTask(): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $owner->id,
        ]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        // Le owner est attaché comme cadre sur l'activité et la tâche
        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $activite->membres()->attach($owner->id, ['role_id' => $cadreRoleId]);
        $tache->assignees()->attach($owner->id, [
            'role_id' => $cadreRoleId,
            'is_responsable' => true,
            'can_edit' => true,
            'can_complete' => true,
        ]);

        return ['owner' => $owner, 'tache' => $tache];
    }

    /**
     * Un résultat en attente N0 est compté dans l'onglet Résultats du détail de tâche.
     *
     * Le compteur affiché à côté de « Résultats » dans la barre d'onglets vient
     * directement de la propriété `additional_info.stats.resultats_count` retournée
     * par l'API. Si ce compteur affiche bien 1, cela prouve que :
     *   - l'API trouve le résultat associé à la tâche
     *   - le statut `en_verification_n0` introduit par Task 5 ne casse pas le comptage
     *   - le frontend reçoit et binde correctement la donnée
     */
    public function test_pending_n0_result_appears_in_results_tab_counter(): void
    {
        $ctx = $this->makeOwnerWithTask();

        TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['owner']->id,
            'is_individual' => true,
            'statut' => 'en_verification_n0',
            'soumis_le' => now(),
            'soumis_n0_le' => now(),
            'taux_realisation' => 80,
            'resultats_obtenus' => 'Test result body',
        ]);

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-results-count"]', 15)
                ->assertSeeIn('@tab-results-count', '1');
        });
    }

    /**
     * Un résultat renvoyé par N0 (statut a_refaire) est lui aussi compté dans
     * l'onglet Résultats. Le compteur ne distingue pas par statut — il compte
     * tous les résultats du tache, peu importe leur position dans le circuit.
     * Ce test vérifie que le statut `a_refaire` introduit par Task 5 ne fait
     * pas disparaître le résultat des aggrégations.
     */
    public function test_a_refaire_result_still_counted_in_results_tab(): void
    {
        $ctx = $this->makeOwnerWithTask();

        TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['owner']->id,
            'is_individual' => true,
            'statut' => 'a_refaire',
            'soumis_le' => now()->subHours(2),
            'soumis_n0_le' => now()->subHours(2),
            'action_n0' => 'renvoye',
            'commentaire_n0' => 'Indicateurs incomplets — merci de revoir.',
            'action_n0_le' => now()->subHour(),
            'taux_realisation' => 60,
            'resultats_obtenus' => 'Premier jet',
        ]);

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-results-count"]', 15)
                ->assertSeeIn('@tab-results-count', '1');
        });
    }
}
