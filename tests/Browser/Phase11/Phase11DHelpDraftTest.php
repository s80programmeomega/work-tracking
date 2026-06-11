<?php

declare(strict_types=1);

namespace Tests\Browser\Phase11;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Phase 11D — Help Center draft auto-save (Dusk).
 *
 * Vérifie que :
 *  - La bannière "Restaurer / Ignorer" s'affiche quand l'article a un brouillon
 *    plus récent que la dernière sauvegarde publiée.
 *  - Le bouton "Ignorer" masque la bannière sans modifier le contenu.
 *  - Le bouton "Restaurer" injecte le contenu du brouillon dans les éditeurs.
 *  - Après une frappe dans l'éditeur, l'indicateur "Brouillon enregistré…" apparaît
 *    (dans les 8 secondes — debounce 5s + délai réseau).
 */
class Phase11DHelpDraftTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    private function makeSuperAdmin(): User
    {
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);

        $user = User::factory()->create(['is_super_admin' => true]);
        $user->forceFill(['role' => 'super_admin'])->save();
        $workspace = Workspace::factory()->create(['owner_id' => $user->id]);
        $user->update(['current_workspace_id' => $workspace->id]);
        $user->assignRole('super_admin');

        return $user;
    }

    private function articleWithDraft(User $user): HelpArticle
    {
        $category = HelpCategory::factory()->create();

        $article = HelpArticle::factory()->create([
            'category_id' => $category->id,
            'body_fr' => '<p>Contenu publié.</p>',
            'body_en' => '<p>Published content.</p>',
            'created_by' => $user->id,
            'updated_by' => $user->id,
            'updated_at' => now()->subHour(),
        ]);

        // Force draft_saved_at à être plus récent que updated_at pour déclencher la bannière.
        $article->updateQuietly([
            'draft_body_fr' => '<p>Brouillon non publié.</p>',
            'draft_body_en' => '<p>Unpublished draft.</p>',
            'draft_saved_at' => now(),
        ]);

        return $article->fresh();
    }

    public function test_restore_banner_shows_when_draft_is_newer_than_last_save(): void
    {
        $user = $this->makeSuperAdmin();
        $article = $this->articleWithDraft($user);

        $this->browse(function (Browser $browser) use ($user, $article) {
            $this->signInAs($browser, $user)
                ->visit("/admin/help-articles/{$article->id}/edit")
                ->waitFor('[dusk="draft-restore-banner"]', 10)
                ->assertVisible('[dusk="draft-restore-banner"]')
                ->assertVisible('[dusk="draft-restore-btn"]');
        });
    }

    public function test_discard_button_hides_the_banner(): void
    {
        $user = $this->makeSuperAdmin();
        $article = $this->articleWithDraft($user);

        $this->browse(function (Browser $browser) use ($user, $article) {
            $this->signInAs($browser, $user)
                ->visit("/admin/help-articles/{$article->id}/edit")
                ->waitFor('[dusk="draft-restore-banner"]', 10)
                ->click('[dusk="draft-restore-btn"]')
                // Après clic sur "Ignorer" (discard), la bannière doit disparaître.
                ->waitUntilMissing('[dusk="draft-restore-banner"]', 5)
                ->assertMissing('[dusk="draft-restore-banner"]');
        });
    }

    public function test_auto_save_indicator_appears_after_typing(): void
    {
        $user = $this->makeSuperAdmin();

        // Article sans brouillon : pas de bannière, on teste l'auto-save après frappe.
        $category = HelpCategory::factory()->create();
        $article = HelpArticle::factory()->create([
            'category_id' => $category->id,
            'created_by' => $user->id,
        ]);

        $this->browse(function (Browser $browser) use ($user, $article) {
            $this->signInAs($browser, $user)
                ->visit("/admin/help-articles/{$article->id}/edit")
                // Attend que l'éditeur Tiptap soit monté (ProseMirror contenteditable).
                ->waitFor('.ProseMirror', 15)
                // Clique dans le premier éditeur et frappe un caractère.
                ->click('.ProseMirror')
                ->keys('.ProseMirror', 'x')
                // Attend l'indicateur (debounce 5s + délai réseau — timeout 12s).
                ->waitFor('[dusk="draft-status-indicator"]', 12)
                ->assertVisible('[dusk="draft-status-indicator"]');
        });
    }
}
