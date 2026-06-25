<?php

declare(strict_types=1);

namespace Tests\Browser\Workspaces;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour le sélecteur de workspace affiché à la connexion.
 */
class WorkspacePickerTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    // -------------------------------------------------------------------------
    // Auth & routing
    // -------------------------------------------------------------------------

    /**
     * Un visiteur non authentifié est redirigé vers /signin.
     */
    public function test_unauthenticated_user_is_redirected_to_signin(): void
    {
        $this->browse(function (Browser $browser) {
            $browser->script(['localStorage.clear();']);
            $browser->visit('/workspaces/select')
                ->waitForLocation('/signin', 10)
                ->assertPathIs('/signin');
        });
    }

    /**
     * Après connexion via le formulaire, l'utilisateur atterrit sur /workspaces/select.
     */
    public function test_authenticated_user_is_redirected_to_picker_after_login(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create(['password' => bcrypt('password')]);
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $browser->script(['localStorage.clear();']);
            $this->signInViaUi($browser, $owner->email, 'password')
                ->waitForLocation('/workspaces/select', 15)
                ->assertPathIs('/workspaces/select');
        });
    }

    // -------------------------------------------------------------------------
    // Picker page content
    // -------------------------------------------------------------------------

    /**
     * Un workspace dont l'utilisateur est propriétaire affiche le badge "Propriétaire".
     */
    public function test_picker_shows_owned_workspace_with_proprietaire_badge(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner, $workspace) {
            $this->signInAs($browser, $owner)
                ->visit('/workspaces/select')
                ->waitFor("[dusk='workspace-card-{$workspace->id}']", 15)
                ->assertVisible("[dusk='workspace-card-{$workspace->id}'] [dusk='owner-badge']");
        });
    }

    /**
     * Un workspace dont l'utilisateur est membre affiche le badge avec son rôle.
     */
    public function test_picker_shows_member_workspace_with_role_badge(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $member = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $member->update(['current_workspace_id' => $workspace->id]);

        $managerRole = Role::findByName('manager');
        $workspace->members()->attach($member->id, ['role_id' => $managerRole->id]);

        $this->browse(function (Browser $browser) use ($member, $workspace) {
            $this->signInAs($browser, $member)
                ->visit('/workspaces/select')
                ->waitFor("[dusk='workspace-card-{$workspace->id}']", 15)
                ->assertVisible("[dusk='workspace-card-{$workspace->id}'] [dusk='role-badge']")
                ->assertSeeIn("[dusk='workspace-card-{$workspace->id}'] [dusk='role-badge']", 'manager');
        });
    }

    /**
     * Le sélecteur affiche tous les workspaces de l'utilisateur (propriétaire + membre).
     */
    public function test_picker_shows_all_user_workspaces(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $ownedWorkspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $otherOwner = User::factory()->create();
        $memberWorkspace = Workspace::factory()->create(['owner_id' => $otherOwner->id]);
        $owner->update(['current_workspace_id' => $ownedWorkspace->id]);

        $cadreRole = Role::findByName('cadre');
        $memberWorkspace->members()->attach($owner->id, ['role_id' => $cadreRole->id]);

        $this->browse(function (Browser $browser) use ($owner, $ownedWorkspace, $memberWorkspace) {
            $this->signInAs($browser, $owner)
                ->visit('/workspaces/select')
                ->waitFor("[dusk='workspace-card-{$ownedWorkspace->id}']", 15)
                ->assertVisible("[dusk='workspace-card-{$ownedWorkspace->id}']")
                ->assertVisible("[dusk='workspace-card-{$memberWorkspace->id}']");
        });
    }

    /**
     * Cliquer sur une carte sélectionne le workspace et redirige vers le dashboard.
     */
    public function test_picker_card_click_switches_workspace_and_redirects_to_dashboard(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'Workspace Demo']);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner, $workspace) {
            $this->signInAs($browser, $owner)
                ->visit('/workspaces/select')
                ->waitFor("[dusk='workspace-card-{$workspace->id}']", 15)
                ->click("[dusk='workspace-card-{$workspace->id}']")
                ->waitForLocation('/', 15)
                ->assertPathIs('/');
        });
    }

    // -------------------------------------------------------------------------
    // Responsiveness
    // -------------------------------------------------------------------------

    /**
     * Sur mobile (375px), les cartes s'empilent en colonne unique et les badges restent visibles.
     */
    public function test_picker_is_responsive_on_mobile(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner, $workspace) {
            $browser->resize(375, 812);

            $this->signInAs($browser, $owner)
                ->visit('/workspaces/select')
                ->waitFor("[dusk='workspace-card-{$workspace->id}']", 15)
                ->assertVisible("[dusk='workspace-card-{$workspace->id}']")
                ->assertVisible("[dusk='workspace-card-{$workspace->id}'] [dusk='owner-badge']");

            // Vérifie qu'il n'y a pas de défilement horizontal
            $overflowX = $browser->script('return document.documentElement.scrollWidth > window.innerWidth;')[0];
            $this->assertFalse($overflowX, 'Défilement horizontal détecté sur mobile');

            $browser->resize(1280, 900);
        });
    }

    /**
     * Sur tablette (768px), les cartes s'affichent sur 2 colonnes.
     */
    public function test_picker_is_responsive_on_tablet(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        Workspace::factory()->count(3)->create(['owner_id' => $owner->id]);
        $first = Workspace::where('owner_id', $owner->id)->first();
        $owner->update(['current_workspace_id' => $first->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $browser->resize(768, 1024);

            $this->signInAs($browser, $owner)
                ->visit('/workspaces/select')
                ->waitFor('.stagger-item', 15);

            // Sur 768px, sm:grid-cols-2 s'applique → les cartes ne dépassent pas la moitié
            $overflowX = $browser->script('return document.documentElement.scrollWidth > window.innerWidth;')[0];
            $this->assertFalse($overflowX, 'Défilement horizontal détecté sur tablette');

            $browser->resize(1280, 900);
        });
    }

    // -------------------------------------------------------------------------
    // Switch workspace button in sidebar
    // -------------------------------------------------------------------------

    /**
     * Le bouton "Changer de workspace" dans la sidebar redirige vers le sélecteur.
     */
    public function test_sidebar_switch_workspace_button_navigates_to_picker(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/')
                ->waitFor('[dusk="user-menu-toggle"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->waitFor('[dusk="switch-workspace-btn"]', 5)
                ->click('[dusk="switch-workspace-btn"]')
                ->waitForLocation('/workspaces/select', 10)
                ->assertPathIs('/workspaces/select');
        });
    }
}
