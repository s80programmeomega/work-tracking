<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\HelpArticle;
use App\Models\HelpCategory;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Centre d'aide — endpoints de lecture (tout utilisateur authentifié).
 *
 * Ne renvoie QUE le contenu publié. La gestion (brouillons, création,
 * édition) passe par AdminHelpController, gardé par HELP_ARTICLES_MANAGE.
 */
class HelpController extends Controller
{
    /**
     * Liste des catégories publiées, avec le nombre d'articles publiés.
     *
     * GET /api/help/categories
     */
    public function categories(): JsonResponse
    {
        $categories = HelpCategory::published()
            ->withCount(['articles' => fn ($q) => $q->published()])
            ->orderBy('position')
            ->get();

        return response()->json([
            'success' => true,
            'categories' => $categories,
        ]);
    }

    /**
     * Détail d'une catégorie publiée + ses articles publiés (paginés).
     *
     * GET /api/help/categories/{slug}
     */
    public function category(Request $request, string $slug): JsonResponse
    {
        $category = HelpCategory::published()->where('slug', $slug)->firstOrFail();

        $articles = $category->articles()
            ->published()
            ->orderByDesc('published_at')
            ->paginate($request->integer('per_page', 15));

        return response()->json([
            'success' => true,
            'category' => $category,
            'articles' => $articles,
        ]);
    }

    /**
     * Recherche / liste d'articles publiés (FULLTEXT si terme fourni).
     *
     * GET /api/help/articles?q=&category=&page=&per_page=
     */
    public function articles(Request $request): JsonResponse
    {
        $request->validate([
            'q' => 'sometimes|nullable|string|max:255',
            'category' => 'sometimes|nullable|string|exists:help_categories,slug',
            'per_page' => 'sometimes|integer|min:1|max:50',
        ]);

        $query = HelpArticle::published()->with('category');

        if ($categorySlug = $request->string('category')->trim()->value()) {
            $query->whereHas('category', fn ($q) => $q->where('slug', $categorySlug));
        }

        $term = $request->string('q')->trim()->value();
        if ($term !== '') {
            $query->search($term);
        } else {
            $query->orderByDesc('published_at');
        }

        $articles = $query->paginate($request->integer('per_page', 15))
            ->appends($request->only(['q', 'category', 'per_page']));

        return response()->json([
            'success' => true,
            'articles' => $articles,
        ]);
    }

    /**
     * Détail d'un article publié + incrément du compteur de vues.
     *
     * GET /api/help/articles/{slug}
     */
    public function article(string $slug): JsonResponse
    {
        $article = HelpArticle::published()
            ->with('category')
            ->where('slug', $slug)
            ->firstOrFail();

        // Incrément atomique du compteur de vues (sans toucher updated_at).
        HelpArticle::whereKey($article->id)->increment('views_count');
        $article->views_count++;

        // Articles liés : même catégorie, publiés, hors article courant.
        $related = HelpArticle::published()
            ->where('category_id', $article->category_id)
            ->whereKeyNot($article->id)
            ->orderByDesc('views_count')
            ->limit(5)
            ->get(['id', 'slug', 'titre_fr', 'titre_en', 'category_id']);

        return response()->json([
            'success' => true,
            'article' => $article,
            'related' => $related,
        ]);
    }
}
