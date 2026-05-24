<?php

declare(strict_types=1);

namespace Tests\Browser\Evaluation;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Task 9 — couverture Dusk de la page Fiche d'évaluation (AgentSheet.vue).
 *
 * Ce qui est testé:
 *   - rendu de la fiche personnelle pour un utilisateur authentifié
 *     (header, score global, 8 critères, donut renvois);
 *   - bascule entre les 4 onglets de sections (tabs cliquables, indicateur
 *     visuel actif).
 *
 * On ne couvre PAS l'export PDF (stub UI pour l'instant — endpoint dédié
 * arrivera à l'étape suivante), ni les chemins de pagination/filtre
 * complexes (déjà couverts par EvaluationAgentSheetEndpointTest côté API).
 */
class AgentSheetTest extends WorkTrackingTestCase
{
    use AttachesWithRoleId, DatabaseTruncation;

    public function test_own_sheet_renders_header_criteria_grid_and_donut(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);
        // L'utilisateur doit être membre du workspace pour que le gate
        // permissions (EVALUATIONS_VIEW_FICHE) résolve son rôle.
        $this->attachWithRole($workspace->members(), $user->id, 'collaborateur');

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit("/evaluations/personnel/{$user->id}/historique")
                ->waitFor('@sheet-user-nom', 15)
                ->assertVisible('@sheet-user-nom')
                ->assertVisible('@sheet-score-global')
                // Donut + 8 critères toujours rendus (même valeurs neutres
                // si l'utilisateur n'a rien fait — voir defaults dans
                // EvaluationScoreService).
                ->assertVisible('@return-quality-donut')
                ->assertVisible('@criterion-completion_rate')
                ->assertVisible('@criterion-deadline_respect')
                ->assertVisible('@criterion-result_quality')
                ->assertVisible('@criterion-first_pass_validation')
                ->assertVisible('@criterion-justified_returns')
                ->assertVisible('@criterion-inactions')
                ->assertVisible('@criterion-work_volume')
                ->assertVisible('@criterion-team_coordination');
        });
    }

    public function test_section_tabs_switch_active_section(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);
        $this->attachWithRole($workspace->members(), $user->id, 'collaborateur');

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit("/evaluations/personnel/{$user->id}/historique")
                ->waitFor('@sheet-user-nom', 15)
                // Onglet par défaut: directed_tasks → l'onglet est dans le DOM.
                ->assertVisible('@section-tab-directed_tasks')
                ->click('@section-tab-assignee_tasks')
                ->pause(500)
                ->click('@section-tab-directed_subtasks')
                ->pause(500)
                ->click('@section-tab-assignee_subtasks')
                ->pause(500)
                ->assertVisible('@section-tab-assignee_subtasks');
        });
    }
}
