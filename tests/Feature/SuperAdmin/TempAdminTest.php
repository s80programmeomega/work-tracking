<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Events\Realtime\SessionsAllRevoked;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\TempAdminAccessGrantedNotification;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Couvre les endpoints de gestion des comptes admin temporaires.
 *
 * POST   /api/admin/temp-admins
 * POST   /api/admin/temp-admins/{user}/send-credentials
 */
class TempAdminTest extends TestCase
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

    // =========================================================================
    // POST /api/admin/temp-admins — création
    // =========================================================================

    /** @test */
    public function directeur_can_create_temp_admin_for_own_workspace(): void
    {
        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Admin Test',
                'email' => 'tempAdmin@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        $response->assertStatus(201);

        $this->assertDatabaseHas('users', [
            'email' => 'tempAdmin@example.com',
            'is_super_admin' => true,
            'admin_expiry_action' => 'suspend',
            'created_by' => $this->directeur->id,
        ]);

        $this->assertDatabaseHas('temporary_access', [
            'accessible_type' => Workspace::class,
            'accessible_id' => $this->workspace->id,
            'role' => 'observateur',
        ]);
    }

    /** @test */
    public function directeur_can_grant_access_to_multiple_own_workspaces(): void
    {
        $ws2 = Workspace::factory()->create(['owner_id' => $this->directeur->id]);

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Admin Multi',
                'email' => 'multiAdmin@example.com',
                'expires_in_days' => 30,
                'expiry_action' => 'delete',
                'workspace_ids' => [$this->workspace->id, $ws2->id],
            ]);

        $response->assertStatus(201);

        $userId = $response->json('data.id');
        $this->assertDatabaseCount('temporary_access', 2);
        $this->assertEquals(2, DB::table('temporary_access')->where('user_id', $userId)->count());
    }

    /** @test */
    public function directeur_cannot_grant_access_to_workspace_they_do_not_own(): void
    {
        $otherWorkspace = Workspace::factory()->create();

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Admin Test',
                'email' => 'badAdmin@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$otherWorkspace->id],
            ]);

        $response->assertForbidden();
        $this->assertDatabaseMissing('users', ['email' => 'badAdmin@example.com']);
    }

    /** @test */
    public function admin_expires_at_is_set_to_now_plus_requested_days(): void
    {
        $days = 14;

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Expiry Test',
                'email' => 'expiry@example.com',
                'expires_in_days' => $days,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        $response->assertStatus(201);

        $user = User::where('email', 'expiry@example.com')->sole();
        $this->assertNotNull($user->admin_expires_at);
        $this->assertEqualsWithDelta(
            now()->addDays($days)->timestamp,
            $user->admin_expires_at->timestamp,
            5
        );
        $this->assertEquals(
            now()->addDays($days)->toDateString(),
            $user->admin_expires_at->toDateString()
        );
    }

    /** @test */
    public function create_temp_admin_rejects_expires_in_days_above_365(): void
    {
        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Too Long',
                'email' => 'toolong@example.com',
                'expires_in_days' => 366,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['expires_in_days']);
    }

    /** @test */
    public function create_temp_admin_rejects_expires_in_days_below_1(): void
    {
        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Zero Days',
                'email' => 'zero@example.com',
                'expires_in_days' => 0,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['expires_in_days']);
    }

    /** @test */
    public function create_temp_admin_requires_at_least_one_workspace(): void
    {
        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Admin Test',
                'email' => 'noWs@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['workspace_ids']);
    }

    /** @test */
    public function create_temp_admin_fails_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'exists@example.com']);

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Duplicate',
                'email' => 'exists@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        $response->assertUnprocessable();
        $response->assertJsonValidationErrors(['email']);
    }

    /** @test */
    public function creation_does_not_send_notification_automatically(): void
    {
        Notification::fake();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Silent Admin',
                'email' => 'silent@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ]);

        Notification::assertNothingSent();
    }

    /** @test */
    public function unauthenticated_user_cannot_create_temp_admin(): void
    {
        $this->postJson('/api/admin/temp-admins', [
            'nom' => 'Hacker',
            'email' => 'hacker@example.com',
            'expires_in_days' => 7,
            'expiry_action' => 'suspend',
            'workspace_ids' => [$this->workspace->id],
        ])->assertUnauthorized();
    }

    /** @test */
    public function regular_user_cannot_create_temp_admin(): void
    {
        $regular = User::factory()->create();
        $regular->syncRoles(['utilisateur']);

        $this->actingAs($regular, 'sanctum')
            ->postJson('/api/admin/temp-admins', [
                'nom' => 'Admin Test',
                'email' => 'noAccess@example.com',
                'expires_in_days' => 7,
                'expiry_action' => 'suspend',
                'workspace_ids' => [$this->workspace->id],
            ])->assertForbidden();
    }

    // =========================================================================
    // POST /api/admin/temp-admins/{user}/send-credentials
    // =========================================================================

    private function makeTempAdmin(): User
    {
        $user = User::factory()->create([
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays(7),
            'admin_expiry_action' => 'suspend',
            'created_by' => $this->directeur->id,
        ]);
        $user->syncRoles(['super_admin']);

        DB::table('temporary_access')->insert([
            'user_id' => $user->id,
            'accessible_type' => Workspace::class,
            'accessible_id' => $this->workspace->id,
            'role' => 'observateur',
            'created_by' => $this->directeur->id,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        return $user;
    }

    /** @test */
    public function directeur_can_send_credentials_for_own_temp_admin(): void
    {
        Notification::fake();
        $tempAdmin = $this->makeTempAdmin();

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/temp-admins/{$tempAdmin->id}/send-credentials");

        $response->assertOk();
        Notification::assertSentTo($tempAdmin, TempAdminAccessGrantedNotification::class);
    }

    /** @test */
    public function sending_credentials_resets_the_password(): void
    {
        Notification::fake();
        $tempAdmin = $this->makeTempAdmin();
        $oldHash = $tempAdmin->password;

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/temp-admins/{$tempAdmin->id}/send-credentials");

        $this->assertNotEquals($oldHash, $tempAdmin->fresh()->password);
    }

    /** @test */
    public function directeur_cannot_send_credentials_for_another_directeurs_temp_admin(): void
    {
        Notification::fake();
        $other = User::factory()->create();
        $other->syncRoles(['directeur']);
        $otherWorkspace = Workspace::factory()->create(['owner_id' => $other->id]);

        $tempAdmin = User::factory()->create([
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays(7),
            'created_by' => $other->id,
        ]);
        $tempAdmin->syncRoles(['super_admin']);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/temp-admins/{$tempAdmin->id}/send-credentials")
            ->assertForbidden();

        Notification::assertNothingSent();
    }

    /** @test */
    public function cannot_send_credentials_for_non_temp_admin(): void
    {
        $regular = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/temp-admins/{$regular->id}/send-credentials")
            ->assertUnprocessable();
    }

    // =========================================================================
    // POST /api/admin/superadmins/{user}/terminate + /reactivate — suspend/restore
    // =========================================================================

    /** @test */
    public function terminating_broadcasts_session_revocation_for_realtime_logout(): void
    {
        Event::fake([SessionsAllRevoked::class]);
        $tempAdmin = $this->makeTempAdmin();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/terminate")
            ->assertOk();

        Event::assertDispatched(SessionsAllRevoked::class, fn ($e) => $e->user->is($tempAdmin));
    }

    /** @test */
    public function terminating_with_suspend_action_deactivates_but_keeps_grants(): void
    {
        $tempAdmin = $this->makeTempAdmin();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/terminate")
            ->assertOk();

        $tempAdmin->refresh();
        $this->assertFalse($tempAdmin->is_active);
        $this->assertFalse($tempAdmin->is_super_admin);

        // Le compte n'est pas supprimé et le grant workspace est conservé pour la réactivation.
        $this->assertDatabaseHas('users', ['id' => $tempAdmin->id]);
        $this->assertDatabaseHas('temporary_access', [
            'user_id' => $tempAdmin->id,
            'accessible_id' => $this->workspace->id,
        ]);
    }

    /** @test */
    public function terminating_with_delete_action_removes_grants_and_account(): void
    {
        $tempAdmin = User::factory()->create([
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays(7),
            'admin_expiry_action' => 'delete',
            'created_by' => $this->directeur->id,
        ]);
        $tempAdmin->syncRoles(['super_admin']);

        DB::table('temporary_access')->insert([
            'user_id' => $tempAdmin->id,
            'accessible_type' => Workspace::class,
            'accessible_id' => $this->workspace->id,
            'role' => 'observateur',
            'created_by' => $this->directeur->id,
            'expires_at' => now()->addDays(7),
            'created_at' => now(),
        ]);

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/terminate")
            ->assertOk();

        $this->assertDatabaseMissing('users', ['id' => $tempAdmin->id]);
        $this->assertDatabaseMissing('temporary_access', ['user_id' => $tempAdmin->id]);
    }

    /** @test */
    public function reactivating_a_suspended_temp_admin_restores_workspace_access(): void
    {
        $tempAdmin = $this->makeTempAdmin();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/terminate")
            ->assertOk();

        $response = $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/reactivate");

        $response->assertOk();

        $tempAdmin->refresh();
        $this->assertTrue($tempAdmin->is_active);
        $this->assertTrue($tempAdmin->is_super_admin);
        $this->assertTrue($tempAdmin->hasRole('super_admin'));

        // Le grant workspace doit être restauré avec une expiration dans le futur,
        // sinon WorkspaceController rejette toujours l'accès malgré le compte actif.
        $grant = DB::table('temporary_access')
            ->where('user_id', $tempAdmin->id)
            ->where('accessible_id', $this->workspace->id)
            ->first();

        $this->assertNotNull($grant);
        $this->assertTrue(Carbon::parse($grant->expires_at)->isFuture());
    }

    /** @test */
    public function cannot_reactivate_an_active_account(): void
    {
        $tempAdmin = $this->makeTempAdmin();

        $this->actingAs($this->directeur, 'sanctum')
            ->postJson("/api/admin/superadmins/{$tempAdmin->id}/reactivate")
            ->assertUnprocessable();
    }
}
