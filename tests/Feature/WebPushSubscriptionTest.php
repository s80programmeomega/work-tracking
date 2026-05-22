<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\PushSubscription;
use App\Models\User;
use App\Notifications\Channels\WebPushChannel;
use App\Services\NotificationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tests pour Task 8b — souscription Web Push.
 *
 * Couvre :
 *   - Les endpoints API (vapid-key / subscribe / unsubscribe / index)
 *   - L'intégration de NotificationService::channelsFor() avec le canal webpush
 *
 * Note : la livraison effective (signature VAPID, sérialisation, appel HTTP
 * au push service) n'est PAS testée ici — elle dépend de la lib externe
 * minishlink/web-push et de réponses du push service. Pour ces points, on
 * fait confiance à la lib (largement utilisée, MIT, v10.x).
 */
class WebPushSubscriptionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        // Clés VAPID factices pour les tests qui dépendent de la config.
        // Les vraies clés (depuis .env) ne doivent pas fuiter dans les tests.
        config()->set('webpush.vapid.public_key', 'TEST_PUBLIC_KEY_FOR_UNIT_TESTS');
        config()->set('webpush.vapid.private_key', 'TEST_PRIVATE_KEY_FOR_UNIT_TESTS');
        config()->set('webpush.vapid.subject', 'mailto:test@example.com');
    }

    /** @test */
    public function vapid_key_endpoint_returns_public_key_to_authenticated_user(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/webpush/vapid-key');

        $response->assertOk()
            ->assertJson(['public_key' => 'TEST_PUBLIC_KEY_FOR_UNIT_TESTS']);
    }

    /** @test */
    public function vapid_key_endpoint_returns_503_when_not_configured(): void
    {
        config()->set('webpush.vapid.public_key', null);

        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/webpush/vapid-key');

        $response->assertStatus(503);
    }

    /** @test */
    public function vapid_key_endpoint_requires_authentication(): void
    {
        $this->getJson('/api/webpush/vapid-key')->assertUnauthorized();
    }

    /** @test */
    public function subscribe_endpoint_persists_a_new_subscription(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/webpush/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'keys' => [
                'p256dh' => 'public-key-base64',
                'auth' => 'auth-token-base64',
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['subscription_id', 'message']);

        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/abc123',
            'public_key' => 'public-key-base64',
            'auth_token' => 'auth-token-base64',
            'active' => true,
        ]);
    }

    /** @test */
    public function subscribe_endpoint_upserts_when_endpoint_already_exists(): void
    {
        $user = User::factory()->create();

        // Première souscription
        $this->actingAs($user)->postJson('/api/webpush/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/same',
            'keys' => ['p256dh' => 'old-key', 'auth' => 'old-auth'],
        ])->assertStatus(201);

        // Deuxième passage avec mêmes endpoint, clés différentes → update, pas duplicate
        $response = $this->actingAs($user)->postJson('/api/webpush/subscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/same',
            'keys' => ['p256dh' => 'new-key', 'auth' => 'new-auth'],
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseCount('push_subscriptions', 1);
        $this->assertDatabaseHas('push_subscriptions', [
            'user_id' => $user->id,
            'public_key' => 'new-key',
            'auth_token' => 'new-auth',
        ]);
    }

    /** @test */
    public function subscribe_endpoint_validates_required_fields(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/webpush/subscribe', [
            'endpoint' => 'not-a-url',
            'keys' => [],
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['endpoint', 'keys.p256dh', 'keys.auth']);
    }

    /** @test */
    public function unsubscribe_endpoint_deactivates_the_subscription(): void
    {
        $user = User::factory()->create();
        $sub = PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/to-deactivate',
            'public_key' => 'pk',
            'auth_token' => 'ak',
            'content_encoding' => 'aesgcm',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->deleteJson('/api/webpush/unsubscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/to-deactivate',
        ]);

        $response->assertOk()
            ->assertJson(['deactivated' => true]);

        $this->assertFalse($sub->fresh()->active);
    }

    /** @test */
    public function unsubscribe_endpoint_returns_no_op_when_endpoint_unknown(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson('/api/webpush/unsubscribe', [
            'endpoint' => 'https://fcm.googleapis.com/fcm/send/never-registered',
        ]);

        $response->assertOk()
            ->assertJson(['deactivated' => false]);
    }

    /** @test */
    public function index_endpoint_lists_only_active_subscriptions_of_the_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();

        // 2 souscriptions actives pour $user + 1 inactive + 1 pour un autre user
        PushSubscription::create([
            'user_id' => $user->id, 'endpoint' => 'https://test/1',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true, 'device_type' => 'desktop',
        ]);
        PushSubscription::create([
            'user_id' => $user->id, 'endpoint' => 'https://test/2',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true, 'device_type' => 'mobile',
        ]);
        PushSubscription::create([
            'user_id' => $user->id, 'endpoint' => 'https://test/3',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => false, // inactive
        ]);
        PushSubscription::create([
            'user_id' => $other->id, 'endpoint' => 'https://test/4',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true,
        ]);

        $response = $this->actingAs($user)->getJson('/api/webpush/subscriptions');

        $response->assertOk();
        $this->assertCount(2, $response->json('data'));
    }

    /** @test */
    public function channels_for_excludes_webpush_when_user_has_no_active_subscription(): void
    {
        $user = User::factory()->create();
        $svc = app(NotificationService::class);

        $channels = $svc->channelsFor($user, 'renvoye_n0');

        $this->assertNotContains(WebPushChannel::class, $channels);
    }

    /** @test */
    public function channels_for_includes_webpush_when_user_has_active_subscription_and_high_signal_event(): void
    {
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://test/active',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true,
        ]);

        $svc = app(NotificationService::class);
        $channels = $svc->channelsFor($user, 'renvoye_n0');

        $this->assertContains(WebPushChannel::class, $channels);
    }

    /** @test */
    public function channels_for_excludes_webpush_when_push_enabled_preference_is_false(): void
    {
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://test/active',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true,
        ]);
        $user->getOrCreateNotificationPreference()->update(['push_enabled' => false]);

        $svc = app(NotificationService::class);
        $channels = $svc->channelsFor($user, 'renvoye_n0');

        $this->assertNotContains(WebPushChannel::class, $channels);
    }

    /** @test */
    public function channels_for_excludes_webpush_for_low_signal_events_even_with_active_subscription(): void
    {
        $user = User::factory()->create();
        PushSubscription::create([
            'user_id' => $user->id,
            'endpoint' => 'https://test/active',
            'public_key' => 'k', 'auth_token' => 'a', 'content_encoding' => 'aesgcm',
            'active' => true,
        ]);

        $svc = app(NotificationService::class);

        // score_updated est silencieux côté push (low signal)
        $this->assertNotContains(WebPushChannel::class, $svc->channelsFor($user, 'score_updated'));
        // approuve_n0 aussi
        $this->assertNotContains(WebPushChannel::class, $svc->channelsFor($user, 'approuve_n0'));
    }
}
