<?php

declare(strict_types=1);

namespace Tests\Feature\Comment;

use App\Models\Activite;
use App\Models\Comment;
use App\Models\CommentReaction;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre CommentController : toggleReaction (ajouter, supprimer via toggle).
 */
class CommentReactionsTest extends TestCase
{
    use AttachesWithRoleId, RefreshDatabase;

    private User $owner;

    private Comment $comment;

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

        $tache = Tache::factory()->create([
            'activite_id' => $activite->id,
            'responsable_id' => $this->owner->id,
        ]);

        $this->comment = Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $tache->id,
            'user_id' => $this->owner->id,
            'content' => 'Commentaire de base',
        ]);
    }

    // =========================================================================
    // TOGGLE REACTION
    // =========================================================================

    /** @test */
    public function user_can_add_reaction_to_comment(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson("/api/comments/{$this->comment->id}/reactions", [
                'emoji' => '👍',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.added', true);

        $this->assertDatabaseHas('comment_reactions', [
            'comment_id' => $this->comment->id,
            'user_id' => $this->owner->id,
            'emoji' => '👍',
        ]);
    }

    /** @test */
    public function toggle_reaction_removes_existing_reaction(): void
    {
        CommentReaction::create([
            'comment_id' => $this->comment->id,
            'user_id' => $this->owner->id,
            'emoji' => '❤️',
        ]);

        $response = $this->actingAs($this->owner)
            ->postJson("/api/comments/{$this->comment->id}/reactions", [
                'emoji' => '❤️',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.added', false);

        $this->assertDatabaseMissing('comment_reactions', [
            'comment_id' => $this->comment->id,
            'user_id' => $this->owner->id,
            'emoji' => '❤️',
        ]);
    }

    /** @test */
    public function toggle_reaction_validates_emoji(): void
    {
        $this->actingAs($this->owner)
            ->postJson("/api/comments/{$this->comment->id}/reactions", [])
            ->assertUnprocessable();
    }

    /** @test */
    public function different_users_can_react_to_same_comment(): void
    {
        $other = User::factory()->create();

        $this->actingAs($this->owner)
            ->postJson("/api/comments/{$this->comment->id}/reactions", ['emoji' => '👍']);

        $this->actingAs($other)
            ->postJson("/api/comments/{$this->comment->id}/reactions", ['emoji' => '👍']);

        $this->assertDatabaseCount('comment_reactions', 2);
    }
}
