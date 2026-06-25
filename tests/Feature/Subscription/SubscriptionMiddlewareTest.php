<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * HTTP-level tests for CheckSubscriptionLimits middleware and subscription API.
 *
 * Covers:
 *  - invite endpoint → 403 when trial expired
 *  - invite endpoint → 403 when member limit reached (active trial)
 *  - invite endpoint → 200 (passes middleware) on paid workspace
 *  - super_admin bypasses subscription limits
 *  - GET /api/workspaces/{id}/subscription → 200 with summary keys
 *  - PATCH /api/workspaces/{id}/subscription → 403 for non-super-admin
 *  - PATCH /api/workspaces/{id}/subscription → 200 for super_admin, updates mode
 */
class SubscriptionMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function makeWorkspaceWithOwner(array $workspaceState = []): array
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(array_merge(['owner_id' => $owner->id], $workspaceState));
        $owner->update(['current_workspace_id' => $workspace->id]);

        return [$owner, $workspace];
    }

    public function test_invite_blocked_when_trial_expired(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceWithOwner([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(60),
            'trial_duration_days' => 30,
        ]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/workspaces/{$workspace->id}/members/invite", [
            'emails' => ['new@example.com'],
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['subscription_status' => 'trial_expired']);
    }

    public function test_invite_blocked_when_member_limit_reached(): void
    {
        config(['subscription.free_max_members' => 1]);

        [$owner, $workspace] = $this->makeWorkspaceWithOwner([
            'subscription_mode' => 'trial',
            'trial_started_at' => now(),
            'trial_duration_days' => 30,
        ]);

        $existing = User::factory()->create();
        $roleId = Role::findByName('collaborateur', 'web')->id;
        $workspace->members()->attach($existing->id, ['role_id' => $roleId]);

        Sanctum::actingAs($owner);

        $response = $this->postJson("/api/workspaces/{$workspace->id}/members/invite", [
            'emails' => ['new@example.com'],
        ]);

        $response->assertStatus(403)
            ->assertJsonFragment(['subscription_status' => 'limit_reached']);
    }

    public function test_super_admin_without_workspace_access_gets_403_on_workspace_route(): void
    {
        // Superadmin scoping: SA without workspace membership cannot access customer workspace routes.
        // The subscription middleware no longer has a SA bypass — access is denied at the workspace gate.
        $workspace = Workspace::factory()->trialExpired()->create();
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'current_workspace_id' => $workspace->id,
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->postJson("/api/workspaces/{$workspace->id}/members/invite", [
            'emails' => ['new@example.com'],
        ]);

        $response->assertStatus(403);
    }

    public function test_subscription_summary_endpoint_returns_200_for_workspace_member(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceWithOwner(['subscription_mode' => 'trial']);

        Sanctum::actingAs($owner);

        $response = $this->getJson("/api/workspaces/{$workspace->id}/subscription");

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'subscription_mode',
                    'trial_expired',
                    'remaining_trial_days',
                    'expiring_soon',
                    'limits',
                ],
            ]);
    }

    public function test_subscription_summary_endpoint_returns_403_for_outsider(): void
    {
        $outsider = User::factory()->create();
        $workspace = Workspace::factory()->create();

        Sanctum::actingAs($outsider);

        $response = $this->getJson("/api/workspaces/{$workspace->id}/subscription");

        $response->assertStatus(403);
    }

    public function test_update_subscription_returns_403_for_non_super_admin(): void
    {
        [$owner, $workspace] = $this->makeWorkspaceWithOwner();

        Sanctum::actingAs($owner);

        $response = $this->patchJson("/api/workspaces/{$workspace->id}/subscription", [
            'subscription_mode' => 'paid',
        ]);

        $response->assertStatus(403);
    }

    public function test_super_admin_can_update_subscription_mode(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $workspace = Workspace::factory()->create(['subscription_mode' => 'trial']);

        Sanctum::actingAs($superAdmin);

        $response = $this->patchJson("/api/workspaces/{$workspace->id}/subscription", [
            'subscription_mode' => 'paid',
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.subscription_mode', 'paid');

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'subscription_mode' => 'paid',
        ]);
    }

    public function test_super_admin_can_update_trial_duration(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $workspace = Workspace::factory()->create([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(20),
            'trial_duration_days' => 30,
        ]);

        Sanctum::actingAs($superAdmin);

        $response = $this->patchJson("/api/workspaces/{$workspace->id}/subscription", [
            'trial_duration_days' => 60,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.trial_expired', false);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'trial_duration_days' => 60,
        ]);
    }
}
