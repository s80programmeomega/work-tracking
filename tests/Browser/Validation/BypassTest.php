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
use Tests\Browser\WorkTrackingTestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Browser tests for the Task 6 anti-sabotage bypass flow.
 *
 * These tests verify:
 * 1. The bypass button appears when statut = a_refaire and bypass not yet used
 * 2. A short motif shows the button disabled
 * 3. Activating bypass calls the API, the badge changes to "Bypass activé"
 */
class BypassTest extends WorkTrackingTestCase
{
    use AttachesWithRoleId, DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /**
     * Bypass submit button is visible and disabled when motif is empty.
     */
    public function test_bypass_button_visible_and_disabled_without_motif(): void
    {
        ['intervenant' => $intervenant, 'tache' => $tache, 'resultat' => $resultat] = $this->makeContext();

        $this->browse(function (Browser $browser) use ($intervenant, $tache) {
            $this->signInAs($browser, $intervenant);

            // L'onglet « Résultats » n'est pas actif par défaut (l'onglet
            // « Détails » l'est) — on doit cliquer dessus pour que
            // TacheResultsTab soit affiché et que le bandeau bypass
            // (statut a_refaire) soit rendu.
            $browser->visit("/taches/{$tache->id}")
                ->waitFor('[dusk="tab-results"]', 15)
                ->click('[dusk="tab-results"]')
                ->waitFor('[dusk="bypass-motif-input"]', 15)
                ->assertPresent('[dusk="bypass-submit-btn"]')
                ->assertAttributeContains('[dusk="bypass-submit-btn"]', 'disabled', '');
        });
    }

    /**
     * Activating bypass via the UI changes the display to "Bypass activé — transmis au N1".
     */
    public function test_bypass_activation_updates_ui_to_active_badge(): void
    {
        ['intervenant' => $intervenant, 'tache' => $tache, 'resultat' => $resultat] = $this->makeContext();
        $token = $intervenant->createToken('dusk-bypass')->plainTextToken;
        $motif = 'Mon résultat respecte entièrement les indicateurs. Le renvoi ne contient aucune justification technique valable et est injustifié.';

        $this->browse(function (Browser $browser) use ($intervenant, $tache, $resultat, $token, $motif) {
            $this->signInAs($browser, $intervenant);

            // Activate bypass via the API using fetch in the browser context.
            // L'onglet « Résultats » doit être actif pour que le bandeau
            // bypass soit rendu (statut a_refaire) — sinon le motif input
            // n'existe pas dans le DOM.
            $browser->visit("/taches/{$tache->id}")
                ->waitFor('[dusk="tab-results"]', 15)
                ->click('[dusk="tab-results"]')
                ->waitFor('[dusk="bypass-motif-input"]', 15)
                ->script([
                    "fetch('/api/taches/{$tache->id}/resultats/{$resultat->id}/activer-bypass', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Authorization': 'Bearer {$token}',
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        },
                        body: JSON.stringify({ motif: '{$motif}' })
                    }).then(r => r.json()).then(d => { window.__bypassResult = d; })",
                ]);

            // Wait for the response then reload to see updated state
            $browser->pause(2000)
                ->visit("/taches/{$tache->id}")
                ->waitFor('[dusk="tab-results"]', 15)
                ->click('[dusk="tab-results"]')
                ->waitFor('[dusk="bypass-active-badge"]', 15)
                ->assertPresent('[dusk="bypass-active-badge"]')
                ->assertMissing('[dusk="bypass-submit-btn"]');
        });
    }

    private function makeContext(): array
    {
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $n1 = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);
        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $n1->id,
        ]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);

        $responsable = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $intervenant = User::factory()->create(['current_workspace_id' => $workspace->id]);

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
            'statut' => 'a_refaire',
            'soumis_le' => now()->subHours(2),
            'soumis_n0_le' => now()->subHours(2),
            'action_n0' => 'renvoye',
            'commentaire_n0' => 'Ce résultat ne respecte pas les indicateurs du cahier des charges.',
            'n0_actor_id' => $responsable->id,
            'action_n0_le' => now()->subHour(),
            'taux_realisation' => 75,
        ]);

        return compact('workspace', 'tache', 'responsable', 'intervenant', 'resultat', 'n1');
    }
}
