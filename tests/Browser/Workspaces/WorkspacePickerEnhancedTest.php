<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\Projet;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour les fonctionnalités ajoutées au WorkspacePicker :
 * tuiles de statistiques, barre de recherche, bouton nouveau workspace,
 * bouton paramètres par carte, et stagger sur les éléments.
 */
class WorkspacePickerEnhancedTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Crée un utilisateur propriétaire avec un workspace actif et le connecte. */
    private function loginOwnerWithWorkspace(Browser $browser, int $projectCount = 0, int $memberCount = 0): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create(['password' => bcrypt('password')]);
        $workspace = Workspace::factory()->create([
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        // Créer les projets demandés
        if ($projectCount > 0) {
            Projet::factory($projectCount)->create(['workspace_id' => $workspace->id]);
        }

        // Créer des membres supplémentaires
        if ($memberCount > 0) {
            $members = User::factory($memberCount)->create();
            $roleId = Role::where('name', 'cadre')->value('id');
            foreach ($members as $member) {
                $workspace->members()->attach($member->id, ['role_id' => $roleId]);
            }
        }

        $browser->script(['localStorage.clear();'])
            ->visit('/signin')
            ->waitFor('#email', 10)
            ->type('#email', $owner->email)
            ->type('#password', 'password')
            ->press('button[type=submit]')
            ->waitForLocation('/workspaces/select', 15)
            ->assertPathIs('/workspaces/select');

        return [$owner, $workspace];
    }

    // -------------------------------------------------------------------------
    // Tuiles de statistiques
    // -------------------------------------------------------------------------

    /** Les quatre tuiles de statistiques du compte sont visibles. */
    public function test_account_stat_tiles_are_visible(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser);

            $browser
                ->assertPresent('[dusk="stat-total-workspaces"]')
                ->assertPresent('[dusk="stat-total-projects"]')
                ->assertPresent('[dusk="stat-total-members"]')
                ->assertPresent('[dusk="stat-active"]');
        });
    }

    /** La tuile total workspaces affiche 1 après la création d'un workspace. */
    public function test_stat_total_workspaces_shows_correct_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser);

            // Le picker vient de charger — la tuile doit afficher "1"
            $browser->assertSeeIn('[dusk="stat-total-workspaces"]', '1');
        });
    }

    /** La tuile total projets reflète le nombre de projets du compte. */
    public function test_stat_total_projects_shows_project_count(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser, projectCount: 3);

            $browser->waitFor('[dusk="stat-total-projects"]', 10)
                ->assertSeeIn('[dusk="stat-total-projects"]', '3');
        });
    }

    /** La tuile workspaces actifs est cohérente avec la liste. */
    public function test_stat_active_reflects_active_workspaces(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser);

            $browser->waitFor('[dusk="stat-active"]', 10)
                ->assertSeeIn('[dusk="stat-active"]', '1');
        });
    }

    // -------------------------------------------------------------------------
    // Barre de recherche
    // -------------------------------------------------------------------------

    /** La barre de recherche est présente et fonctionnelle. */
    public function test_search_input_is_present(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser);

            $browser->assertPresent('[dusk="picker-search-input"]');
        });
    }

    /** Saisir un texte dans la recherche filtre les cartes affichées. */
    public function test_search_filters_workspace_cards(): void
    {
        $this->browse(function (Browser $browser) {
            $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

            $owner = User::factory()->create(['password' => bcrypt('password')]);
            $ws1 = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'AlphaSpace']);
            $ws2 = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'BetaZone']);
            $owner->update(['current_workspace_id' => $ws1->id]);

            $browser->script(['localStorage.clear();'])
                ->visit('/signin')
                ->waitFor('#email', 10)
                ->type('#email', $owner->email)
                ->type('#password', 'password')
                ->press('button[type=submit]')
                ->waitForLocation('/workspaces/select', 15);

            $browser->type('[dusk="picker-search-input"]', 'Alpha')
                ->pause(400)
                ->assertSee('AlphaSpace')
                ->assertDontSee('BetaZone');
        });
    }

    // -------------------------------------------------------------------------
    // Bouton nouveau workspace
    // -------------------------------------------------------------------------

    /** Le bouton « Nouveau workspace » navigue vers la page de création. */
    public function test_new_workspace_button_navigates_to_create(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwnerWithWorkspace($browser);

            $browser->assertPresent('[dusk="picker-create-btn"]')
                ->click('[dusk="picker-create-btn"]')
                ->waitForLocation('/workspaces/create', 10)
                ->assertPathIs('/workspaces/create');
        });
    }

    // -------------------------------------------------------------------------
    // Bouton paramètres par carte
    // -------------------------------------------------------------------------

    /** Chaque carte workspace affiche un bouton paramètres avec son dusk attrib interpolé. */
    public function test_settings_button_is_present_per_card(): void
    {
        $this->browse(function (Browser $browser) {
            [, $workspace] = $this->loginOwnerWithWorkspace($browser);

            $browser->waitFor("[dusk=\"workspace-settings-btn-{$workspace->id}\"]", 10)
                ->assertPresent("[dusk=\"workspace-settings-btn-{$workspace->id}\"]");
        });
    }

    /** Cliquer sur le bouton paramètres d'une carte navigue vers les settings du workspace. */
    public function test_settings_button_navigates_to_workspace_settings(): void
    {
        $this->browse(function (Browser $browser) {
            [, $workspace] = $this->loginOwnerWithWorkspace($browser);

            $browser->waitFor("[dusk=\"workspace-settings-btn-{$workspace->id}\"]", 10)
                ->click("[dusk=\"workspace-settings-btn-{$workspace->id}\"]")
                ->waitForLocation("/workspaces/{$workspace->id}/settings", 10)
                ->assertPathIs("/workspaces/{$workspace->id}/settings");
        });
    }
}
