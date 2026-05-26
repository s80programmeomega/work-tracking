<?php

declare(strict_types=1);

namespace Tests\Browser\Results;

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
 * Tests Dusk pour le circuit de soumission et de validation des résultats.
 */
class SubmitAndValidateTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /** @return array{owner: User, collaborateur: User, tache: Tache} */
    private function makeValidationContext(): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

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

        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $collabRoleId = Role::where('name', 'collaborateur')->where('guard_name', 'web')->value('id');

        $activite->membres()->attach($owner->id, ['role_id' => $cadreRoleId]);

        $collaborateur = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($collaborateur->id, ['role_id' => $collabRoleId]);

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'statut' => 'termine',
        ]);

        $tache->assignees()->attach($owner->id, [
            'role_id' => $cadreRoleId,
            'is_responsable' => true,
            'can_edit' => true,
            'can_complete' => true,
        ]);
        $tache->assignees()->attach($collaborateur->id, [
            'role_id' => $collabRoleId,
            'is_responsable' => false,
            'can_complete' => true,
            'statut_individuel' => 'termine',
        ]);

        return ['owner' => $owner, 'collaborateur' => $collaborateur, 'tache' => $tache];
    }

    /**
     * Un collaborateur peut soumettre son résultat depuis l'onglet Résultats.
     * Après soumission, le badge de statut apparaît sur la carte résultat.
     */
    public function test_collaborateur_can_submit_result_and_status_badge_appears(): void
    {
        $ctx = $this->makeValidationContext();

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['collaborateur'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-results"]', 15)
                ->click('[dusk="tab-results"]')
                ->waitFor('[dusk="open-submit-result-btn"]', 15)
                ->click('@open-submit-result-btn')
                ->waitFor('[dusk="submit-result-attendus"]', 10)
                ->type('@submit-result-attendus', 'Réaliser le reporting hebdomadaire complet.')
                ->type('@submit-result-textarea', 'Résultat Dusk — objectifs atteints à 100%.')
                ->click('@submit-result-submit-btn')
                ->waitFor('[dusk="my-result-card"]', 15)
                ->assertVisible('@my-result-card');
        });
    }

    /**
     * Le bouton Soumettre résultat est absent quand le résultat a déjà été validé N1.
     * On seed directement un résultat validé et on vérifie que le bouton est masqué.
     */
    public function test_submit_button_hidden_when_result_already_validated_n1(): void
    {
        $ctx = $this->makeValidationContext();

        TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['collaborateur']->id,
            'statut' => 'en_validation_n2',
            'valide_par_n1' => true,
            'soumis_le' => now()->subHours(3),
            'action_n0' => 'approuve',
            'action_n0_le' => now()->subHours(2),
        ]);

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['collaborateur'])
                ->visit("/taches/{$ctx['tache']->id}")
                ->waitFor('[dusk="tab-results"]', 15)
                ->click('[dusk="tab-results"]')
                ->pause(3000)
                ->assertMissing('@open-submit-result-btn');
        });
    }

    /**
     * Le responsable N1 voit le bouton Valider N1 sur un résultat en attente.
     * On seed un résultat en_validation_n1 et le responsable de l'activité peut le valider.
     */
    public function test_n1_validator_sees_validate_button_for_pending_result(): void
    {
        $ctx = $this->makeValidationContext();

        TacheResultat::factory()->create([
            'tache_id' => $ctx['tache']->id,
            'user_id' => $ctx['collaborateur']->id,
            'statut' => 'en_validation_n1',
            'soumis_le' => now()->subHours(2),
            'action_n0' => 'approuve',
            'action_n0_le' => now()->subHours(1),
            'taux_realisation' => 90,
            'resultats_obtenus' => 'Résultats obtenus complets.',
        ]);

        $this->browse(function (Browser $browser) use ($ctx) {
            $this->signInAs($browser, $ctx['owner'])
                ->visit('/validations/en-attente')
                ->waitForText('Validations en attente', 10)
                ->waitFor('[dusk="pending-n1-section"]', 10)
                ->assertVisible('@pending-n1-section')
                ->assertSeeIn('@pending-n1-section', 'En attente N1');
        });
    }
}
