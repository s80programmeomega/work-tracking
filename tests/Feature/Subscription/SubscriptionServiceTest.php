<?php

declare(strict_types=1);

namespace Tests\Feature\Subscription;

use App\Models\User;
use App\Models\Workspace;
use App\Services\SubscriptionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Unit-style tests for SubscriptionService business logic.
 *
 * Covers:
 *  - isTrialExpired(): paid workspace → false
 *  - isTrialExpired(): trial with no start date → false
 *  - isTrialExpired(): trial past expiry → true
 *  - isTrialExpired(): trial still active → false
 *  - getRemainingTrialDays(): paid → PHP_INT_MAX
 *  - getRemainingTrialDays(): trial active → correct days
 *  - getRemainingTrialDays(): trial expired → 0
 *  - isExpiringSoon(): within warning window → true
 *  - isExpiringSoon(): outside warning window → false
 *  - canAddMember(): paid → always true
 *  - canAddMember(): trial at limit → false
 *  - canAddMember(): trial below limit → true
 *  - summary(): returns expected keys
 */
class SubscriptionServiceTest extends TestCase
{
    use RefreshDatabase;

    private SubscriptionService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->service = app(SubscriptionService::class);
    }

    public function test_paid_workspace_is_not_trial_expired(): void
    {
        $workspace = Workspace::factory()->paid()->create();

        $this->assertFalse($this->service->isTrialExpired($workspace));
    }

    public function test_trial_without_start_date_is_not_expired(): void
    {
        $workspace = Workspace::factory()->create([
            'subscription_mode' => 'trial',
            'trial_started_at' => null,
        ]);

        $this->assertFalse($this->service->isTrialExpired($workspace));
    }

    public function test_trial_past_duration_is_expired(): void
    {
        $workspace = Workspace::factory()->trialExpired()->create();

        $this->assertTrue($this->service->isTrialExpired($workspace));
    }

    public function test_trial_still_active_is_not_expired(): void
    {
        $workspace = Workspace::factory()->create([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(5),
            'trial_duration_days' => 30,
        ]);

        $this->assertFalse($this->service->isTrialExpired($workspace));
    }

    public function test_paid_workspace_remaining_days_is_max_int(): void
    {
        $workspace = Workspace::factory()->paid()->create();

        $this->assertSame(PHP_INT_MAX, $this->service->getRemainingTrialDays($workspace));
    }

    public function test_remaining_days_calculated_correctly_for_active_trial(): void
    {
        $workspace = Workspace::factory()->create([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(20),
            'trial_duration_days' => 30,
        ]);

        $remaining = $this->service->getRemainingTrialDays($workspace);
        $this->assertGreaterThanOrEqual(9, $remaining);
        $this->assertLessThanOrEqual(10, $remaining);
    }

    public function test_remaining_days_is_zero_when_trial_expired(): void
    {
        $workspace = Workspace::factory()->trialExpired()->create();

        $this->assertSame(0, $this->service->getRemainingTrialDays($workspace));
    }

    public function test_expiring_soon_within_warning_window(): void
    {
        $workspace = Workspace::factory()->trialExpiringSoon(3)->create();

        $this->assertTrue($this->service->isExpiringSoon($workspace));
    }

    public function test_not_expiring_soon_outside_warning_window(): void
    {
        $workspace = Workspace::factory()->create([
            'subscription_mode' => 'trial',
            'trial_started_at' => now()->subDays(5),
            'trial_duration_days' => 30,
        ]);

        $this->assertFalse($this->service->isExpiringSoon($workspace));
    }

    public function test_paid_workspace_can_always_add_member(): void
    {
        $workspace = Workspace::factory()->paid()->create();

        $this->assertTrue($this->service->canAddMember($workspace));
    }

    public function test_trial_at_member_limit_cannot_add_member(): void
    {
        config(['subscription.free_max_members' => 2]);
        $workspace = Workspace::factory()->create(['subscription_mode' => 'trial']);

        $roleId = Role::findByName('collaborateur', 'web')->id;
        User::factory()->count(2)->create()->each(function (User $u) use ($workspace, $roleId) {
            $workspace->members()->attach($u->id, ['role_id' => $roleId]);
        });

        $this->assertFalse($this->service->canAddMember($workspace));
    }

    public function test_trial_below_member_limit_can_add_member(): void
    {
        config(['subscription.free_max_members' => 5]);
        $workspace = Workspace::factory()->create(['subscription_mode' => 'trial']);

        $this->assertTrue($this->service->canAddMember($workspace));
    }

    public function test_summary_returns_expected_keys(): void
    {
        $workspace = Workspace::factory()->create(['subscription_mode' => 'trial']);

        $summary = $this->service->summary($workspace);

        $this->assertArrayHasKey('subscription_mode', $summary);
        $this->assertArrayHasKey('trial_expired', $summary);
        $this->assertArrayHasKey('remaining_trial_days', $summary);
        $this->assertArrayHasKey('expiring_soon', $summary);
        $this->assertArrayHasKey('limits', $summary);
        $this->assertArrayHasKey('members', $summary['limits']);
    }
}
