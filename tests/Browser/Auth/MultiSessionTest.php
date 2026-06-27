<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

class MultiSessionTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'session-management/multi-session';

    private function makeUser(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    private function openSecurityTab(Browser $browser, User $user): Browser
    {
        return $this->signInAs($browser, $user)
            ->visit('/profile')
            ->waitFor('[dusk="profile-tab-security"]', 15)
            ->click('@profile-tab-security')
            ->waitFor('[dusk="active-sessions-section"]', 10);
    }

    /**
     * La liste des sessions affiche plusieurs lignes quand plusieurs tokens existent.
     */
    public function test_sessions_list_shows_multiple_sessions(): void
    {
        $user = $this->makeUser();

        // Créer un second token (comme si l'utilisateur était connecté sur un autre appareil)
        $user->createToken('auth_token', ['*'], now()->addHours(24));

        $this->browse(function (Browser $browser) use ($user) {
            $this->openSecurityTab($browser, $user)
                ->waitFor('[dusk="sessions-list"]', 10);

            $browser->screenshot("{$this->screenshotDir}/01_sessions_list_multiple");

            $count = count($browser->elements('[dusk^="session-item-"]'));
            $this->assertGreaterThanOrEqual(2, $count);
        });
    }

    /**
     * Révoquer une session non-courante la retire de la liste.
     */
    public function test_revoke_other_session_removes_row(): void
    {
        $user = $this->makeUser();

        $extra = $user->createToken('auth_token', ['*'], now()->addHours(24));
        $extraId = $extra->accessToken->id;

        $this->browse(function (Browser $browser) use ($user, $extraId) {
            $this->openSecurityTab($browser, $user)
                ->waitFor("[dusk=\"session-item-{$extraId}\"]", 10);

            $browser->screenshot("{$this->screenshotDir}/02_before_revoke");

            $browser->within("[dusk=\"session-item-{$extraId}\"]", function (Browser $b) {
                $b->click('@session-revoke-btn');
            })->acceptDialog()->pause(1500);

            $browser->screenshot("{$this->screenshotDir}/03_after_revoke");

            $browser->assertMissing("[dusk=\"session-item-{$extraId}\"]");
        });
    }

    /**
     * "Déconnecter tous les appareils" redirige vers /signin.
     */
    public function test_logout_all_redirects_to_signin(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openSecurityTab($browser, $user)
                ->waitFor('[dusk="logout-all-btn"]', 10);

            $browser->screenshot("{$this->screenshotDir}/04_before_logout_all");

            $browser->click('@logout-all-btn')
                ->acceptDialog()
                ->waitForLocation('/signin', 10);

            $browser->screenshot("{$this->screenshotDir}/05_after_logout_all_signin");

            $browser->assertPathIs('/signin');
        });
    }
}
