<?php

declare(strict_types=1);

namespace Tests\Browser\Profile;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

class NotificationSettingsTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'session-management/notification-settings';

    private function makeUser(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);

        return $user;
    }

    private function openNotificationsTab(Browser $browser, User $user): Browser
    {
        return $this->signInAs($browser, $user)
            ->visit('/profile')
            ->waitFor('[dusk="profile-tab-notifications"]', 15)
            ->click('@profile-tab-notifications')
            ->waitFor('[dusk="save-notifications-btn"]', 10);
    }

    /**
     * Le toggle push reflète l'état réel (OFF par défaut — pas de souscription navigateur).
     */
    public function test_push_toggle_reflects_real_subscription_state(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openNotificationsTab($browser, $user);

            $browser->screenshot("{$this->screenshotDir}/01_notifications_tab_loaded");

            $checked = $browser->element('[dusk="push-notifications-toggle"]')
                ->getAttribute('checked');

            $this->assertNull($checked, 'Le toggle push doit être OFF sans souscription navigateur active.');
        });
    }

    /**
     * Désactiver email → sauvegarder → recharger → toggle toujours OFF.
     */
    public function test_email_toggle_saves_to_backend_and_persists(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openNotificationsTab($browser, $user);

            $checked = $browser->element('[dusk="email-notifications-toggle"]')
                ->getAttribute('checked');

            if ($checked !== null) {
                $browser->click('@email-notifications-toggle')->pause(300);
            }

            $browser->screenshot("{$this->screenshotDir}/02_email_toggle_off_before_save");

            $browser->click('@save-notifications-btn')->pause(2000);

            $browser->screenshot("{$this->screenshotDir}/03_after_save_toast");

            $browser->visit('/profile')
                ->waitFor('[dusk="profile-tab-notifications"]', 15)
                ->click('@profile-tab-notifications')
                ->waitFor('[dusk="email-notifications-toggle"]', 10);

            $browser->screenshot("{$this->screenshotDir}/04_email_toggle_after_reload");

            $checkedAfter = $browser->element('[dusk="email-notifications-toggle"]')
                ->getAttribute('checked');

            $this->assertNull($checkedAfter, 'Le toggle email doit rester OFF après rechargement.');
        });
    }

    /**
     * Le bouton Enregistrer affiche un toast, pas une alert() native.
     */
    public function test_save_shows_toast_not_alert(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openNotificationsTab($browser, $user)
                ->click('@save-notifications-btn')
                ->pause(1500);

            $browser->screenshot("{$this->screenshotDir}/05_save_toast_visible");

            $browser->assertPresent('.Vue-Toastification__toast')
                ->assertPathIs('/profile');
        });
    }
}
