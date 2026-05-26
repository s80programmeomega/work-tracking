<?php

declare(strict_types=1);

namespace Tests\Feature\Services;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ResultatEnAttenteN2Notification;
use App\Notifications\ResultatRejeteN2InfoNotification;
use App\Notifications\ResultatRejeteNotification;
use App\Notifications\ResultatSoumisNotification;
use App\Notifications\ResultatValidationCompleteNotification;
use App\Notifications\ResultatValideN1Notification;
use App\Notifications\ResultatValideN2Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * G3: vérifie que les 7 notifications N1/N2 délèguent leur via() à
 * NotificationService::channelsFor() plutôt que de hard-coder les canaux.
 */
class NotificationServiceChannelsForTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $user;

    private TacheResultat $resultat;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->user = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $this->user->id]);
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $this->resultat = TacheResultat::factory()->create([
            'tache_id' => $tache->id,
            'user_id' => $this->user->id,
        ]);
    }

    // ── channelsFor() high-signal coverage for N1/N2 event types ────────────

    /** @test */
    public function soumis_n1_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'soumis_n1');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function en_validation_n2_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'en_validation_n2');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function valide_n1_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'valide_n1');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function valide_n2_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'valide_n2');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function rejete_n1_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'rejete_n1');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function rejete_n2_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'rejete_n2');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function validation_complete_is_high_signal_and_includes_mail_and_broadcast(): void
    {
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($this->user, 'validation_complete');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    // ── via() delegates to channelsFor() for each notification class ─────────

    /** @test */
    public function resultat_soumis_notification_via_matches_channels_for_soumis_n1(): void
    {
        $notification = new ResultatSoumisNotification($this->resultat);

        $expected = app(NotificationService::class)->channelsFor($this->user, 'soumis_n1');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_en_attente_n2_notification_via_matches_channels_for_en_validation_n2(): void
    {
        $notification = new ResultatEnAttenteN2Notification($this->resultat);

        $expected = app(NotificationService::class)->channelsFor($this->user, 'en_validation_n2');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_valide_n1_notification_via_matches_channels_for_valide_n1(): void
    {
        $notification = new ResultatValideN1Notification($this->resultat, $this->user, null);

        $expected = app(NotificationService::class)->channelsFor($this->user, 'valide_n1');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_valide_n2_notification_via_matches_channels_for_valide_n2(): void
    {
        $notification = new ResultatValideN2Notification($this->resultat, $this->user, null);

        $expected = app(NotificationService::class)->channelsFor($this->user, 'valide_n2');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_rejete_notification_via_uses_rejete_n1_for_n1_level(): void
    {
        $notification = new ResultatRejeteNotification($this->resultat, $this->user, 'Motif de test.', 'n1');

        $expected = app(NotificationService::class)->channelsFor($this->user, 'rejete_n1');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_rejete_notification_via_uses_rejete_n2_for_n2_level(): void
    {
        $notification = new ResultatRejeteNotification($this->resultat, $this->user, 'Motif de test.', 'n2');

        $expected = app(NotificationService::class)->channelsFor($this->user, 'rejete_n2');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_rejete_n2_info_notification_via_matches_channels_for_rejete_n2(): void
    {
        $notification = new ResultatRejeteN2InfoNotification($this->resultat, 'Motif de test.');

        $expected = app(NotificationService::class)->channelsFor($this->user, 'rejete_n2');

        $this->assertSame($expected, $notification->via($this->user));
    }

    /** @test */
    public function resultat_validation_complete_notification_via_matches_channels_for_validation_complete(): void
    {
        $notification = new ResultatValidationCompleteNotification($this->resultat);

        $expected = app(NotificationService::class)->channelsFor($this->user, 'validation_complete');

        $this->assertSame($expected, $notification->via($this->user));
    }
}
