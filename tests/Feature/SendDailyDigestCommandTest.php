<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Mail\DailyDigestMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class SendDailyDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        Mail::fake();
    }

    private function makeUserWithUnread(array $prefOverrides = [], int $unreadCount = 3): User
    {
        $user = User::factory()->create();
        $pref = $user->getOrCreateNotificationPreference();
        $pref->forceFill(array_merge([
            'digest_frequency' => 'daily',
            'digest_time' => '09:00:00',
            'last_digest_sent_at' => null,
            'quiet_hours_enabled' => false,
        ], $prefOverrides))->save();

        for ($i = 0; $i < $unreadCount; $i++) {
            $user->notifications()->create([
                'id' => (string) Str::uuid(),
                'type' => 'App\\Notifications\\Test',
                'data' => ['type' => 'resultat_soumis_n0', 'tache_titre' => "Task {$i}"],
            ]);
        }

        return $user;
    }

    /** @test */
    public function sends_digest_to_eligible_user_with_unread_notifications(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $user = $this->makeUserWithUnread();

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertQueued(DailyDigestMail::class, fn ($mail) => $mail->user->is($user));

        $this->assertNotNull($user->fresh()->notificationPreference->last_digest_sent_at);
    }

    /** @test */
    public function skips_user_with_no_unread_notifications(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $user = $this->makeUserWithUnread(unreadCount: 0);

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertNothingQueued();
        $this->assertNull($user->fresh()->notificationPreference->last_digest_sent_at);
    }

    /** @test */
    public function skips_user_with_digest_frequency_none(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $this->makeUserWithUnread(['digest_frequency' => 'none']);

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function skips_user_when_digest_time_not_yet_reached(): void
    {
        Carbon::setTestNow('2026-05-21 08:00:00');

        $this->makeUserWithUnread(['digest_time' => '09:00:00']);

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function does_not_resend_to_user_who_already_received_digest_today(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $this->makeUserWithUnread([
            'last_digest_sent_at' => Carbon::parse('2026-05-21 09:15:00'),
        ]);

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function skips_user_inside_quiet_hours(): void
    {
        Carbon::setTestNow('2026-05-21 23:00:00');

        $this->makeUserWithUnread([
            'digest_time' => '21:00:00',
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '08:00:00',
        ]);

        $this->artisan('notifications:send-digest')->assertExitCode(0);

        Mail::assertNothingQueued();
    }

    /** @test */
    public function dry_run_does_not_send_or_mark_sent(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $user = $this->makeUserWithUnread();

        $this->artisan('notifications:send-digest', ['--dry-run' => true])->assertExitCode(0);

        Mail::assertNothingQueued();
        $this->assertNull($user->fresh()->notificationPreference->last_digest_sent_at);
    }

    /** @test */
    public function user_filter_targets_only_one_user(): void
    {
        Carbon::setTestNow('2026-05-21 09:30:00');

        $target = $this->makeUserWithUnread();
        $other = $this->makeUserWithUnread();

        $this->artisan('notifications:send-digest', ['--user' => $target->id])->assertExitCode(0);

        Mail::assertQueued(DailyDigestMail::class, fn ($mail) => $mail->user->is($target));
        Mail::assertNotQueued(DailyDigestMail::class, fn ($mail) => $mail->user->is($other));
    }
}
