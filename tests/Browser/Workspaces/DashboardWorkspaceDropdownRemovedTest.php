<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Vérifie que le sélecteur multi-workspace a bien été retiré du Dashboard.
 * Le dashboard doit afficher les données du workspace courant sans offrir
 * de dropdown pour basculer entre workspaces.
 */
class DashboardWorkspaceDropdownRemovedTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function loginAndGoToDashboard(Browser $browser): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create(['password' => bcrypt('password')]);
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id, 'is_active' => true]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $browser->script(['localStorage.clear();'])
            ->visit('/signin')
            ->waitFor('#email', 10)
            ->type('#email', $owner->email)
            ->type('#password', 'password')
            ->press('button[type=submit]')
            ->waitForLocation('/workspaces/select', 15)
            ->click("[dusk=\"workspace-card-{$workspace->id}\"]")
            ->waitForLocation('/', 15)
            ->assertPathIs('/');

        return [$owner, $workspace];
    }

    /** Le dashboard ne contient aucun <select> de workspace. */
    public function test_no_workspace_select_element_on_dashboard(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToDashboard($browser);

            // Vérifier l'absence de l'élément select pour les workspaces
            $browser->assertMissing('select[id="workspace-select"]')
                ->assertMissing('select[name="workspace"]');
        });
    }

    /** L'option « Tous les workspaces » n'est plus présente dans le dashboard. */
    public function test_all_workspaces_option_absent(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToDashboard($browser);

            // S'assurer que l'option "Tous" n'apparaît pas dans un <select>
            $browser->assertDontSee('option[value="all"]');
        });
    }

    /** Le dashboard charge correctement les données du workspace actif sans dropdown. */
    public function test_dashboard_loads_with_current_workspace_data(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginAndGoToDashboard($browser);

            // La page doit se charger sans erreur
            $browser->assertPathIs('/')
                ->assertDontSee('500')
                ->assertDontSee('Error');
        });
    }
}
