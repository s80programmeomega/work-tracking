<?php

declare(strict_types=1);

namespace Tests\Browser\Phase11;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 11B — Profile quick wins (Dusk).
 *
 * Vérifie que :
 *  - L'onglet Notifications affiche le toggle son (notification-sounds-toggle).
 *  - L'onglet Préférences ne contient plus les sections Thème / Langue / Densité
 *    (supprimées en 11B comme redondantes avec le switcher de la navbar).
 *  - L'onglet Préférences contient toujours la section Fuseau horaire (conservée).
 */
class Phase11BProfileTest extends WorkTrackingTestCase
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

    public function test_notifications_tab_shows_sound_toggle(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-notifications"]', 15)
                ->click('[dusk="profile-tab-notifications"]')
                ->waitFor('[dusk="notification-sounds-toggle"]', 10)
                ->assertPresent('[dusk="notification-sounds-toggle"]');
        });
    }

    public function test_preferences_tab_shows_timezone_section(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-preferences"]', 15)
                ->click('[dusk="profile-tab-preferences"]')
                ->waitFor('[dusk="preferences-settings-panel"]', 10)
                ->assertPresent('[dusk="pref-timezone-section"]');
        });
    }

    public function test_preferences_tab_has_no_theme_or_language_section(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/profile')
                ->waitFor('[dusk="profile-tab-preferences"]', 15)
                ->click('[dusk="profile-tab-preferences"]')
                ->waitFor('[dusk="preferences-settings-panel"]', 10)
                // Ces sections ont été retirées en Phase 11B (redondantes avec le switcher navbar).
                ->assertMissing('[dusk="pref-theme-section"]')
                ->assertMissing('[dusk="pref-language-section"]')
                ->assertMissing('[dusk="pref-density-section"]');
        });
    }
}
