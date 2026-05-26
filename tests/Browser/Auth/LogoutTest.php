<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la déconnexion utilisateur.
 */
class LogoutTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    /**
     * Un utilisateur connecté peut se déconnecter via le menu utilisateur.
     * Après déconnexion, il est redirigé vers la page de connexion.
     */
    public function test_authenticated_user_can_sign_out(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->waitFor('[dusk="user-menu-toggle"]', 15)
                ->click('@user-menu-toggle')
                ->waitFor('[dusk="user-menu-signout"]', 5)
                ->click('@user-menu-signout')
                ->waitFor('[dusk="email"]', 15)
                ->assertPathIs('/signin');
        });
    }

    /**
     * Après déconnexion, le token est supprimé du localStorage.
     * Naviguer vers une page protégée redirige vers /signin.
     */
    public function test_after_logout_protected_route_redirects_to_signin(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->waitFor('[dusk="user-menu-toggle"]', 15)
                ->click('@user-menu-toggle')
                ->waitFor('[dusk="user-menu-signout"]', 5)
                ->click('@user-menu-signout')
                ->waitFor('[dusk="email"]', 15)
                ->visit('/taches/mes-taches')
                ->waitFor('[dusk="email"]', 10)
                ->assertPathIs('/signin');
        });
    }
}
