<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Events\Realtime\SessionsAllRevoked;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

/**
 * Couvre la commande admin:expire-accounts (suspension/suppression auto des
 * comptes superadmin temporaires expirés).
 */
class ExpireSuperAdminAccountsTest extends TestCase
{
    use RefreshDatabase;

    private User $directeur;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $this->directeur = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->directeur->id]);
        $this->directeur->syncRoles(['directeur']);
    }

    private function makeExpiredTempAdmin(string $expiryAction = 'suspend'): User
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'admin_expires_at' => now()->subDay(),
            'admin_expiry_action' => $expiryAction,
            'created_by' => $this->directeur->id,
        ]);
        $user->syncRoles(['super_admin']);

        DB::table('temporary_access')->insert([
            'user_id' => $user->id,
            'accessible_type' => Workspace::class,
            'accessible_id' => $this->workspace->id,
            'role' => 'observateur',
            'created_by' => $this->directeur->id,
            'expires_at' => now()->subDay(),
            'created_at' => now()->subDays(8),
        ]);

        return $user;
    }

    /** @test */
    public function suspends_expired_account_and_keeps_grants(): void
    {
        $user = $this->makeExpiredTempAdmin('suspend');

        $this->artisan('admin:expire-accounts')->assertExitCode(0);

        $user->refresh();
        $this->assertFalse($user->is_active);
        $this->assertFalse($user->is_super_admin);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
        $this->assertDatabaseHas('temporary_access', [
            'user_id' => $user->id,
            'accessible_id' => $this->workspace->id,
        ]);
    }

    /** @test */
    public function deletes_expired_account_and_grants_when_action_is_delete(): void
    {
        $user = $this->makeExpiredTempAdmin('delete');

        $this->artisan('admin:expire-accounts')->assertExitCode(0);

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
        $this->assertDatabaseMissing('temporary_access', ['user_id' => $user->id]);
    }

    /** @test */
    public function broadcasts_session_revocation_for_realtime_logout(): void
    {
        Event::fake([SessionsAllRevoked::class]);
        $user = $this->makeExpiredTempAdmin('suspend');

        $this->artisan('admin:expire-accounts')->assertExitCode(0);

        Event::assertDispatched(SessionsAllRevoked::class, fn ($e) => $e->user->is($user));
    }

    /** @test */
    public function dry_run_makes_no_changes(): void
    {
        $user = $this->makeExpiredTempAdmin('suspend');

        $this->artisan('admin:expire-accounts', ['--dry-run' => true])->assertExitCode(0);

        $user->refresh();
        $this->assertTrue($user->is_active);
        $this->assertTrue($user->is_super_admin);
    }
}
