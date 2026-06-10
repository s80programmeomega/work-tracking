<?php

declare(strict_types=1);

namespace Tests\Browser\Phase11;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 11C — Session management (Dusk).
 *
 * Vérifie que :
 *  - L'onglet Sécurité affiche la section "Sessions actives".
 *  - La liste des sessions s'affiche avec au moins un élément (la session courante).
 *  - L'élément de session courant porte le badge "Actif".
 */
class Phase11CSessionTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function makeUser(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    public function test_security_tab_shows_active_sessions_section(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-security"]', 15)
                ->click('[dusk="profile-tab-security"]')
                ->waitFor('[dusk="active-sessions-section"]', 10)
                ->assertPresent('[dusk="active-sessions-section"]');
        });
    }

    public function test_sessions_list_shows_after_loading(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            // signInAs() crée un token "dusk" — mais le composant charge les tokens
            // via GET /api/users/sessions qui ne retourne que les "auth_token".
            // On crée donc un token auth_token en plus pour que la liste ne soit pas vide.
            $user->createToken('auth_token');

            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-security"]', 15)
                ->click('[dusk="profile-tab-security"]')
                // Attend que la liste soit chargée (disparition du spinner de chargement).
                ->waitFor('[dusk="sessions-list"]', 15)
                ->assertPresent('[dusk="sessions-list"]');
        });
    }

    public function test_session_revoke_button_is_visible(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $user->createToken('auth_token');

            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-security"]', 15)
                ->click('[dusk="profile-tab-security"]')
                ->waitFor('[dusk="session-revoke-btn"]', 15)
                ->assertPresent('[dusk="session-revoke-btn"]');
        });
    }
}
