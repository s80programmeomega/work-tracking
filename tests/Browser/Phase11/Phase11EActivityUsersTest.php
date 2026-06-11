<?php

declare(strict_types=1);

namespace Tests\Browser\Phase11;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Spatie\Permission\Models\Role;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 11E — Activity tabs + Users management (Dusk).
 *
 * Vérifie que :
 *  - L'onglet Activité du profil affiche le composant (liste ou état vide) sans données mockées.
 *  - La page /admin/users affiche le bouton "Activité" par ligne.
 *  - La page /workspace/users s'affiche pour un owner et liste ses membres.
 */
class Phase11EActivityUsersTest extends WorkTrackingTestCase
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

    private function makeOwner(): array
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }

    public function test_profile_activity_tab_renders_real_data_component(): void
    {
        $user = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-activity"]', 15)
                ->click('[dusk="profile-tab-activity"]')
                // L'onglet doit soit afficher la liste stagger soit l'état vide
                // (jamais de données mockées aléatoires).
                ->waitForText('Journal d\'activité', 10)
                ->assertSee('Journal d\'activité');
        });
    }

    public function test_admin_users_page_shows_activity_button_per_row(): void
    {
        $superAdmin = $this->makeSuperAdmin();

        // Crée un second utilisateur pour qu'il apparaisse dans la liste
        $other = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $other->id]);
        $other->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($superAdmin) {
            $this->signInAs($browser, $superAdmin)
                ->visit('/admin/users')
                ->waitFor('[dusk="admin-users-title"]', 15)
                ->waitFor('[dusk="view-activity-button"]', 10)
                ->assertPresent('[dusk="view-activity-button"]');
        });
    }

    public function test_workspace_users_page_shows_for_owner(): void
    {
        [$owner, $workspace] = $this->makeOwner();

        // Ajoute un membre au workspace
        $member = User::factory()->create();
        $roleModel = Role::firstOrCreate(['name' => 'collaborateur', 'guard_name' => 'web']);
        $workspace->members()->attach($member->id, ['role_id' => $roleModel->id]);

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/workspace/users')
                ->waitFor('[dusk="workspace-users-title"]', 15)
                ->assertPresent('[dusk="workspace-users-title"]')
                ->waitFor('[dusk="workspace-user-row"]', 10)
                ->assertPresent('[dusk="workspace-user-row"]');
        });
    }
}
