<?php

declare(strict_types=1);

namespace Tests\Feature\Comment;

use App\Models\Activite;
use App\Models\Comment;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Tests\Traits\AttachesWithRoleId;

/**
 * Couvre CommentController : index, store, show, update, destroy.
 */
class CommentCrudTest extends TestCase
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

    private function makeComment(?User $user = null): Comment
    {
        $user ??= $this->owner;

        return Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $this->tache->id,
            'user_id' => $user->id,
            'content' => 'Un commentaire de test',
        ]);
    }

    // =========================================================================
    // INDEX
    // =========================================================================

    /** @test */
    public function user_can_list_comments_for_a_tache(): void
    {
        $this->makeComment();

        $response = $this->actingAs($this->owner)
            ->getJson('/api/comments?commentable_type='.urlencode(Tache::class).'&commentable_id='.$this->tache->id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data', 'meta']);
    }

    /** @test */
    public function index_validates_required_commentable_params(): void
    {
        $this->actingAs($this->owner)
            ->getJson('/api/comments')
            ->assertUnprocessable();
    }

    // =========================================================================
    // STORE
    // =========================================================================

    /** @test */
    public function user_can_create_comment(): void
    {
        $response = $this->actingAs($this->owner)
            ->postJson('/api/comments', [
                'commentable_type' => Tache::class,
                'commentable_id' => $this->tache->id,
                'content' => 'Voici mon commentaire.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Commentaire créé avec succès');

        $this->assertDatabaseHas('comments', [
            'commentable_type' => Tache::class,
            'commentable_id' => $this->tache->id,
            'content' => 'Voici mon commentaire.',
        ]);
    }

    /** @test */
    public function user_can_reply_to_comment(): void
    {
        $parent = $this->makeComment();

        $response = $this->actingAs($this->owner)
            ->postJson('/api/comments', [
                'commentable_type' => Tache::class,
                'commentable_id' => $this->tache->id,
                'parent_id' => $parent->id,
                'content' => 'Réponse au commentaire.',
            ]);

        $response->assertCreated()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('comments', [
            'parent_id' => $parent->id,
            'content' => 'Réponse au commentaire.',
        ]);
    }

    /** @test */
    public function store_validates_required_fields(): void
    {
        $this->actingAs($this->owner)
            ->postJson('/api/comments', [])
            ->assertUnprocessable();
    }

    // =========================================================================
    // SHOW
    // =========================================================================

    /** @test */
    public function user_can_view_a_comment(): void
    {
        $comment = $this->makeComment();

        $this->actingAs($this->owner)
            ->getJson("/api/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonStructure(['data']);
    }

    // =========================================================================
    // UPDATE
    // =========================================================================

    /** @test */
    public function author_can_update_own_comment(): void
    {
        $comment = $this->makeComment();

        $response = $this->actingAs($this->owner)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Contenu modifié',
            ]);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('message', 'Commentaire modifié avec succès');

        $this->assertDatabaseHas('comments', [
            'id' => $comment->id,
            'content' => 'Contenu modifié',
        ]);
    }

    /** @test */
    public function non_author_cannot_update_comment(): void
    {
        $comment = $this->makeComment();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->putJson("/api/comments/{$comment->id}", [
                'content' => 'Tentative de modification',
            ])
            ->assertForbidden();
    }

    // =========================================================================
    // DESTROY
    // =========================================================================

    /** @test */
    public function author_can_delete_own_comment(): void
    {
        $comment = $this->makeComment();

        $this->actingAs($this->owner)
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertSoftDeleted('comments', ['id' => $comment->id]);
    }

    /** @test */
    public function non_author_cannot_delete_comment(): void
    {
        $comment = $this->makeComment();
        $other = User::factory()->create();

        $this->actingAs($other)
            ->deleteJson("/api/comments/{$comment->id}")
            ->assertForbidden();
    }
}
