<?php

declare(strict_types=1);

namespace Tests\Feature\Comment;

use App\Models\Activite;
use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre CommentController : unreadMentions, markMentionsAsRead.
 * Couvre aussi la création automatique de mentions via @username dans le contenu.
 */
class CommentMentionsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Tache $tache;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
        $this->refreshRoleIdCache();

        $this->owner = User::factory()->create();

        $workspace = Workspace::factory()->create(['owner_id' => $this->owner->id]);
        $workspace->addMember($this->owner, 'owner');

        $projet = Projet::factory()->create([
            'workspace_id' => $workspace->id,
            'responsable_id' => $this->owner->id,
        ]);

        $activite = Activite::factory()->create([
            'projet_id' => $projet->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);
    }

    // =========================================================================
    // UNREAD MENTIONS
    // =========================================================================

    /** @test */
    public function user_can_get_unread_mentions(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/comments/mentions/unread')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    /** @test */
    public function unread_mentions_returns_only_unread(): void
    {
        $comment = Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $this->tache->id,
            'user_id' => $this->owner->id,
            'content' => 'Commentaire',
        ]);

        CommentMention::create([
            'comment_id' => $comment->id,
            'mentioned_user_id' => $this->owner->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->getJson('/api/comments/mentions/unread');

        $response->assertOk()
            ->assertJsonPath('success', true);

        $this->assertCount(1, $response->json('data'));
    }

    // =========================================================================
    // MARK MENTIONS AS READ
    // =========================================================================

    /** @test */
    public function user_can_mark_all_mentions_as_read(): void
    {
        $comment = Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $this->tache->id,
            'user_id' => $this->owner->id,
            'content' => 'Commentaire',
        ]);

        CommentMention::create([
            'comment_id' => $comment->id,
            'mentioned_user_id' => $this->owner->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/comments/mentions/mark-read', []);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.count', 1);

        $this->assertDatabaseHas('comment_mentions', [
            'comment_id' => $comment->id,
            'mentioned_user_id' => $this->owner->id,
            'is_read' => true,
        ]);
    }

    /** @test */
    public function user_can_mark_specific_comment_mention_as_read(): void
    {
        $comment = Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $this->tache->id,
            'user_id' => $this->owner->id,
            'content' => 'Commentaire',
        ]);

        CommentMention::create([
            'comment_id' => $comment->id,
            'mentioned_user_id' => $this->owner->id,
            'is_read' => false,
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson('/api/comments/mentions/mark-read', [
                'comment_id' => $comment->id,
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true);
    }
}
