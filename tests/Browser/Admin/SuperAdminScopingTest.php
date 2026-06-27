<?php

declare(strict_types=1);

namespace Tests\Browser\Admin;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour le périmètre de la plateforme superadmin.
 *
 * Vérifie :
 *  - Un SA authentifié est redirigé vers /admin/dashboard sur les routes non-admin
 *  - Le tableau de bord admin se charge correctement
 *  - Un SA sans appartenance workspace ne peut pas accéder aux routes workspace
 *  - Un utilisateur normal ne peut pas accéder aux routes /admin/*
 */
class SuperAdminScopingTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'superadmin-scoping';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function makeSuperAdmin(): User
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        $admin->assignRole('super_admin');

        return $admin;
    }

    private function makeRegularUser(): User
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $user->assignRole('directeur');

        return $user;
    }

    /**
     * Un SA authentifié atterrit sur /admin/dashboard (redirigé depuis /taches/mes-taches).
     * signInAs() visite /taches/mes-taches — le guard router redirige vers /admin/dashboard
     * et user-menu-toggle est présent dans l'AdminLayout, donc le wait réussit.
     */
    public function test_super_admin_lands_on_admin_dashboard(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->assertPathIs('/admin/dashboard');

            $browser->screenshot("{$this->screenshotDir}/01_sa_on_admin_dashboard");
        });
    }

    /**
     * Le tableau de bord admin affiche le menu utilisateur (layout chargé).
     */
    public function test_admin_dashboard_layout_renders(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->assertVisible('[dusk="user-menu-toggle"]');

            $browser->screenshot("{$this->screenshotDir}/02_sa_admin_layout");
        });
    }

    /**
     * Un SA qui navigue vers une route tâches est redirigé vers /admin/dashboard.
     */
    public function test_super_admin_redirected_from_taches_route(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/taches/mes-taches')
                ->waitFor('[dusk="user-menu-toggle"]', 8)
                ->assertPathIs('/admin/dashboard');

            $browser->screenshot("{$this->screenshotDir}/03_sa_redirected_from_taches");
        });
    }

    /**
     * Un SA qui navigue vers une route projets est redirigé vers /admin/dashboard.
     */
    public function test_super_admin_redirected_from_projets_route(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/projets/mes-projets')
                ->waitFor('[dusk="user-menu-toggle"]', 8)
                ->assertPathIs('/admin/dashboard');

            $browser->screenshot("{$this->screenshotDir}/04_sa_redirected_from_projets");
        });
    }

    /**
     * Un utilisateur normal est redirigé quand il tente d'accéder à /admin/dashboard.
     */
    public function test_regular_user_cannot_access_admin_dashboard(): void
    {
        $user = $this->makeRegularUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/admin/dashboard')
                ->waitFor('[dusk="user-menu-toggle"]', 8)
                ->assertPathIsNot('/admin/dashboard');

            $browser->screenshot("{$this->screenshotDir}/05_regular_user_blocked_from_admin");
        });
    }

    /**
     * Un SA peut accéder aux sous-pages admin (/admin/users).
     */
    public function test_super_admin_can_reach_admin_users_page(): void
    {
        $admin = $this->makeSuperAdmin();

        $this->browse(function (Browser $browser) use ($admin) {
            $this->signInAs($browser, $admin)
                ->visit('/admin/users')
                ->waitFor('[dusk="user-menu-toggle"]', 8)
                ->assertPathIs('/admin/users');

            $browser->screenshot("{$this->screenshotDir}/06_sa_admin_users_page");
        });
    }
}
