<?php

declare(strict_types=1);

namespace Tests\Browser\Phase11;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 11A — Dashboard accuracy (Dusk).
 *
 * Vérifie que :
 *  - Les stat cards s'affichent avec un badge de variation de période (format %).
 *  - Les valeurs hardcodées de la Phase 10 (+12%, +5%, -2%) ont disparu.
 *  - Le Kanban affiche les colonnes "active" et "completed" alignées sur ProjetStatus
 *    (la colonne orpheline "pending" ne doit plus exister).
 */
class Phase11ADashboardTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function makeSuperAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create(['is_super_admin' => true]);
        $user->forceFill(['role' => 'super_admin'])->save();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $user->assignRole('super_admin');

        return $user;
    }

    public function test_stats_cards_render_with_change_badges(): void
    {
        $user = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/dashboard')
                ->waitFor('[dusk="stats-cards-grid"]', 15)
                ->assertPresent('[dusk="stat-card-0"]')
                ->assertPresent('[dusk="stat-card-0-change"]');
        });
    }

    public function test_stats_change_values_are_not_hardcoded_placeholders(): void
    {
        $user = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/dashboard')
                ->waitFor('[dusk="stats-cards-grid"]', 15)
                // Les valeurs hardcodées pré-11A étaient exactement "+12%" et "+5%".
                ->assertDontSee('+12%')
                ->assertDontSee('+5%');
        });
    }

    public function test_kanban_has_active_and_completed_columns_not_pending(): void
    {
        $user = $this->makeSuperAdmin();

        Projet::factory()->create([
            'workspace_id' => $user->current_workspace_id,
            'responsable_id' => $user->id,
            'status' => 'active',
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/dashboard')
                ->waitFor('[dusk="kanban-board"]', 15)
                ->assertPresent('[dusk="kanban-col-active"]')
                ->assertPresent('[dusk="kanban-col-completed"]')
                ->assertMissing('[dusk="kanban-col-pending"]');
        });
    }
}
