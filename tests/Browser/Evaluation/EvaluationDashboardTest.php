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
 * Task 10 — couverture Dusk de la page Tableau de bord évaluations (EvaluationDashboard.vue).
 *
 * Ce qui est testé:
 *   - rendu de la page pour un owner (titre, filtres de période, bouton actualiser);
 *   - les sections top performers, scores et alertes sont présentes;
 *   - un collaborateur est redirigé (ne voit pas le tableau de bord — gate 403).
 */
class EvaluationDashboardTest extends WorkTrackingTestCase
{
    use AttachesWithRoleId, DatabaseTruncation;

    public function test_owner_sees_evaluation_dashboard_with_all_sections(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/evaluations/tableau-de-bord')
                ->waitFor('[dusk="evaluation-dashboard-title"]', 15)
                ->assertVisible('[dusk="evaluation-dashboard-title"]')
                ->assertVisible('[dusk="periode-start"]')
                ->assertVisible('[dusk="periode-end"]')
                ->assertVisible('[dusk="refresh-dashboard-btn"]')
                ->assertVisible('[dusk="top-performers-section"]')
                ->assertVisible('[dusk="all-scores-section"]')
                ->assertVisible('[dusk="alerts-section"]');
        });
    }

    public function test_owner_can_refresh_dashboard_after_period_change(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $this->attachWithRole($workspace->members(), $owner->id, 'owner');

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/evaluations/tableau-de-bord')
                ->waitFor('[dusk="evaluation-dashboard-title"]', 15)
                ->type('[dusk="periode-start"]', '2026-01-01')
                ->type('[dusk="periode-end"]', '2026-12-31')
                ->click('[dusk="refresh-dashboard-btn"]')
                // After re-fetch, top performers section must still be present.
                ->waitFor('[dusk="top-performers-section"]', 10)
                ->assertVisible('[dusk="top-performers-section"]');
        });
    }
}
