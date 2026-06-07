<?php

declare(strict_types=1);

namespace Tests\Feature\Search;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Inclusion du contenu d'aide dans la recherche globale (GET /api/search).
 *
 * Driver Scout : collection (phpunit.xml) — l'index ne contient que les modèles
 * dont shouldBeSearchable() est vrai (articles/catégories publiés uniquement).
 *
 * Vérifie surtout la garantie de confidentialité : les brouillons ne doivent
 * JAMAIS apparaître dans les résultats de recherche.
 */
class HelpSearchInclusionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_published_help_article_appears_in_global_search(): void
    {
        $category = HelpCategory::factory()->create();
        HelpArticle::factory()->create([
            'category_id' => $category->id,
            'titre_fr' => 'GuideRechercheUnique',
            'body_fr' => '<p>Contenu cherchable.</p>',
        ]);

        // Super-admin : passe la garde de tier de recherche (le contenu d'aide
        // est global et visible de tous ; on teste ici la portée publié/brouillon).
        Sanctum::actingAs(User::factory()->create(['is_super_admin' => true]));

        $response = $this->getJson('/api/search?q=GuideRechercheUnique&types[]=help_articles')
            ->assertStatus(200);

        $labels = collect($response->json('results.help_articles'))->pluck('label')->all();
        $this->assertContains('GuideRechercheUnique', $labels);
    }

    public function test_published_help_category_appears_in_global_search(): void
    {
        HelpCategory::factory()->create(['nom_fr' => 'CategorieRechercheUnique']);

        // Super-admin : passe la garde de tier de recherche (le contenu d'aide
        // est global et visible de tous ; on teste ici la portée publié/brouillon).
        Sanctum::actingAs(User::factory()->create(['is_super_admin' => true]));

        $response = $this->getJson('/api/search?q=CategorieRechercheUnique&types[]=help_categories')
            ->assertStatus(200);

        $labels = collect($response->json('results.help_categories'))->pluck('label')->all();
        $this->assertContains('CategorieRechercheUnique', $labels);
    }

    public function test_draft_help_article_never_appears_in_search(): void
    {
        $category = HelpCategory::factory()->create();
        HelpArticle::factory()->unpublished()->create([
            'category_id' => $category->id,
            'titre_fr' => 'BrouillonSecretRecherche',
            'body_fr' => '<p>Ne doit pas fuiter.</p>',
        ]);

        // Super-admin : passe la garde de tier de recherche (le contenu d'aide
        // est global et visible de tous ; on teste ici la portée publié/brouillon).
        Sanctum::actingAs(User::factory()->create(['is_super_admin' => true]));

        $response = $this->getJson('/api/search?q=BrouillonSecretRecherche&types[]=help_articles')
            ->assertStatus(200);

        $labels = collect($response->json('results.help_articles'))->pluck('label')->all();
        $this->assertNotContains('BrouillonSecretRecherche', $labels);
    }

    public function test_help_types_are_accepted_in_search_validation(): void
    {
        Sanctum::actingAs(User::factory()->create(['is_super_admin' => true]));

        $this->getJson('/api/search?q=test&types[]=help_articles&types[]=help_categories&types[]=help_images')
            ->assertStatus(200)
            ->assertJsonStructure(['results' => ['help_articles', 'help_categories', 'help_images']]);
    }
}
