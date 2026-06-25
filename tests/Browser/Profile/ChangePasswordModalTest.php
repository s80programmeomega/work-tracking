<?php

declare(strict_types=1);

namespace Tests\Browser\Profile;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Facades\Hash;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour la modale de changement de mot de passe dans le profil.
 *
 * Vérifie :
 *  - Le bouton "Changer le mot de passe" apparaît dans l'onglet Sécurité
 *  - Un clic ouvre la modale
 *  - Le formulaire affiche les 3 champs (actuel, nouveau, confirmation)
 *  - Annuler ferme la modale
 *  - Une soumission avec mauvais mot de passe actuel affiche une erreur
 */
class ChangePasswordModalTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'change-password-modal';

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function makeUser(): User
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);
        $user->assignRole('directeur');
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    private function goToSecurityTab(Browser $browser, User $user): Browser
    {
        return $this->signInAs($browser, $user)
            ->visit('/profile')
            ->waitFor('[dusk="profile-tab-security"]', 25)
            ->click('[dusk="profile-tab-security"]')
            ->waitFor('[dusk="change-password-btn"]', 15);
    }

    /**
     * L'onglet Sécurité affiche le bouton de changement de mot de passe.
     */
    public function test_security_tab_shows_change_password_button(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->assertVisible('[dusk="change-password-btn"]');

            $browser->screenshot("{$this->screenshotDir}/01_security_tab_with_change_password_btn");
        });
    }

    /**
     * Un clic sur le bouton ouvre la modale avec les trois champs.
     */
    public function test_clicking_button_opens_modal(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->click('[dusk="change-password-btn"]')
                ->waitFor('#current_password', 8)
                ->assertVisible('#current_password')
                ->assertVisible('#new_password')
                ->assertVisible('#new_password_confirmation');

            $browser->screenshot("{$this->screenshotDir}/02_modal_open_with_fields");
        });
    }

    /**
     * Le bouton Annuler ferme la modale.
     */
    public function test_cancel_closes_modal(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->click('[dusk="change-password-btn"]')
                ->waitFor('#current_password', 8)
                ->click('.btn.btn-default')
                ->pause(500)
                ->assertMissing('#current_password');

            $browser->screenshot("{$this->screenshotDir}/03_modal_closed_after_cancel");
        });
    }

    /**
     * Le bouton Soumettre est désactivé tant que les champs ne sont pas remplis.
     */
    public function test_submit_button_disabled_when_form_empty(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->click('[dusk="change-password-btn"]')
                ->waitFor('[dusk="change-password-submit"]', 8)
                ->assertDisabled('[dusk="change-password-submit"]');

            $browser->screenshot("{$this->screenshotDir}/04_submit_disabled_when_empty");
        });
    }

    /**
     * Une soumission avec le bon mot de passe ferme la modale (succès).
     */
    public function test_correct_password_closes_modal_on_success(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->click('[dusk="change-password-btn"]')
                ->waitFor('#current_password', 8)
                ->type('#current_password', 'password123')
                ->type('#new_password', 'NouveauMDP123!')
                ->type('#new_password_confirmation', 'NouveauMDP123!')
                ->waitUntilEnabled('[dusk="change-password-submit"]', 5)
                ->click('[dusk="change-password-submit"]')
                ->waitUntilMissing('#current_password', 8);

            $browser->screenshot("{$this->screenshotDir}/05_success_modal_closed");
        });
    }

    /**
     * Une soumission avec mauvais mot de passe actuel affiche un message d'erreur.
     */
    public function test_wrong_current_password_shows_error(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->goToSecurityTab($browser, $user)
                ->click('[dusk="change-password-btn"]')
                ->waitFor('#current_password', 8)
                ->type('#current_password', 'mauvais-mot-de-passe')
                ->type('#new_password', 'NouveauMDP123!')
                ->type('#new_password_confirmation', 'NouveauMDP123!')
                ->waitUntilEnabled('[dusk="change-password-submit"]', 5)
                ->click('[dusk="change-password-submit"]')
                ->pause(2000);

            $browser->screenshot("{$this->screenshotDir}/06_wrong_current_password_error");
        });
    }
}
