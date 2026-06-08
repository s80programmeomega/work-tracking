<?php

declare(strict_types=1);

namespace Tests\Feature\Payment;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\Payment\FakePaymentProvider;
use App\Services\Payment\MtnMomoProvider;
use App\Services\Payment\PaymentProviderRegistry;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 9 — flux de paiement (MTN MoMo + Orange Money).
 *
 * Tout est testé avec Http::fake() : aucun appel réseau réel, aucun secret.
 * Couvre : initiation MTN (push) + Orange (redirect), confirmation par webhook
 * vérifiée (succès → activation), échec/expiration → pas d'activation,
 * idempotence (callback rejoué), autorisation (non-propriétaire), plan gratuit.
 */
class PaymentFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);

        // Config de paiement déterministe pour les tests.
        config([
            // Mode factice désactivé par défaut : les tests exercent les VRAIS
            // fournisseurs (via Http::fake). Les cas dédiés le réactivent localement.
            'payment.fake' => false,
            'payment.mtn_momo.base_url' => 'https://sandbox.momodeveloper.mtn.com',
            'payment.mtn_momo.subscription_key' => 'test-key',
            'payment.mtn_momo.api_user' => 'test-user',
            'payment.mtn_momo.api_key' => 'test-secret',
            'payment.orange_money.base_url' => 'https://api.orange.com',
            'payment.orange_money.token_url' => 'https://api.orange.com/oauth/v3/token',
            'payment.orange_money.webpayment_url' => 'https://api.orange.com/orange-money-webpay/dev/v1/webpayment',
            'payment.orange_money.status_url' => 'https://api.orange.com/orange-money-webpay/dev/v1/transactionstatus',
            'payment.orange_money.merchant_key' => 'merchant-123',
            'payment.orange_money.currency' => 'OUV',
        ]);
    }

    private function ownerWithWorkspace(): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }

    private function paidPlan(): Plan
    {
        return Plan::where('slug', 'starter')->first();
    }

    // ── Initiation ────────────────────────────────────────────────────────────

    public function test_mtn_initiate_creates_pending_payment(): void
    {
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok'], 200),
            '*/collection/v1_0/requesttopay' => Http::response(null, 202),
        ]);

        [$owner] = $this->ownerWithWorkspace();
        Sanctum::actingAs($owner);

        $response = $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->paidPlan()->id,
            'provider' => Payment::PROVIDER_MTN,
            'payer_phone' => '237650000000',
        ]);

        $response->assertCreated()->assertJsonPath('payment.status', Payment::STATUS_PENDING);
        $this->assertDatabaseHas('payments', [
            'provider' => Payment::PROVIDER_MTN,
            'status' => Payment::STATUS_PENDING,
        ]);
    }

    public function test_orange_initiate_returns_redirect_url(): void
    {
        Http::fake([
            '*/oauth/v3/token' => Http::response(['access_token' => 'tok'], 200),
            '*/webpayment' => Http::response([
                'payment_url' => 'https://pay.orange.test/abc',
                'pay_token' => 'ptk-1',
                'notif_token' => 'ntk-1',
            ], 201),
        ]);

        [$owner] = $this->ownerWithWorkspace();
        Sanctum::actingAs($owner);

        $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->paidPlan()->id,
            'provider' => Payment::PROVIDER_ORANGE,
            'payer_phone' => '237650000000',
        ])->assertCreated()->assertJsonPath('redirect_url', 'https://pay.orange.test/abc');
    }

    public function test_non_owner_cannot_initiate(): void
    {
        [, $workspace] = $this->ownerWithWorkspace();
        $stranger = User::factory()->create(['current_workspace_id' => $workspace->id]);
        Sanctum::actingAs($stranger);

        $this->postJson('/api/payment/initiate', [
            'plan_id' => $this->paidPlan()->id,
            'provider' => Payment::PROVIDER_MTN,
            'payer_phone' => '237650000000',
        ])->assertStatus(403);
    }

    public function test_free_plan_cannot_be_paid(): void
    {
        [$owner] = $this->ownerWithWorkspace();
        Sanctum::actingAs($owner);

        $this->postJson('/api/payment/initiate', [
            'plan_id' => Plan::where('slug', 'free')->first()->id,
            'provider' => Payment::PROVIDER_MTN,
            'payer_phone' => '237650000000',
        ])->assertStatus(422);
    }

    // ── Confirmation par webhook (statut RE-VÉRIFIÉ) ────────────────────────────

    public function test_momo_webhook_success_activates_subscription(): void
    {
        [, $workspace] = $this->ownerWithWorkspace();
        $plan = $this->paidPlan();
        $payment = Payment::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'provider' => Payment::PROVIDER_MTN,
            'status' => Payment::STATUS_PENDING,
        ]);

        // À la réception du webhook, notre code RE-INTERROGE MTN : on simule SUCCESSFUL.
        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok'], 200),
            '*/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL', 'financialTransactionId' => 'FT1'], 200),
        ]);

        $this->postJson('/api/webhooks/payment/momo', ['referenceId' => $payment->reference])
            ->assertOk();

        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
        $fresh = $workspace->fresh();
        $this->assertSame('active', $fresh->subscription_status);
        $this->assertSame('paid', $fresh->subscription_mode);
        $this->assertSame($plan->id, $fresh->plan_id);
    }

    public function test_momo_webhook_failed_does_not_activate(): void
    {
        [, $workspace] = $this->ownerWithWorkspace();
        $payment = Payment::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $this->paidPlan()->id,
            'provider' => Payment::PROVIDER_MTN,
            'status' => Payment::STATUS_PENDING,
        ]);

        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok'], 200),
            '*/collection/v1_0/requesttopay/*' => Http::response(['status' => 'FAILED'], 200),
        ]);

        $this->postJson('/api/webhooks/payment/momo', ['referenceId' => $payment->reference])->assertOk();

        $this->assertSame(Payment::STATUS_FAILED, $payment->fresh()->status);
        $this->assertNotSame('active', $workspace->fresh()->subscription_status);
    }

    public function test_webhook_is_idempotent_on_replay(): void
    {
        [, $workspace] = $this->ownerWithWorkspace();
        $plan = $this->paidPlan();
        $payment = Payment::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'provider' => Payment::PROVIDER_MTN,
            'status' => Payment::STATUS_PENDING,
        ]);

        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok'], 200),
            '*/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        // Deux callbacks identiques (rejeu fournisseur).
        $this->postJson('/api/webhooks/payment/momo', ['referenceId' => $payment->reference])->assertOk();
        $endsAtAfterFirst = $workspace->fresh()->subscription_ends_at;

        $this->postJson('/api/webhooks/payment/momo', ['referenceId' => $payment->reference])->assertOk();

        // Le paiement reste succeeded une seule fois ; l'activation n'a pas été rejouée.
        $this->assertSame(Payment::STATUS_SUCCEEDED, $payment->fresh()->status);
        $this->assertEquals($endsAtAfterFirst, $workspace->fresh()->subscription_ends_at);
    }

    public function test_webhook_unknown_reference_returns_404(): void
    {
        Http::fake();

        $this->postJson('/api/webhooks/payment/momo', ['referenceId' => 'does-not-exist'])
            ->assertStatus(404);
    }

    public function test_status_poll_reverifies_pending_payment_and_activates(): void
    {
        // Flux push : sans webhook, le sondage du statut doit RE-INTERROGER MTN
        // et conclure le paiement (SUCCESSFUL) → activation.
        [$owner, $workspace] = $this->ownerWithWorkspace();
        $plan = $this->paidPlan();
        $payment = Payment::factory()->create([
            'workspace_id' => $workspace->id,
            'plan_id' => $plan->id,
            'user_id' => $owner->id,
            'provider' => Payment::PROVIDER_MTN,
            'status' => Payment::STATUS_PENDING,
        ]);

        Http::fake([
            '*/collection/token/' => Http::response(['access_token' => 'tok'], 200),
            '*/collection/v1_0/requesttopay/*' => Http::response(['status' => 'SUCCESSFUL'], 200),
        ]);

        Sanctum::actingAs($owner);

        $this->getJson("/api/payment/{$payment->reference}/status")
            ->assertOk()
            ->assertJsonPath('payment.status', Payment::STATUS_SUCCEEDED);

        $this->assertSame('active', $workspace->fresh()->subscription_status);
    }

    // ── Mode factice (dev) + verrou anti-production ────────────────────────────

    public function test_fake_provider_is_used_when_enabled_outside_production(): void
    {
        // Hors production + PAYMENT_FAKE=true → le registre renvoie le faux fournisseur.
        $this->app['env'] = 'local';
        config(['payment.fake' => true]);

        $registry = app(PaymentProviderRegistry::class);

        $this->assertTrue($registry->fakeEnabled());
        $this->assertInstanceOf(
            FakePaymentProvider::class,
            $registry->for(Payment::PROVIDER_MTN),
        );
    }

    public function test_fake_provider_is_blocked_in_production(): void
    {
        // Même avec PAYMENT_FAKE=true, la production NE DOIT JAMAIS utiliser le faux.
        $this->app['env'] = 'production';
        config(['payment.fake' => true]);

        $registry = app(PaymentProviderRegistry::class);

        $this->assertFalse($registry->fakeEnabled());
        $this->assertInstanceOf(
            MtnMomoProvider::class,
            $registry->for(Payment::PROVIDER_MTN),
        );
    }
}
