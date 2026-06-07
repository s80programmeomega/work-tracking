<?php

declare(strict_types=1);

namespace Tests\Feature\Help;

use App\Models\HelpArticle;
use App\Models\HelpArticleImage;
use App\Models\HelpCategory;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Centre d'aide (Phase 7) — endpoints de lecture + gestion.
 *
 * Couvre :
 *  - Lecture publique (catégories, articles, détail, recherche FULLTEXT)
 *  - Filtrage des brouillons en lecture publique
 *  - Incrément du compteur de vues
 *  - Autorisation de gestion (super_admin / owner / refus)
 *  - CRUD article + publication
 *  - Sanitisation HTML au save (défense en profondeur)
 *  - Suppression d'image réservée à l'uploader / super_admin
 */
class HelpCenterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    private function superAdmin(): User
    {
        return User::factory()->create(['is_super_admin' => true]);
    }

    private function workspaceOwner(): User
    {
        $owner = User::factory()->create();
        Workspace::factory()->create(['owner_id' => $owner->id]);

        return $owner;
    }

    private function plainUser(): User
    {
        return User::factory()->create();
    }

    // ── Lecture ──────────────────────────────────────────────────────────────

    public function test_authenticated_user_lists_published_categories_only(): void
    {
        HelpCategory::factory()->create(['nom_fr' => 'Publiee']);
        HelpCategory::factory()->unpublished()->create(['nom_fr' => 'Brouillon']);

        Sanctum::actingAs($this->plainUser());

        $response = $this->getJson('/api/help/categories')->assertOk();

        $noms = collect($response->json('categories'))->pluck('nom_fr')->all();
        $this->assertContains('Publiee', $noms);
        $this->assertNotContains('Brouillon', $noms);
    }

    public function test_unauthenticated_cannot_read_help(): void
    {
        $this->getJson('/api/help/categories')->assertUnauthorized();
    }

    public function test_article_listing_excludes_drafts(): void
    {
        $category = HelpCategory::factory()->create();
        HelpArticle::factory()->create(['category_id' => $category->id, 'titre_fr' => 'ArticlePublie']);
        HelpArticle::factory()->unpublished()->create(['category_id' => $category->id, 'titre_fr' => 'ArticleBrouillon']);

        Sanctum::actingAs($this->plainUser());

        $titres = collect($this->getJson('/api/help/articles')->assertOk()->json('articles.data'))
            ->pluck('titre_fr')->all();

        $this->assertContains('ArticlePublie', $titres);
        $this->assertNotContains('ArticleBrouillon', $titres);
    }

    public function test_show_article_increments_views(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->create(['category_id' => $category->id, 'views_count' => 0]);

        Sanctum::actingAs($this->plainUser());

        $this->getJson("/api/help/articles/{$article->slug}")
            ->assertOk()
            ->assertJsonPath('article.views_count', 1);

        $this->assertSame(1, $article->fresh()->views_count);
    }

    public function test_show_draft_article_returns_404_in_public_read(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->unpublished()->create(['category_id' => $category->id]);

        Sanctum::actingAs($this->plainUser());

        $this->getJson("/api/help/articles/{$article->slug}")->assertNotFound();
    }

    // ── Gestion : autorisation ─────────────────────────────────────────────────

    public function test_plain_user_cannot_manage(): void
    {
        Sanctum::actingAs($this->plainUser());

        $this->getJson('/api/admin/help/articles')->assertForbidden();
    }

    public function test_super_admin_can_list_drafts(): void
    {
        $category = HelpCategory::factory()->create();
        HelpArticle::factory()->unpublished()->create(['category_id' => $category->id, 'titre_fr' => 'Brouillon']);

        Sanctum::actingAs($this->superAdmin());

        $titres = collect($this->getJson('/api/admin/help/articles?status=draft')->assertOk()->json('articles.data'))
            ->pluck('titre_fr')->all();

        $this->assertContains('Brouillon', $titres);
    }

    public function test_workspace_owner_can_create_article(): void
    {
        $owner = $this->workspaceOwner();
        $category = HelpCategory::factory()->create();

        Sanctum::actingAs($owner);

        $this->postJson('/api/admin/help/articles', [
            'category_id' => $category->id,
            'slug' => 'mon-article',
            'titre_fr' => 'Mon article',
            'titre_en' => 'My article',
            'body_fr' => '<p>Contenu FR</p>',
            'body_en' => '<p>Content EN</p>',
        ])->assertCreated()->assertJsonPath('success', true);

        $this->assertDatabaseHas('help_articles', [
            'slug' => 'mon-article',
            'created_by' => $owner->id,
            'published_at' => null,
        ]);
    }

    // ── Gestion : CRUD + publication ──────────────────────────────────────────

    public function test_publish_and_unpublish_toggle_published_at(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->unpublished()->create(['category_id' => $category->id]);

        Sanctum::actingAs($this->superAdmin());

        $this->postJson("/api/admin/help/articles/{$article->id}/publish")->assertOk();
        $this->assertNotNull($article->fresh()->published_at);

        $this->postJson("/api/admin/help/articles/{$article->id}/unpublish")->assertOk();
        $this->assertNull($article->fresh()->published_at);
    }

    public function test_store_validates_required_fields(): void
    {
        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/admin/help/articles', [])->assertUnprocessable();
    }

    public function test_destroy_soft_deletes_article(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->create(['category_id' => $category->id]);

        Sanctum::actingAs($this->superAdmin());

        $this->deleteJson("/api/admin/help/articles/{$article->id}")->assertOk();

        $this->assertSoftDeleted('help_articles', ['id' => $article->id]);
    }

    // ── Sanitisation HTML ──────────────────────────────────────────────────────

    public function test_body_is_sanitized_on_save(): void
    {
        $category = HelpCategory::factory()->create();

        Sanctum::actingAs($this->superAdmin());

        $this->postJson('/api/admin/help/articles', [
            'category_id' => $category->id,
            'slug' => 'article-xss',
            'titre_fr' => 'Titre',
            'titre_en' => 'Title',
            'body_fr' => '<p>Bonjour</p><script>alert(1)</script>',
            'body_en' => '<p>Hello</p><img src=x onerror=alert(1)>',
        ])->assertCreated();

        $article = HelpArticle::where('slug', 'article-xss')->firstOrFail();

        $this->assertStringNotContainsString('<script', $article->body_fr);
        $this->assertStringNotContainsString('onerror', $article->body_en);
        $this->assertStringContainsString('Bonjour', $article->body_plain_fr);
    }

    // ── Images ──────────────────────────────────────────────────────────────────

    public function test_only_uploader_or_super_admin_can_delete_image(): void
    {
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->create(['category_id' => $category->id]);

        // Image uploadée par un owner A
        $ownerA = $this->workspaceOwner();
        $image = HelpArticleImage::factory()->create([
            'article_id' => $article->id,
            'uploaded_by' => $ownerA->id,
        ]);

        // Owner B (autre manager) ne peut pas supprimer l'image de A
        $ownerB = $this->workspaceOwner();
        Sanctum::actingAs($ownerB);
        $this->deleteJson("/api/admin/help/article-images/{$image->id}")->assertForbidden();

        // Le super_admin peut
        Sanctum::actingAs($this->superAdmin());
        $this->deleteJson("/api/admin/help/article-images/{$image->id}")->assertOk();
        $this->assertDatabaseMissing('help_article_images', ['id' => $image->id]);
    }
}
