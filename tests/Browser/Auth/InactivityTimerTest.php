<?php

declare(strict_types=1);

namespace Tests\Browser\Auth;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

class InactivityTimerTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private string $screenshotDir = 'session-management/inactivity-timer';

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
            ->waitFor('[dusk="timeout-countdown"]', 10);
    }

    /**
     * Sélectionner 15 min affiche un countdown en minutes, pas en secondes.
     */
    public function test_timer_display_shows_minutes_not_seconds(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openSecurityTab($browser, $user)
                ->click('@timeout-option-15')
                ->pause(500);

            $browser->screenshot("{$this->screenshotDir}/01_timer_15min_selected");

            $browser->assertSeeIn('@timeout-countdown', 'min')
                ->assertDontSeeIn('@timeout-countdown', '15 sec');
        });
    }

    /**
     * Sélectionner "Jamais" affiche "Jamais" dans le countdown.
     */
    public function test_selecting_never_shows_jamais(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openSecurityTab($browser, $user)
                ->click('@timeout-option-0')
                ->pause(500);

            $browser->screenshot("{$this->screenshotDir}/02_timer_never_selected");

            $browser->assertSeeIn('@timeout-countdown', 'Jamais');
        });
    }

    /**
     * Le réglage du timeout persiste après rechargement de la page.
     */
    public function test_timeout_setting_persists_after_reload(): void
    {
        $user = $this->makeUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->openSecurityTab($browser, $user)
                ->click('@timeout-option-30')
                ->pause(300);

            $browser->screenshot("{$this->screenshotDir}/03_timeout_30min_before_reload");

            $browser->visit('/profile')
                ->waitFor('[dusk="profile-tab-security"]', 15)
                ->click('@profile-tab-security')
                ->waitFor('[dusk="timeout-option-30"]', 10);

            $browser->screenshot("{$this->screenshotDir}/04_timeout_30min_after_reload");

            $browser->assertAttributeContains('@timeout-option-30', 'class', 'border-blue-500');
        });
    }
}
