<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Models\Plan;
use App\Models\User;
use App\Models\Workspace;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Phase 8 — garde d'abonnement globale (CheckSubscriptionStatus), plans,
 * et activation/verrou.
 *
 * Couvre :
 *  - workspace 'locked' → 402 sur une route protégée
 *  - routes exemptées (subscription/*) accessibles même verrouillé
 *  - super_admin contourne le verrou
 *  - catalogue de plans, sélection (gratuit immédiat / payant pending)
 *  - activation manuelle super_admin + verrou/déverrou
 *  - limites lues depuis le plan effectif (illimité = -1)
 */
class SubscriptionStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->artisan('db:seed', ['--class' => 'PlanSeeder']);
    }

    private function ownerWithWorkspace(array $state = []): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(array_merge(['owner_id' => $owner->id], $state));
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }

    // ── Middleware global : verrou ────────────────────────────────────────────

    public function test_locked_workspace_gets_402_on_protected_route(): void
    {
        [$owner] = $this->ownerWithWorkspace(['subscription_status' => 'locked']);
        Sanctum::actingAs($owner);

        $this->getJson('/api/dashboard')
            ->assertStatus(402)
            ->assertJsonFragment(['subscription_status' => 'locked']);
    }

    public function test_locked_workspace_can_still_access_subscription_routes(): void
    {
        [$owner] = $this->ownerWithWorkspace(['subscription_status' => 'locked']);
        Sanctum::actingAs($owner);

        // Route exemptée → doit passer (200) malgré le verrou.
        $this->getJson('/api/subscription/plans')->assertStatus(200);
    }

    public function test_super_admin_bypasses_lock(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        [, $workspace] = $this->ownerWithWorkspace(['subscription_status' => 'locked']);
        $admin->update(['current_workspace_id' => $workspace->id]);

        Sanctum::actingAs($admin);

        $this->getJson('/api/dashboard')->assertStatus(200);
    }

    public function test_active_workspace_passes_protected_route(): void
    {
        [$owner] = $this->ownerWithWorkspace([
            'subscription_status' => 'active',
            'subscription_mode' => 'paid',
            'subscription_ends_at' => now()->addDays(30),
        ]);
        Sanctum::actingAs($owner);

        $this->getJson('/api/dashboard')->assertStatus(200);
    }

    // ── Plans + sélection ─────────────────────────────────────────────────────

    public function test_plans_endpoint_lists_active_plans(): void
    {
        [$owner] = $this->ownerWithWorkspace();
        Sanctum::actingAs($owner);

        $slugs = collect($this->getJson('/api/subscription/plans')->assertOk()->json('plans'))
            ->pluck('slug')->all();

        $this->assertContains('free', $slugs);
        $this->assertContains('pro', $slugs);
    }

    public function test_owner_selects_free_plan_applied_immediately(): void
    {
        [$owner, $workspace] = $this->ownerWithWorkspace();
        $free = Plan::where('slug', 'free')->first();

        Sanctum::actingAs($owner);

        $this->postJson('/api/subscription/select', ['plan_id' => $free->id])
            ->assertOk()
            ->assertJsonPath('subscription.subscription_status', 'free');

        $this->assertSame($free->id, $workspace->fresh()->plan_id);
    }

    public function test_owner_selects_paid_plan_goes_pending(): void
    {
        [$owner, $workspace] = $this->ownerWithWorkspace();
        $pro = Plan::where('slug', 'pro')->first();

        Sanctum::actingAs($owner);

        $this->postJson('/api/subscription/select', ['plan_id' => $pro->id])
            ->assertOk()
            ->assertJsonPath('subscription.subscription_status', 'pending');
    }

    public function test_non_owner_cannot_select_plan(): void
    {
        [, $workspace] = $this->ownerWithWorkspace();
        $stranger = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $free = Plan::where('slug', 'free')->first();

        Sanctum::actingAs($stranger);

        $this->postJson('/api/subscription/select', ['plan_id' => $free->id, 'workspace_id' => $workspace->id])
            ->assertStatus(403);
    }

    // ── Activation / verrou (super_admin) ─────────────────────────────────────

    public function test_super_admin_activates_paid_plan(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        [, $workspace] = $this->ownerWithWorkspace();
        $pro = Plan::where('slug', 'pro')->first();

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/subscription/{$workspace->id}/activate", [
            'plan_id' => $pro->id,
            'period_days' => 30,
        ])->assertOk()->assertJsonPath('subscription.subscription_status', 'active');

        $fresh = $workspace->fresh();
        $this->assertSame('paid', $fresh->subscription_mode);
        $this->assertNotNull($fresh->subscription_ends_at);
    }

    public function test_non_super_admin_cannot_activate(): void
    {
        [$owner, $workspace] = $this->ownerWithWorkspace();
        $pro = Plan::where('slug', 'pro')->first();

        Sanctum::actingAs($owner);

        $this->postJson("/api/admin/subscription/{$workspace->id}/activate", ['plan_id' => $pro->id])
            ->assertStatus(403);
    }

    public function test_super_admin_lock_then_unlock(): void
    {
        $admin = User::factory()->create(['is_super_admin' => true]);
        [, $workspace] = $this->ownerWithWorkspace();

        Sanctum::actingAs($admin);

        $this->postJson("/api/admin/subscription/{$workspace->id}/lock")->assertOk();
        $this->assertSame('locked', $workspace->fresh()->subscription_status);

        $this->postJson("/api/admin/subscription/{$workspace->id}/unlock")->assertOk();
        $this->assertSame('lapsed', $workspace->fresh()->subscription_status);
    }

    // ── Limites pilotées par le plan ──────────────────────────────────────────

    public function test_paid_pro_plan_grants_unlimited_members(): void
    {
        [, $workspace] = $this->ownerWithWorkspace([
            'subscription_status' => 'active',
            'subscription_mode' => 'paid',
        ]);
        $pro = Plan::where('slug', 'pro')->first();
        $workspace->update(['plan_id' => $pro->id]);

        $service = app(SubscriptionService::class);

        // Pro = membres illimités (-1) → toujours autorisé.
        $this->assertTrue($service->canAddMember($workspace->fresh()));
    }

    public function test_lapsed_workspace_falls_back_to_free_plan_limits(): void
    {
        [, $workspace] = $this->ownerWithWorkspace(['subscription_status' => 'lapsed']);
        $service = app(SubscriptionService::class);

        $plan = $service->effectivePlan($workspace);
        $this->assertNotNull($plan);
        $this->assertTrue($plan->is_free);
    }
}
