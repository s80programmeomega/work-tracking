<?php

declare(strict_types=1);

namespace Tests\Feature\Help;

use App\Models\HelpArticle;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Phase 11D — PATCH /api/admin/help/articles/{article}/draft
 *
 * Vérifie que l'endpoint de brouillon :
 *   - enregistre uniquement les colonnes draft_*, jamais body_fr/body_en ni published_at
 *   - retourne draft_saved_at à jour
 *   - refuse les utilisateurs non autorisés (401, 403)
 *   - fonctionne pour un super_admin et pour un membre ayant help_articles.edit
 */
class HelpArticleDraftTest extends TestCase
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

    private function plainUser(): User
    {
        return User::factory()->create();
    }

    private function memberWithEditPermission(): User
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

        $role = Role::create([
            'name' => 'draft-test-'.uniqid(),
            'guard_name' => 'web',
        ]);
        $role->syncPermissions(
            Permission::where('name', 'help_articles.edit')
                ->where('guard_name', 'web')
                ->get()
        );

        $member = User::factory()->create(['current_workspace_id' => $workspace->id]);
        $workspace->members()->attach($member->id, ['role_id' => $role->id]);

        return $member;
    }

    // ── Accès ────────────────────────────────────────────────────────────────

    public function test_unauthenticated_request_returns_401(): void
    {
        $article = HelpArticle::factory()->create();

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Brouillon</p>',
        ])->assertUnauthorized();
    }

    public function test_user_without_help_edit_permission_is_forbidden(): void
    {
        $article = HelpArticle::factory()->create();

        Sanctum::actingAs($this->plainUser());

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Brouillon</p>',
        ])->assertForbidden();
    }

    // ── Comportement nominal ──────────────────────────────────────────────────

    public function test_super_admin_can_save_draft(): void
    {
        $article = HelpArticle::factory()->create([
            'body_fr' => '<p>Contenu publié</p>',
            'published_at' => now()->subDay(),
        ]);

        Sanctum::actingAs($this->superAdmin());

        $response = $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Nouveau brouillon FR</p>',
            'draft_body_en' => '<p>New draft EN</p>',
        ]);

        $response->assertOk()
            ->assertJsonStructure(['success', 'draft_saved_at'])
            ->assertJson(['success' => true]);

        $this->assertNotNull($response->json('draft_saved_at'));
    }

    public function test_draft_endpoint_writes_only_draft_columns(): void
    {
        $originalBodyFr = '<p>Contenu publié original</p>';
        $originalPublishedAt = now()->subDay();

        $article = HelpArticle::factory()->create([
            'body_fr' => $originalBodyFr,
            'published_at' => $originalPublishedAt,
        ]);

        Sanctum::actingAs($this->superAdmin());

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Brouillon modifié</p>',
        ])->assertOk();

        $article->refresh();

        // Les colonnes live ne doivent PAS avoir changé.
        $this->assertSame($originalBodyFr, $article->body_fr);
        $this->assertEqualsWithDelta(
            $originalPublishedAt->timestamp,
            $article->published_at->timestamp,
            1
        );

        // La colonne brouillon doit avoir été mise à jour.
        $this->assertSame('<p>Brouillon modifié</p>', $article->draft_body_fr);
        $this->assertNotNull($article->draft_saved_at);
    }

    public function test_draft_saved_at_is_set_to_now(): void
    {
        $article = HelpArticle::factory()->create();

        Sanctum::actingAs($this->superAdmin());

        $before = now()->subSecond();

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Test</p>',
        ])->assertOk();

        $article->refresh();
        $this->assertTrue($article->draft_saved_at->isAfter($before));
    }

    public function test_member_with_edit_permission_can_save_draft(): void
    {
        $article = HelpArticle::factory()->create();

        Sanctum::actingAs($this->memberWithEditPermission());

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Brouillon membre</p>',
        ])->assertOk()->assertJson(['success' => true]);
    }

    public function test_draft_does_not_trigger_purifier_on_live_body(): void
    {
        // Vérifie que body_plain_fr n'est pas recalculé lors d'un save de brouillon.
        $article = HelpArticle::factory()->create([
            'body_fr' => '<p>Corps original</p>',
            'body_plain_fr' => 'Corps original',
        ]);

        $originalPlain = $article->body_plain_fr;

        Sanctum::actingAs($this->superAdmin());

        $this->patchJson("/api/admin/help/articles/{$article->id}/draft", [
            'draft_body_fr' => '<p>Brouillon modifié</p>',
        ])->assertOk();

        $article->refresh();

        // body_plain_fr doit rester inchangé — le hook saving n'a pas dû se déclencher
        // sur body_fr puisqu'on n'y a pas touché.
        $this->assertSame($originalPlain, $article->body_plain_fr);
    }
}
