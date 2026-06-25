<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Models\AdminAuditLog;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AdminAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;
use Tests\TestCase;

class AdminAuditLogTest extends TestCase
{
    use RefreshDatabase;

    // =========================================================================
    // AdminAuditService::log()
    // =========================================================================

    /** @test */
    public function log_creates_record_for_permanent_superadmin(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true, 'admin_expires_at' => null]);

        AdminAuditService::log($sa, 'stats.read');

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $sa->id,
            'actor_type' => 'permanent_superadmin',
            'action' => 'stats.read',
            'created_by' => null,
        ]);
    }

    /** @test */
    public function log_creates_record_for_temporary_superadmin(): void
    {
        $directeur = User::factory()->create();
        $tempSa = User::factory()->create([
            'is_super_admin' => true,
            'admin_expires_at' => now()->addDays(7),
            'created_by' => $directeur->id,
        ]);

        AdminAuditService::log($tempSa, 'workspace.read');

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $tempSa->id,
            'actor_type' => 'temporary_superadmin',
            'action' => 'workspace.read',
            'created_by' => $directeur->id,
        ]);
    }

    /** @test */
    public function log_stores_target_type_and_id(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true]);
        $workspace = Workspace::factory()->create();

        AdminAuditService::log($sa, 'workspace.suspend', $workspace, ['reason' => 'spam']);

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $sa->id,
            'action' => 'workspace.suspend',
            'target_type' => 'Workspace',
            'target_id' => $workspace->id,
        ]);

        $log = AdminAuditLog::where('actor_id', $sa->id)->first();
        $this->assertEquals(['reason' => 'spam'], $log->context);
    }

    // =========================================================================
    // GET /api/admin/audit-log — permanent superadmin only
    // =========================================================================

    /** @test */
    public function audit_log_returns_paginated_results_for_superadmin(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true]);
        AdminAuditLog::factory()->count(5)->create(['actor_id' => $sa->id, 'actor_type' => 'permanent_superadmin']);

        $response = $this->actingAs($sa)->getJson('/api/admin/audit-log');

        $response->assertOk()
            ->assertJsonStructure([
                'success',
                'data',
                'meta' => ['current_page', 'last_page', 'per_page', 'total'],
            ]);

        $this->assertEquals(5, $response->json('meta.total'));
    }

    /** @test */
    public function audit_log_is_forbidden_for_non_superadmin(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)->getJson('/api/admin/audit-log')->assertForbidden();
    }

    /** @test */
    public function audit_log_filters_by_action(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true]);
        AdminAuditLog::factory()->create(['actor_id' => $sa->id, 'action' => 'stats.read', 'actor_type' => 'permanent_superadmin']);
        AdminAuditLog::factory()->create(['actor_id' => $sa->id, 'action' => 'workspace.suspend', 'actor_type' => 'permanent_superadmin']);

        $response = $this->actingAs($sa)->getJson('/api/admin/audit-log?action=stats.read');

        $response->assertOk();
        $this->assertEquals(1, $response->json('meta.total'));
        $this->assertEquals('stats.read', $response->json('data.0.action'));
    }

    // =========================================================================
    // GET /api/admin/my-audit-log — directeur scoped view
    // =========================================================================

    /** @test */
    public function my_audit_log_returns_only_logs_created_by_directeur(): void
    {
        // Le super-admin a accès à platform.operator (isSuperAdmin() fiable sans cache Spatie).
        // Ce test vérifie le comportement de scoping (created_by) — pas l'accès au gate.
        $directeur = User::factory()->create(['is_super_admin' => true, 'admin_expires_at' => null]);
        $otherDir = User::factory()->create();

        AdminAuditLog::factory()->count(3)->create(['created_by' => $directeur->id, 'actor_type' => 'temporary_superadmin']);
        AdminAuditLog::factory()->count(2)->create(['created_by' => $otherDir->id, 'actor_type' => 'temporary_superadmin']);

        $response = $this->actingAs($directeur)->getJson('/api/admin/my-audit-log');

        $response->assertOk();
        $this->assertEquals(3, $response->json('meta.total'));
    }

    /** @test */
    public function my_audit_log_is_forbidden_for_regular_user(): void
    {
        $user = User::factory()->create(['is_super_admin' => false]);

        $this->actingAs($user)->getJson('/api/admin/my-audit-log')->assertForbidden();
    }

    // =========================================================================
    // AdminController — logging wired correctly
    // =========================================================================

    /** @test */
    public function stats_endpoint_logs_audit_entry(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true]);

        $this->actingAs($sa)->getJson('/api/admin/stats');

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $sa->id,
            'action' => 'stats.read',
        ]);
    }

    /** @test */
    public function suspend_workspace_logs_audit_entry(): void
    {
        $sa = User::factory()->create(['is_super_admin' => true]);
        $workspace = Workspace::factory()->create(['is_active' => true]);

        $this->actingAs($sa)->postJson("/api/admin/workspaces/{$workspace->id}/suspend", [
            'reason' => 'test reason',
        ]);

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $sa->id,
            'action' => 'workspace.suspend',
            'target_type' => 'Workspace',
            'target_id' => $workspace->id,
        ]);
    }

    /** @test */
    public function update_user_role_logs_audit_entry(): void
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $sa = User::factory()->create(['is_super_admin' => true]);
        $user = User::factory()->create(['is_super_admin' => false]);
        $user->assignRole('utilisateur');

        $response = $this->actingAs($sa)->patchJson("/api/admin/users/{$user->id}/role", [
            'role' => 'directeur',
        ]);

        $response->assertOk();

        $this->assertDatabaseHas('admin_audit_logs', [
            'actor_id' => $sa->id,
            'action' => 'user.role_updated',
            'target_id' => $user->id,
        ]);
    }
}
