<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints de gestion des invitations du workspace :
 * invitations, resendInvitation, cancelInvitation, allInvitations,
 * invitationStatistics.
 */
class WorkspaceInvitationsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
    }

    private function makePendingInvitation(): WorkspaceInvitation
    {
        return WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'email' => fake()->safeEmail(),
            'role' => 'collaborateur',
            'token' => Str::uuid()->toString(),
            'invited_by' => $this->owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);
    }

    // =========================================================================
    // LIST INVITATIONS
    // =========================================================================

    /** @test */
    public function owner_can_list_workspace_invitations(): void
    {
        $this->makePendingInvitation();

        $response = $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/invitations");

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_manager_cannot_list_invitations(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/workspaces/{$this->workspace->id}/members/invitations")
            ->assertForbidden();
    }

    // =========================================================================
    // RESEND INVITATION
    // =========================================================================

    /** @test */
    public function owner_can_resend_invitation(): void
    {
        Notification::fake();

        $invitation = $this->makePendingInvitation();
        $originalExpiry = $invitation->expires_at;

        $this->travel(1)->days();

        $response = $this->actingAs($this->owner)
            ->postJson("/api/workspaces/{$this->workspace->id}/members/invitations/{$invitation->id}/resend");

        $response->assertOk()
            ->assertJsonPath('message', 'Invitation renvoyée avec succès');

        $this->assertTrue(
            $invitation->fresh()->expires_at->greaterThan($originalExpiry)
        );
    }

    // =========================================================================
    // CANCEL INVITATION
    // =========================================================================

    /** @test */
    public function owner_can_cancel_invitation(): void
    {
        $invitation = $this->makePendingInvitation();

        $this->actingAs($this->owner)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/invitations/{$invitation->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Invitation annulée avec succès');

        $this->assertDatabaseHas('workspace_invitations', [
            'id' => $invitation->id,
            'status' => 'cancelled',
        ]);
    }

    /** @test */
    public function non_owner_cannot_cancel_invitation(): void
    {
        $invitation = $this->makePendingInvitation();
        $collaborateur = User::factory()->create();
        $this->workspace->addMember($collaborateur, 'collaborateur');

        $this->actingAs($collaborateur)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/members/invitations/{$invitation->id}")
            ->assertForbidden();
    }

    // =========================================================================
    // ALL INVITATIONS (super admin)
    // =========================================================================

    /** @test */
    public function super_admin_can_list_all_invitations(): void
    {
        $this->makePendingInvitation();

        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/workspace-invitations/all');

        $response->assertOk()
            ->assertJsonStructure(['data', 'meta', 'statistics']);
    }

    /** @test */
    public function non_super_admin_cannot_list_all_invitations(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/workspace-invitations/all')
            ->assertForbidden();
    }

    // =========================================================================
    // INVITATION STATISTICS (super admin)
    // =========================================================================

    /** @test */
    public function super_admin_can_view_invitation_statistics(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($superAdmin)
            ->getJson('/api/workspace-invitations/statistics');

        $response->assertOk()
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function non_super_admin_cannot_view_invitation_statistics(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/workspace-invitations/statistics')
            ->assertForbidden();
    }
}
