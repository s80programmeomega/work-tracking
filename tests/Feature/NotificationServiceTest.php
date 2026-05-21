<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Activite;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\BypassActivatedNotification;
use App\Notifications\ResultatApprouveN0Notification;
use App\Notifications\ResultatRenvoyeNotification;
use App\Notifications\ResultatSoumisN0Notification;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

class NotificationServiceTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
    }

    /** @test */
    public function channels_for_high_signal_event_includes_mail(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($user, 'renvoye_n0');

        $this->assertContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function channels_for_low_signal_event_excludes_mail(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($user, 'approuve_n0');

        $this->assertNotContains('mail', $channels);
        $this->assertContains('database', $channels);
        $this->assertContains('broadcast', $channels);
    }

    /** @test */
    public function channels_for_unknown_event_defaults_to_database_and_broadcast_only(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $this->assertSame(['database', 'broadcast'], $svc->channelsFor($user, 'unknown_event_xyz'));
    }

    /** @test */
    public function dedup_key_is_deterministic_with_and_without_resultat_id(): void
    {
        $svc = app(NotificationService::class);

        $this->assertSame('bypass:resultat=42', $svc->dedupKey('bypass', 42));
        $this->assertSame('bypass', $svc->dedupKey('bypass'));
    }

    /** @test */
    public function is_duplicate_returns_false_when_no_prior_notification(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $this->assertFalse($svc->isDuplicate($user, 'bypass', 42));
    }

    /** @test */
    public function is_duplicate_returns_true_when_recent_notification_matches_dedup_key(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            'data' => ['dedup_key' => $svc->dedupKey('bypass', 42), 'type' => 'bypass'],
        ]);

        $this->assertTrue($svc->isDuplicate($user, 'bypass', 42));
    }

    /** @test */
    public function is_duplicate_returns_false_when_prior_notification_is_outside_window(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $notification = $user->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => 'App\\Notifications\\Test',
            'data' => ['dedup_key' => $svc->dedupKey('bypass', 42)],
        ]);

        // Push it past the dedup window
        $notification->forceFill(['created_at' => now()->subMinutes(NotificationService::DEDUP_WINDOW_MINUTES + 1)])->save();

        $this->assertFalse($svc->isDuplicate($user, 'bypass', 42));
    }

    /** @test */
    public function notify_hierarchy_sends_to_recipient_directeur_and_managers_without_duplicates(): void
    {
        Notification::fake();

        $directeur = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $directeur->id]);

        $manager1 = User::factory()->create();
        $manager2 = User::factory()->create();
        $managerRoleId = Role::where('name', 'manager')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($manager1->id, ['role_id' => $managerRoleId]);
        $workspace->members()->attach($manager2->id, ['role_id' => $managerRoleId]);

        $cadre = User::factory()->create();
        $cadreRoleId = Role::where('name', 'cadre')->where('guard_name', 'web')->value('id');
        $workspace->members()->attach($cadre->id, ['role_id' => $cadreRoleId]);

        // Create a real result + the notification we'll fan out
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $author = User::factory()->create();
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id, 'user_id' => $author->id]);

        $notification = new ResultatSoumisN0Notification($resultat, $author);

        app(NotificationService::class)->notifyHierarchy(
            directRecipient: $cadre,
            workspace: $workspace,
            notification: $notification,
            eventType: 'soumis_n0',
            tacheResultatId: $resultat->id,
        );

        Notification::assertSentTo($cadre, ResultatSoumisN0Notification::class);
        Notification::assertSentTo($directeur, ResultatSoumisN0Notification::class);
        Notification::assertSentTo($manager1, ResultatSoumisN0Notification::class);
        Notification::assertSentTo($manager2, ResultatSoumisN0Notification::class);
    }

    /** @test */
    public function notify_hierarchy_deduplicates_when_user_already_received_within_window(): void
    {
        Notification::fake();

        $directeur = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $directeur->id]);

        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $author = User::factory()->create();
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id, 'user_id' => $author->id]);

        $svc = app(NotificationService::class);
        $eventType = 'soumis_n0';

        // Pre-seed a notification row that should trigger dedup
        $directeur->notifications()->create([
            'id' => (string) Str::uuid(),
            'type' => ResultatSoumisN0Notification::class,
            'data' => ['dedup_key' => $svc->dedupKey($eventType, $resultat->id), 'type' => $eventType],
        ]);

        $notification = new ResultatSoumisN0Notification($resultat, $author);
        $svc->notifyHierarchy(
            directRecipient: null,
            workspace: $workspace,
            notification: $notification,
            eventType: $eventType,
            tacheResultatId: $resultat->id,
        );

        Notification::assertNotSentTo($directeur, ResultatSoumisN0Notification::class);
    }

    /** @test */
    public function notifications_carry_dedup_key_in_data_payload(): void
    {
        $directeur = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $directeur->id]);
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $author = User::factory()->create();
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id, 'user_id' => $author->id]);

        // Each notification's toArray must include a dedup_key
        $notifications = [
            new ResultatSoumisN0Notification($resultat, $author),
            new ResultatRenvoyeNotification($resultat, $author, 'Commentaire de renvoi long enough pour passer la validation R4 du circuit.'),
            new ResultatApprouveN0Notification($resultat, $author),
            new BypassActivatedNotification($resultat, $author),
        ];

        foreach ($notifications as $n) {
            $data = $n->toArray($directeur);
            $this->assertArrayHasKey('dedup_key', $data, get_class($n).' must expose dedup_key');
            $this->assertNotEmpty($data['dedup_key']);
        }
    }

    /** @test */
    public function via_method_returns_broadcast_channel_for_user_facing_events(): void
    {
        $user = User::factory()->create();
        $workspace = Workspace::factory()->create();
        $projet = Projet::factory()->create(['workspace_id' => $workspace->id]);
        $activite = Activite::factory()->create(['projet_id' => $projet->id]);
        $tache = Tache::factory()->create(['activite_id' => $activite->id]);
        $resultat = TacheResultat::factory()->create(['tache_id' => $tache->id, 'user_id' => $user->id]);

        $notification = new ResultatSoumisN0Notification($resultat, $user);

        $this->assertContains('broadcast', $notification->via($user));
    }
}
