<?php

namespace Tests\Browser\Help;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Dusk\Browser;
use Tests\Browser\WorkTrackingTestCase;

/**
 * Tests Dusk pour le centre d'aide (Phase 7).
 *
 * Couvre :
 *  - Lecteur : la page /help se charge avec la grille de catégories + recherche
 *  - Lecteur : ouvrir un article affiche son contenu + le sélecteur FR|EN
 *  - Auteur : la page d'administration se charge et l'onglet Catégories s'affiche
 */
class HelpCenterTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    /** Crée une catégorie + un article publiés pour peupler le lecteur. */
    private function seedPublishedContent(): HelpArticle
    {
        $category = HelpCategory::factory()->create(['nom_fr' => 'CategorieDusk', 'nom_en' => 'DuskCategory']);

        return HelpArticle::factory()->create([
            'category_id' => $category->id,
            'titre_fr' => 'ArticleDuskFr',
            'titre_en' => 'ArticleDuskEn',
            'body_fr' => '<p>Contenu francais Dusk.</p>',
            'body_en' => '<p>English content Dusk.</p>',
        ]);
    }

    /**
     * Crée un utilisateur rattaché à un workspace (sinon le garde de navigation
     * SPA redirige tout utilisateur sans current_workspace_id vers /workspaces/create).
     */
    private function memberUser(): User
    {
        $owner = User::factory()->create();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);

        return User::factory()->create(['current_workspace_id' => $workspace->id]);
    }

    public function test_reader_sees_help_index_with_categories(): void
    {
        $this->seedPublishedContent();
        $user = $this->memberUser();

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/help')
                ->waitFor('[dusk="help-index-title"]', 5)
                ->assertVisible('[dusk="help-search-input"]')
                // La grille de catégories est chargée en async — on attend son texte.
                ->waitForText('CategorieDusk', 10);
        });
    }

    public function test_reader_opens_article_and_can_toggle_language(): void
    {
        $article = $this->seedPublishedContent();
        $user = $this->memberUser();

        $this->browse(function (Browser $browser) use ($user, $article) {
            $this->signInAs($browser, $user)
                ->visit('/help/a/'.$article->slug)
                ->waitForText('ArticleDuskFr', 5)
                ->assertSee('Contenu francais Dusk')
                // Bascule la langue d'affichage de l'article vers l'anglais.
                ->click('[dusk="help-article-lang-en"]')
                ->waitForText('ArticleDuskEn', 5)
                ->assertSee('English content Dusk');
        });
    }

    public function test_author_sees_admin_page_and_categories_tab(): void
    {
        $this->seedPublishedContent();

        // Super-admin : signInAs() dérive le flag is_super_admin du token de
        // hasRole('super_admin') (basé sur la colonne role), et le SPA n'affiche
        // l'onglet Catégories que si canManageHelpCategories (court-circuité par
        // is_super_admin). On force donc la colonne role + le flag + un workspace.
        $owner = User::factory()->create(['is_super_admin' => true]);
        // La colonne role n'est pas dans $fillable → forceFill pour la persister.
        $owner->forceFill(['role' => 'super_admin'])->save();
        $workspace = Workspace::factory()->create(['owner_id' => $owner->id]);
        $owner->update(['current_workspace_id' => $workspace->id]);
        $owner->assignRole('super_admin');

        $this->browse(function (Browser $browser) use ($owner) {
            $this->signInAs($browser, $owner)
                ->visit('/admin/help-articles')
                ->waitFor('[dusk="help-admin-title"]', 5)
                ->assertVisible('[dusk="help-admin-tab-articles"]')
                ->click('[dusk="help-admin-tab-categories"]')
                // La liste des catégories est chargée en async après le clic d'onglet.
                ->waitForText('CategorieDusk', 10);
        });
    }
}
