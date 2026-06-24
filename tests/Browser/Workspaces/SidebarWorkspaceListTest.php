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
 * Tests Dusk pour l'affichage contextuel des workspaces dans la sidebar.
 */
class SidebarWorkspaceListTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * Super admin avec 5 workspaces → la sidebar n'en affiche que 3.
     */
    public function test_super_admin_sees_max_three_workspaces_in_sidebar(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        Workspace::factory()->count(5)->create(['owner_id' => $admin->id]);
        $first = Workspace::where('owner_id', $admin->id)->first();
        $admin->update(['current_workspace_id' => $first->id]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/')
                ->waitFor('[dusk="workspace-selector-btn"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->waitFor('.stagger-item', 5)
                ->assertScript(
                    'return document.querySelectorAll("[dusk=\'workspace-selector-btn\'] ~ div button.w-full.flex.items-center.gap-3").length',
                    3
                );
        });
    }

    /**
     * Super admin voit le lien "Voir tous les workspaces" dans la dropdown.
     */
    public function test_super_admin_sees_view_all_link(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $admin = User::factory()->create();
        $admin->assignRole('super_admin');
        $workspace = Workspace::factory()->create(['owner_id' => $admin->id]);
        $admin->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/')
                ->waitFor('[dusk="workspace-selector-btn"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->waitFor('[dusk="view-all-workspaces-btn"]', 5)
                ->assertVisible('[dusk="view-all-workspaces-btn"]');
        });
    }

    /**
     * Un propriétaire voit uniquement ses workspaces, pas ceux où il est membre.
     */
    public function test_owner_sees_only_owned_workspaces(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $owned1 = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'Mon Workspace A']);
        $owned2 = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'Mon Workspace B']);
        $otherOwner = User::factory()->create();
        $memberWorkspace = Workspace::factory()->create(['owner_id' => $otherOwner->id, 'nom' => 'Workspace Externe']);
        $owner->update(['current_workspace_id' => $owned1->id]);

        $managerRole = Role::findByName('manager');
        $memberWorkspace->members()->attach($owner->id, ['role_id' => $managerRole->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/')
                ->waitFor('[dusk="workspace-selector-btn"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->pause(500)
                ->assertSee('Mon Workspace A')
                ->assertSee('Mon Workspace B')
                ->assertDontSee('Workspace Externe');
        });
    }

    /**
     * Un non-propriétaire dans le workspace d'un autre ne voit que le workspace courant.
     */
    public function test_non_owner_sees_only_current_workspace(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $cadre = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id, 'nom' => 'Workspace Patron']);
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $cadre->id, 'nom' => 'Mon Propre WS']);
        $cadre->update(['current_workspace_id' => $workspace->id]);

        $cadreRole = Role::findByName('cadre');
        $workspace->members()->attach($cadre->id, ['role_id' => $cadreRole->id]);

        $this->browse(function (Browser $browser) use ($cadre) {
            $this->signInAs($browser, $cadre)
                ->visit('/')
                ->waitFor('[dusk="workspace-selector-btn"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->pause(500)
                ->assertSee('Workspace Patron')
                ->assertDontSee('Mon Propre WS');
        });
    }

    /**
     * Un non-propriétaire voit toujours le lien "Changer de workspace".
     */
    public function test_switch_workspace_link_always_visible_for_non_owner(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $cadre = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $cadre->update(['current_workspace_id' => $workspace->id]);

        $cadreRole = Role::findByName('cadre');
        $workspace->members()->attach($cadre->id, ['role_id' => $cadreRole->id]);

        $this->browse(function (Browser $browser) use ($cadre) {
            $this->signInAs($browser, $cadre)
                ->visit('/')
                ->waitFor('[dusk="workspace-selector-btn"]', 15)
                ->click('[dusk="workspace-selector-btn"]')
                ->waitFor('[dusk="switch-workspace-btn"]', 5)
                ->assertVisible('[dusk="switch-workspace-btn"]');
        });
    }
}
