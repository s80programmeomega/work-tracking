<?php

declare(strict_types=1);

namespace Tests\Feature\Chat;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceChannel;
use App\Models\WorkspaceMessage;
use App\Notifications\WorkspaceChat\WorkspaceMessageMentionNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Phase 3 — Chat workspace (canaux responsibles + global).
 */
class WorkspaceChatTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Workspace $workspace;

    private WorkspaceChannel $globalChannel;

    private WorkspaceChannel $responsiblesChannel;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();
        $this->workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $this->workspace->addMember($this->owner, 'owner');
        $this->owner->update(['current_workspace_id' => $this->workspace->id]);

        // WorkspaceObserver crée les canaux via firstOrCreate lors de la création du workspace.
        $this->globalChannel = WorkspaceChannel::where('workspace_id', $this->workspace->id)
            ->where('type', WorkspaceChannel::TYPE_GLOBAL)
            ->firstOrFail();

        $this->responsiblesChannel = WorkspaceChannel::where('workspace_id', $this->workspace->id)
            ->where('type', WorkspaceChannel::TYPE_RESPONSIBLES)
            ->firstOrFail();
    }

    private function makeCollaborateur(): User
    {
        $user = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($user, 'collaborateur');

        return $user;
    }

    private function makeCadre(): User
    {
        $user = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($user, 'cadre');

        return $user;
    }

    // =========================================================================
    // CANAUX AUTO-CRÉÉS PAR OBSERVER
    // =========================================================================

    /** @test */
    public function seed_channels_command_creates_both_channels_for_workspace(): void
    {
        // Supprimer les canaux créés dans setUp pour tester la commande
        WorkspaceChannel::where('workspace_id', $this->workspace->id)->delete();

        $this->artisan('workspace:seed-channels', ['--workspace' => $this->workspace->id])
            ->assertSuccessful();

        $this->assertDatabaseHas('workspace_channels', [
            'workspace_id' => $this->workspace->id,
            'type' => WorkspaceChannel::TYPE_GLOBAL,
        ]);
        $this->assertDatabaseHas('workspace_channels', [
            'workspace_id' => $this->workspace->id,
            'type' => WorkspaceChannel::TYPE_RESPONSIBLES,
        ]);
    }

    // =========================================================================
    // LISTE DES CANAUX
    // =========================================================================

    /** @test */
    public function member_can_list_workspace_channels(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/chat/channels")
            ->assertOk()
            ->assertJsonCount(2, 'data');
    }

    /** @test */
    public function non_member_cannot_list_channels(): void
    {
        $outsider = User::factory()->create();

        $this->actingAs($outsider)
            ->getJson("/api/workspaces/{$this->workspace->id}/chat/channels")
            ->assertForbidden();
    }

    // =========================================================================
    // CANAL GLOBAL — tous les membres peuvent envoyer
    // =========================================================================

    /** @test */
    public function any_member_can_send_to_global_channel(): void
    {
        $collab = $this->makeCollaborateur();

        $this->actingAs($collab)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/channels/global/messages", [
                'content' => 'Bonjour à tous !',
            ])
            ->assertCreated();

        $this->assertDatabaseHas('workspace_messages', [
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $collab->id,
            'content' => 'Bonjour à tous !',
        ]);
    }

    /** @test */
    public function member_can_list_global_messages(): void
    {
        WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Premier message.',
        ]);

        $this->actingAs($this->makeCollaborateur())
            ->getJson("/api/workspaces/{$this->workspace->id}/chat/channels/global/messages")
            ->assertOk()
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // CANAL RESPONSABLES — gated (cadre+)
    // =========================================================================

    /** @test */
    public function cadre_can_send_to_responsibles_channel(): void
    {
        $cadre = $this->makeCadre();

        $this->actingAs($cadre)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/channels/responsibles/messages", [
                'content' => 'Discussion réservée.',
            ])
            ->assertCreated();
    }

    /** @test */
    public function collaborateur_cannot_send_to_responsibles_channel(): void
    {
        $collab = $this->makeCollaborateur();

        $this->actingAs($collab)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/channels/responsibles/messages", [
                'content' => 'Tentative interdite.',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // MODIFICATION
    // =========================================================================

    /** @test */
    public function author_can_update_their_message(): void
    {
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Contenu original.',
        ]);

        $this->actingAs($this->owner)
            ->patchJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}", [
                'content' => 'Contenu modifié.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('workspace_messages', [
            'uuid' => $msg->uuid,
            'content' => 'Contenu modifié.',
            'is_edited' => true,
        ]);
    }

    /** @test */
    public function non_author_cannot_update_message(): void
    {
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Message de l\'owner.',
        ]);

        $collab = $this->makeCollaborateur();

        $this->actingAs($collab)
            ->patchJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}", [
                'content' => 'Tentative de modification.',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // SUPPRESSION
    // =========================================================================

    /** @test */
    public function author_can_delete_their_message(): void
    {
        $collab = $this->makeCollaborateur();
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $collab->id,
            'content' => 'Message à supprimer.',
        ]);

        $this->actingAs($collab)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}")
            ->assertOk();

        $this->assertSoftDeleted('workspace_messages', ['uuid' => $msg->uuid]);
    }

    /** @test */
    public function manager_can_delete_any_message(): void
    {
        $collab = $this->makeCollaborateur();
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $collab->id,
            'content' => 'Message supprimable par manager.',
        ]);

        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($manager, 'manager');

        $this->actingAs($manager)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}")
            ->assertOk();

        $this->assertSoftDeleted('workspace_messages', ['uuid' => $msg->uuid]);
    }

    // =========================================================================
    // RÉACTIONS
    // =========================================================================

    /** @test */
    public function member_can_add_reaction(): void
    {
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Message avec réaction.',
        ]);

        $this->actingAs($this->makeCollaborateur())
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}/reactions", [
                'emoji' => '👍',
            ])
            ->assertOk();

        $this->assertDatabaseHas('workspace_message_reactions', [
            'workspace_message_id' => $msg->id,
            'emoji' => '👍',
        ]);
    }

    /** @test */
    public function member_can_remove_reaction(): void
    {
        $collab = $this->makeCollaborateur();
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Message.',
        ]);

        $msg->reactions()->create(['user_id' => $collab->id, 'emoji' => '❤️']);

        $this->actingAs($collab)
            ->deleteJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}/reactions", [
                'emoji' => '❤️',
            ])
            ->assertOk();

        $this->assertDatabaseMissing('workspace_message_reactions', [
            'workspace_message_id' => $msg->id,
            'user_id' => $collab->id,
            'emoji' => '❤️',
        ]);
    }

    // =========================================================================
    // MARQUER COMME LU
    // =========================================================================

    /** @test */
    public function member_can_mark_channel_as_read(): void
    {
        $collab = $this->makeCollaborateur();

        $this->actingAs($collab)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/channels/global/read")
            ->assertOk();

        $this->assertDatabaseHas('workspace_channel_reads', [
            'user_id' => $collab->id,
            'workspace_channel_id' => $this->globalChannel->id,
        ]);
    }

    // =========================================================================
    // UNREAD COUNTS
    // =========================================================================

    /** @test */
    public function unread_count_returns_correct_structure(): void
    {
        $this->actingAs($this->owner)
            ->getJson("/api/workspaces/{$this->workspace->id}/chat/unread")
            ->assertOk()
            ->assertJsonStructure(['responsibles', 'global', 'total']);
    }

    // =========================================================================
    // MENTION NOTIFICATION
    // =========================================================================

    /** @test */
    public function mention_dispatches_notification_to_mentioned_user(): void
    {
        Notification::fake();

        $collab = $this->makeCollaborateur();

        $this->actingAs($this->owner)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/channels/global/messages", [
                'content' => "Bonjour @{$collab->nom}",
                'mentions' => [$collab->id],
            ])
            ->assertCreated();

        Notification::assertSentTo($collab, WorkspaceMessageMentionNotification::class);
    }

    // =========================================================================
    // ÉPINGLAGE
    // =========================================================================

    /** @test */
    public function manager_can_pin_message(): void
    {
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Message à épingler.',
        ]);

        $manager = User::factory()->create(['current_workspace_id' => $this->workspace->id]);
        $this->workspace->addMember($manager, 'manager');

        $this->actingAs($manager)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}/pin")
            ->assertOk();

        $this->assertDatabaseHas('workspace_messages', [
            'uuid' => $msg->uuid,
            'is_pinned' => true,
        ]);
    }

    /** @test */
    public function collaborateur_cannot_pin_message(): void
    {
        $collab = $this->makeCollaborateur();
        $msg = WorkspaceMessage::create([
            'workspace_id' => $this->workspace->id,
            'workspace_channel_id' => $this->globalChannel->id,
            'user_id' => $this->owner->id,
            'content' => 'Message.',
        ]);

        $this->actingAs($collab)
            ->postJson("/api/workspaces/{$this->workspace->id}/chat/messages/{$msg->uuid}/pin")
            ->assertForbidden();
    }
}
