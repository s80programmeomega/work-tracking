<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Vérifie que les anciens liens vers /workspaces et workspaces.show
 * ont bien été remplacés par workspaces.select dans tous les composants concernés.
 */
class WorkspaceOrphanLinksTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private User $owner;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function loginOwner(Browser $browser): void
    {
        $this->owner = User::factory()->create(['password' => bcrypt('password')]);
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id, 'is_active' => true]);
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        $browser->script(['localStorage.clear();'])
            ->visit('/signin')
            ->waitFor('#email', 10)
            ->type('#email', $this->owner->email)
            ->type('#password', 'password')
            ->press('button[type=submit]')
            ->waitForLocation('/workspaces/select', 15);
    }

    // -------------------------------------------------------------------------
    // /workspaces route supprimée
    // -------------------------------------------------------------------------

    /** Naviguer vers /workspaces ne doit PAS afficher la page Index supprimée. */
    public function test_workspaces_index_route_is_gone(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwner($browser);

            // La route n'existe plus — le router Vue doit gérer un 404 ou rediriger
            $browser->visit('/workspaces')
                ->pause(800);

            // Vérifier que ce n'est PAS la vue Index supprimée (elle avait un titre distinct)
            $browser->assertDontSee('Mes Workspaces')
                ->assertDontSee('workspaces_index');
        });
    }

    // -------------------------------------------------------------------------
    // Create.vue — liens de retour et redirect post-création
    // -------------------------------------------------------------------------

    /** La page de création affiche un lien de retour vers /workspaces/select. */
    public function test_create_page_back_link_points_to_picker(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwner($browser);

            $browser->visit('/workspaces/create')
                ->waitFor('a[href*="workspaces/select"]', 10)
                ->assertPresent('a[href*="workspaces/select"]');
        });
    }

    // -------------------------------------------------------------------------
    // Settings.vue — bouton retour
    // -------------------------------------------------------------------------

    /** La page Settings affiche un lien retour vers /workspaces/select. */
    public function test_settings_back_link_points_to_picker(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwner($browser);

            $browser->visit("/workspaces/{$this->workspace->id}/settings")
                ->waitFor('a[href*="workspaces/select"]', 10)
                ->assertPresent('a[href*="workspaces/select"]');
        });
    }

    // -------------------------------------------------------------------------
    // Edit.vue — boutons retour
    // -------------------------------------------------------------------------

    /** La page Edit affiche des liens retour vers /workspaces/select. */
    public function test_edit_page_back_links_point_to_picker(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwner($browser);

            $browser->visit("/workspaces/{$this->workspace->id}/edit")
                ->waitFor('a[href*="workspaces/select"]', 10)
                ->assertPresent('a[href*="workspaces/select"]');
        });
    }

    // -------------------------------------------------------------------------
    // Aucun lien vers /workspaces/:id (route show supprimée)
    // -------------------------------------------------------------------------

    /** La sidebar ne contient aucun lien direct vers /workspaces/{id} (show supprimé). */
    public function test_no_direct_workspace_show_links_in_sidebar(): void
    {
        $this->browse(function (Browser $browser) {
            $this->loginOwner($browser);

            $browser->click("[dusk=\"workspace-card-{$this->workspace->id}\"]")
                ->waitForLocation('/', 15);

            // Le HTML de la sidebar ne doit pas contenir de lien /workspaces/{id} sans sous-chemin
            $sidebarHtml = $browser->element('.sidebar, nav, #sidebar')?->getAttribute('innerHTML') ?? '';
            $this->assertDoesNotMatchRegularExpression(
                '|href="[^"]*workspaces/\d+(?!/)|',
                $sidebarHtml,
                'Aucun lien vers workspaces.show ne doit subsister dans la sidebar'
            );
        });
    }
}
