<?php

declare(strict_types=1);

namespace Tests\Feature\Help;

use App\Models\HelpArticle;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Foundation\Testing\DatabaseTruncation;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

/**
 * Recherche FULLTEXT du centre d'aide.
 *
 * Utilise DatabaseTruncation (et non RefreshDatabase) : les index FULLTEXT
 * InnoDB ne voient pas les lignes insérées dans une transaction non validée.
 * RefreshDatabase enveloppe chaque test dans une transaction, ce qui rendrait
 * MATCH...AGAINST toujours vide. DatabaseTruncation valide les écritures puis
 * tronque les tables entre les tests.
 */
class HelpSearchTest extends TestCase
{
    use DatabaseTruncation;

    protected function setUp(): void
    {
        parent::setUp();
        $this->artisan('db:seed', ['--class' => 'RolePermissionSeeder']);
    }

    public function test_fulltext_search_finds_matching_published_article(): void
    {
        $category = HelpCategory::factory()->create();

        HelpArticle::factory()->create([
            'category_id' => $category->id,
            'titre_fr' => 'Comment configurer les notifications',
            'body_fr' => '<p>Guide detaille sur la configuration.</p>',
        ]);
        HelpArticle::factory()->create([
            'category_id' => $category->id,
            'titre_fr' => 'Gestion des projets',
            'body_fr' => '<p>Autre sujet sans rapport.</p>',
        ]);

        Sanctum::actingAs(User::factory()->create());

        $titres = collect($this->getJson('/api/help/articles?q=notifications')->assertOk()->json('articles.data'))
            ->pluck('titre_fr')->all();

        $this->assertContains('Comment configurer les notifications', $titres);
        $this->assertNotContains('Gestion des projets', $titres);
    }

    public function test_fulltext_search_matches_word_prefix(): void
    {
        $category = HelpCategory::factory()->create();

        HelpArticle::factory()->create([
            'category_id' => $category->id,
            'titre_fr' => 'Configuration avancee',
            'body_fr' => '<p>Parametrage.</p>',
        ]);

        Sanctum::actingAs(User::factory()->create());

        // "config" doit matcher "Configuration" grâce au suffixe * du mode booléen.
        $titres = collect($this->getJson('/api/help/articles?q=config')->assertOk()->json('articles.data'))
            ->pluck('titre_fr')->all();

        $this->assertContains('Configuration avancee', $titres);
    }
}
