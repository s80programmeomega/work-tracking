<?php

declare(strict_types=1);

namespace Tests\Feature\Invitations;

use App\Models\Projet;
use App\Models\ProjetInvitation;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\ProjetInvitationNotification;
use App\Notifications\WorkspaceInvitationNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Covers the invitation system:
 *   - Workspace invite sends email, creates record
 *   - Workspace invite to existing member returns error
 *   - Workspace resend extends expiry AND sends email
 *   - Workspace cancel sets status to cancelled
 *   - Projet invite (workspace member) adds directly without email
 *   - Projet invite (external) sends email
 *   - Projet invite stores all 7 permission columns
 *   - Accepting projet invitation transfers all 7 permissions to pivot
 *   - Accepting invalid/expired invitation returns 404
 */
class InvitationSystemTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private Workspace $workspace;

    private Projet $projet;

    private User $owner;

    private User $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();
        $this->buildWorld();
    }

    private function buildWorld(): void
    {
        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->attachWithRole($this->workspace->members(), $this->owner->id, 'owner');

        $this->member = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $this->member->id, 'cadre');

        $this->projet = Projet::factory()->create([
            'workspace_id' => $this->workspace->id,
            'responsable_id' => $this->owner->id,
        ]);
        $this->attachWithRole($this->projet->members(), $this->owner->id, 'manager');
    }

    // ─── Workspace invitations ────────────────────────────────────────────────

    public function test_workspace_invite_creates_record_and_sends_email(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        $externalEmail = 'ws_invite_'.Str::random(6).'@example.com';

        $this->postJson("/api/workspaces/{$this->workspace->id}/members/invite", [
            'emails' => [$externalEmail],
            'role' => 'cadre',
        ])->assertOk();

        $this->assertDatabaseHas('workspace_invitations', [
            'workspace_id' => $this->workspace->id,
            'email' => $externalEmail,
            'role' => 'cadre',
            'status' => 'pending',
        ]);

        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
    }

    public function test_workspace_invite_to_existing_member_returns_error(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        // Le contrôleur retourne 422 quand tous les destinataires sont en erreur
        $this->postJson("/api/workspaces/{$this->workspace->id}/members/invite", [
            'emails' => [$this->member->email],
            'role' => 'cadre',
        ])->assertStatus(422)
            ->assertJsonPath('data.error_count', 1)
            ->assertJsonPath('data.success_count', 0);

        Notification::assertNothingSent();
    }

    public function test_workspace_resend_extends_expiry_and_sends_email(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'email' => 'resend_'.Str::random(6).'@example.com',
            'role' => 'cadre',
            'token' => Str::random(64),
            'invited_by' => $this->owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(2),
        ]);

        $this->postJson("/api/workspaces/{$this->workspace->id}/members/invitations/{$invitation->id}/resend")
            ->assertOk()
            ->assertJsonPath('message', 'Invitation renvoyée avec succès');

        $this->assertGreaterThan(
            now()->addDays(6),
            $invitation->fresh()->expires_at
        );

        Notification::assertSentOnDemand(WorkspaceInvitationNotification::class);
    }

    public function test_workspace_cancel_sets_status_cancelled(): void
    {
        Sanctum::actingAs($this->owner);

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'email' => 'cancel_'.Str::random(6).'@example.com',
            'role' => 'cadre',
            'token' => Str::random(64),
            'invited_by' => $this->owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        $this->deleteJson("/api/workspaces/{$this->workspace->id}/members/invitations/{$invitation->id}")
            ->assertOk();

        $this->assertDatabaseHas('workspace_invitations', [
            'id' => $invitation->id,
            'status' => 'cancelled',
        ]);
    }

    // ─── Projet invitations ───────────────────────────────────────────────────

    public function test_projet_invite_workspace_member_adds_directly_without_email(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        $this->postJson("/api/projets/{$this->projet->id}/invitations", [
            'emails' => [$this->member->email],
            'role' => 'collaborateur',
            'send_email' => false,
        ])->assertOk()
            ->assertJsonPath('data.direct_add_count', 1)
            ->assertJsonPath('data.invitation_count', 0);

        $this->assertTrue($this->projet->members()->where('user_id', $this->member->id)->exists());
        Notification::assertNothingSent();
    }

    public function test_projet_invite_external_user_sends_email_and_creates_record(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        $externalEmail = 'ext_'.Str::random(6).'@example.com';

        $this->postJson("/api/projets/{$this->projet->id}/invitations", [
            'emails' => [$externalEmail],
            'role' => 'collaborateur',
            'can_edit' => true,
        ])->assertOk()
            ->assertJsonPath('data.invitation_count', 1);

        $this->assertDatabaseHas('projet_invitations', [
            'projet_id' => $this->projet->id,
            'email' => $externalEmail,
            'status' => 'pending',
            'can_edit' => true,
        ]);

        Notification::assertSentOnDemand(ProjetInvitationNotification::class);
    }

    public function test_projet_invite_stores_all_seven_permission_columns(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        $externalEmail = 'perms_'.Str::random(6).'@example.com';

        $this->postJson("/api/projets/{$this->projet->id}/invitations", [
            'emails' => [$externalEmail],
            'role' => 'cadre',
            'can_edit' => true,
            'can_delete' => true,
            'can_invite' => true,
            'can_delete_member' => true,
            'can_create_activity' => true,
            'can_edit_activity' => true,
            'can_delete_activity' => true,
        ])->assertOk();

        $this->assertDatabaseHas('projet_invitations', [
            'email' => $externalEmail,
            'can_edit' => true,
            'can_delete' => true,
            'can_invite' => true,
            'can_delete_member' => true,
            'can_create_activity' => true,
            'can_edit_activity' => true,
            'can_delete_activity' => true,
        ]);
    }

    public function test_accepting_projet_invitation_transfers_all_permissions_to_pivot(): void
    {
        $invitee = User::factory()->create();
        $this->attachWithRole($this->workspace->members(), $invitee->id, 'cadre');

        $invitation = ProjetInvitation::create([
            'projet_id' => $this->projet->id,
            'email' => $invitee->email,
            'role' => 'member',
            'can_edit' => true,
            'can_delete' => false,
            'can_invite' => true,
            'can_delete_member' => true,
            'can_create_activity' => true,
            'can_edit_activity' => false,
            'can_delete_activity' => true,
            'token' => Str::random(64),
            'invited_by' => $this->owner->id,
            'status' => 'pending',
            'expires_at' => now()->addDays(7),
        ]);

        Sanctum::actingAs($invitee);

        $this->postJson("/api/invitations/projet/{$invitation->token}/accept")
            ->assertOk();

        $pivot = $this->projet->members()->where('user_id', $invitee->id)->first()?->pivot;
        $this->assertNotNull($pivot, 'L\'invité doit être membre du projet après acceptation');
        $this->assertTrue((bool) $pivot->can_edit);
        $this->assertFalse((bool) $pivot->can_delete);
        $this->assertTrue((bool) $pivot->can_invite);
        $this->assertTrue((bool) $pivot->can_delete_member);
        $this->assertTrue((bool) $pivot->can_create_activity);
        $this->assertFalse((bool) $pivot->can_edit_activity);
        $this->assertTrue((bool) $pivot->can_delete_activity);
    }

    public function test_accepting_expired_projet_invitation_returns_404(): void
    {
        $invitee = User::factory()->create();

        $invitation = ProjetInvitation::create([
            'projet_id' => $this->projet->id,
            'email' => $invitee->email,
            'role' => 'member',
            'token' => Str::random(64),
            'invited_by' => $this->owner->id,
            'status' => 'pending',
            'expires_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($invitee);

        $this->postJson("/api/invitations/projet/{$invitation->token}/accept")
            ->assertStatus(404);
    }

    public function test_projet_invite_already_member_returns_error(): void
    {
        Notification::fake();
        Sanctum::actingAs($this->owner);

        // Le contrôleur retourne 422 quand tous les destinataires sont en erreur
        $this->postJson("/api/projets/{$this->projet->id}/invitations", [
            'emails' => [$this->owner->email],
            'role' => 'collaborateur',
        ])->assertStatus(422)
            ->assertJsonPath('data.error_count', 1)
            ->assertJsonPath('data.success_count', 0);
    }
}
