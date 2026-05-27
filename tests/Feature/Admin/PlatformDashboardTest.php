<?php

declare(strict_types=1);

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TrialExtendedNotification;
use App\Notifications\WorkspaceSuspendedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Feature tests for the platform admin dashboard endpoints.
 *
 * Covers:
 *  - GET /admin/stats → 403 for regular user
 *  - GET /admin/stats → 200 with correct structure for super_admin
 *  - GET /admin/workspaces → 403 for regular user
 *  - GET /admin/workspaces → 200 with paginated list for super_admin
 *  - GET /admin/users → 403 for regular user
 *  - GET /admin/users → 200 with paginated list for super_admin
 *  - POST /admin/workspaces/{id}/extend-trial → 200, updates duration, sends notification
 *  - POST /admin/workspaces/{id}/suspend → 200, marks inactive, sends notification
 *  - POST /admin/workspaces/{id}/reactivate → 200, marks active again
 */
class PlatformDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function regularUser(): User
    {
        return User::factory()->create(['is_super_admin' => false]);
    }

    public function test_stats_returns_403_for_regular_user(): void
    {
        Sanctum::actingAs($this->regularUser());

        $this->getJson('/api/admin/stats')->assertStatus(403);
    }

    public function test_stats_returns_200_for_super_admin(): void
    {
        Workspace::factory()->count(3)->create();

        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/admin/stats');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    'workspaces' => ['total', 'active', 'trial', 'paid', 'expired_trials', 'expiring_soon'],
                    'users' => ['total', 'active_last_30_days'],
                    'recent_workspaces',
                ],
            ]);
    }

    public function test_stats_workspace_counts_are_correct(): void
    {
        Workspace::factory()->count(2)->paid()->create();
        Workspace::factory()->create(['subscription_mode' => 'trial']);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/admin/stats');
        $data = $response->json('data.workspaces');

        $this->assertGreaterThanOrEqual(2, $data['paid']);
        $this->assertGreaterThanOrEqual(1, $data['trial']);
    }

    public function test_workspaces_returns_403_for_regular_user(): void
    {
        Sanctum::actingAs($this->regularUser());

        $this->getJson('/api/admin/workspaces')->assertStatus(403);
    }

    public function test_workspaces_returns_paginated_list_for_super_admin(): void
    {
        Workspace::factory()->count(3)->create();

        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/admin/workspaces');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'last_page', 'total']);
    }

    public function test_users_returns_403_for_regular_user(): void
    {
        Sanctum::actingAs($this->regularUser());

        $this->getJson('/api/admin/users')->assertStatus(403);
    }

    public function test_users_returns_paginated_list_for_super_admin(): void
    {
        User::factory()->count(3)->create();

        Sanctum::actingAs($this->superAdmin());

        $response = $this->getJson('/api/admin/users');

        $response->assertStatus(200)
            ->assertJsonStructure(['data', 'current_page', 'last_page']);
    }

    public function test_extend_trial_updates_duration_and_notifies_owner(): void
    {
        Notification::fake();

        $owner = $this->regularUser();
        $workspace = Workspace::factory()->create([
            'owner_id' => $owner->id,
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(5),
            'trial_duration_days' => 30,
        ]);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->postJson("/api/admin/workspaces/{$workspace->id}/extend-trial", [
            'trial_duration_days' => 60,
        ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.trial_expired', false);

        $this->assertDatabaseHas('workspaces', [
            'id' => $workspace->id,
            'trial_duration_days' => 60,
        ]);

        Notification::assertSentTo($owner, TrialExtendedNotification::class);
    }

    public function test_suspend_workspace_marks_inactive_and_notifies_owner(): void
    {
        Notification::fake();

        $owner = $this->regularUser();
        $workspace = Workspace::factory()->create([
            'owner_id' => $owner->id,
            'is_active' => true,
        ]);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->postJson("/api/admin/workspaces/{$workspace->id}/suspend", [
            'reason' => 'Policy violation',
        ]);

        $response->assertStatus(200);

        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'is_active' => false]);

        Notification::assertSentTo($owner, WorkspaceSuspendedNotification::class);
    }

    public function test_reactivate_workspace_marks_active(): void
    {
        $workspace = Workspace::factory()->create(['is_active' => false]);

        Sanctum::actingAs($this->superAdmin());

        $this->postJson("/api/admin/workspaces/{$workspace->id}/reactivate")
            ->assertStatus(200);

        $this->assertDatabaseHas('workspaces', ['id' => $workspace->id, 'is_active' => true]);
    }

    public function test_extend_trial_returns_403_for_regular_user(): void
    {
        $workspace = Workspace::factory()->create();

        Sanctum::actingAs($this->regularUser());

        $this->postJson("/api/admin/workspaces/{$workspace->id}/extend-trial", [
            'trial_duration_days' => 60,
        ])->assertStatus(403);
    }
}
