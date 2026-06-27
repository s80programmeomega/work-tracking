<?php

declare(strict_types=1);

namespace Tests\Feature\Workspace;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Notifications\WorkspaceInvitationAcceptedNotification;
use App\Notifications\WorkspaceMemberBannedNotification;
use App\Permissions\ContextualPermissionGate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre les endpoints ban/unban d'un membre de workspace
 * et la notification envoyée à l'invitant quand une invitation est acceptée.
 *
 * POST   /api/workspaces/{workspace}/members/{user}/ban
 * DELETE /api/workspaces/{workspace}/members/{user}/ban
 */
class WorkspaceBanMemberTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        // Le ContextualPermissionGate est un singleton qui cache les permissions
        // par role_id. RefreshDatabase recrée les rôles avec les mêmes IDs mais
        // le cache interne est périmé — on le réinitialise explicitement.
        $this->app->forgetInstance(ContextualPermissionGate::class);

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
    }

    private function makeMember(string $role = 'collaborateur'): User
    {
        $user = User::factory()->create();
        $this->workspace->addMember($user, $role);

        return $user;
    }

    private function banUrl(User $target): string
    {
        return "/api/workspaces/{$this->workspace->id}/members/{$target->id}/ban";
    }

    // =========================================================================
    // BAN — happy path
    // =========================================================================

    /** @test */
    public function owner_can_ban_a_member(): void
    {
        Notification::fake();
        $member = $this->makeMember();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member), ['reason' => 'Comportement inapproprié']);

        $response->assertOk();

        $pivot = DB::table('workspace_members')
            ->where('workspace_id', $this->workspace->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($pivot->banned_at);
        $this->assertEquals($this->owner->id, $pivot->banned_by);
        $this->assertEquals('Comportement inapproprié', $pivot->ban_reason);
    }

    /** @test */
    public function ban_sends_notification_to_the_banned_member(): void
    {
        Notification::fake();
        $member = $this->makeMember();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member), ['reason' => 'Motif test']);

        Notification::assertSentTo($member, WorkspaceMemberBannedNotification::class);
    }

    /** @test */
    public function ban_without_reason_is_allowed(): void
    {
        Notification::fake();
        $member = $this->makeMember();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member), []);

        $response->assertOk();

        $pivot = DB::table('workspace_members')
            ->where('workspace_id', $this->workspace->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNotNull($pivot->banned_at);
        $this->assertNull($pivot->ban_reason);
    }

    // =========================================================================
    // BAN — error paths
    // =========================================================================

    /** @test */
    public function non_owner_cannot_ban_a_member(): void
    {
        Notification::fake();
        $manager = $this->makeMember('manager');
        $target = $this->makeMember();

        $response = $this->actingAs($manager, 'sanctum')
            ->postJson($this->banUrl($target));

        $response->assertForbidden();
        $this->assertStringContainsString('propriétaire', $response->json('message'));

        Notification::assertNotSentTo($target, WorkspaceMemberBannedNotification::class);
    }

    /** @test */
    public function owner_cannot_ban_themselves(): void
    {
        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($this->owner));

        $response->assertUnprocessable();
    }

    /** @test */
    public function banning_already_banned_member_returns_conflict(): void
    {
        Notification::fake();
        $member = $this->makeMember();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member), ['reason' => 'Premier ban']);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member));

        $response->assertConflict();
    }

    /** @test */
    public function banning_non_member_returns_not_found(): void
    {
        $outsider = User::factory()->create();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($outsider));

        $response->assertNotFound();
    }

    // =========================================================================
    // UNBAN — happy path
    // =========================================================================

    /** @test */
    public function owner_can_unban_a_banned_member(): void
    {
        Notification::fake();
        $member = $this->makeMember();

        $this->actingAs($this->owner, 'sanctum')
            ->postJson($this->banUrl($member), ['reason' => 'Test']);

        $response = $this->actingAs($this->owner, 'sanctum')
            ->deleteJson($this->banUrl($member));

        $response->assertOk();

        $pivot = DB::table('workspace_members')
            ->where('workspace_id', $this->workspace->id)
            ->where('user_id', $member->id)
            ->first();

        $this->assertNull($pivot->banned_at);
        $this->assertNull($pivot->banned_by);
        $this->assertNull($pivot->ban_reason);
    }

    // =========================================================================
    // UNBAN — error paths
    // =========================================================================

    /** @test */
    public function non_owner_cannot_unban_a_member(): void
    {
        $manager = $this->makeMember('manager');
        $target = $this->makeMember();

        $response = $this->actingAs($manager, 'sanctum')
            ->deleteJson($this->banUrl($target));

        $response->assertForbidden();
    }

    /** @test */
    public function unbanning_non_banned_member_returns_conflict(): void
    {
        $member = $this->makeMember();

        $response = $this->actingAs($this->owner, 'sanctum')
            ->deleteJson($this->banUrl($member));

        $response->assertConflict();
    }

    // =========================================================================
    // Invitation accepted — notification to inviter
    // =========================================================================

    /** @test */
    public function accepting_invitation_notifies_the_inviter(): void
    {
        Notification::fake();

        $inviter = User::factory()->create();
        $this->workspace->addMember($inviter, 'manager');

        $invitation = WorkspaceInvitation::create([
            'workspace_id' => $this->workspace->id,
            'email' => 'newuser@example.com',
            'invited_by' => $inviter->id,
            'role' => 'collaborateur',
            'status' => 'pending',
            'token' => Str::uuid()->toString(),
            'expires_at' => now()->addDays(7),
        ]);

        $acceptingUser = User::factory()->create(['email' => $invitation->email]);
        $acceptingUser->update(['current_workspace_id' => $this->workspace->id]);

        $this->actingAs($acceptingUser, 'sanctum')
            ->postJson("/api/workspace-invitations/{$invitation->token}/accept");

        Notification::assertSentTo($inviter, WorkspaceInvitationAcceptedNotification::class);
    }
}
