<?php

namespace Tests\Browser\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Illuminate\Support\Str;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Task 8 — verifies the notification bell renders the badge for unread
 * database notifications. The "live broadcast" part requires a running
 * Reverb server in the test env and is out of scope for the Dusk suite;
 * the broadcast wiring is covered by NotificationServiceTest (feature).
 */
class NotificationBellTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    public function test_notification_bell_shows_badge_when_user_has_unread_notifications(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        // Seed 3 unread database notifications
        for ($i = 0; $i < 3; $i++) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\ResultatSoumisN0Notification',
                'data' => [
                    'type' => 'resultat_soumis_n0',
                    'dedup_key' => "soumis_n0:resultat={$i}",
                    'tache_titre' => "Task {$i}",
                ],
            ]);
        }

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->waitFor('@notification-bell', 10)
                ->assertVisible('@notification-bell')
                ->assertVisible('@notification-badge')
                ->assertSeeIn('@notification-badge', '3');
        });
    }

    public function test_notification_bell_hides_badge_when_no_unread(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $workspace = Workspace::factory()->create();
        $user = User::factory()->create([
            'current_workspace_id' => $workspace->id,
        ]);

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->waitFor('@notification-bell', 10)
                ->assertVisible('@notification-bell')
                ->assertMissing('@notification-badge');
        });
    }
}
