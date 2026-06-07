<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Help\StoreHelpArticleRequest;
use App\Http\Requests\Help\StoreHelpCategoryRequest;
use App\Http\Requests\Help\UpdateHelpArticleRequest;
use App\Http\Requests\Help\UpdateHelpCategoryRequest;
use App\Models\HelpArticle;
use App\Models\HelpArticleImage;
use App\Models\HelpCategory;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Centre d'aide — gestion (HELP_ARTICLES_MANAGE).
 *
 * Autorisation : super_admin, ou propriétaire d'au moins un workspace
 * (les directeurs deviennent owner du workspace qu'ils créent). Vérifiée
 * par authorizeManage() au début de chaque action d'écriture.
 */
class AdminHelpController extends Controller
{
    // =========================================================================
    // CATÉGORIES
    // =========================================================================

    /** GET /api/admin/help/categories — inclut les brouillons. */
    public function indexCategories(Request $request): JsonResponse
    {
        $this->authorizeManage($request->user());

        $categories = HelpCategory::withCount('articles')
            ->orderBy('position')
            ->get();

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /** POST /api/admin/help/categories */
    public function storeCategory(StoreHelpCategoryRequest $request): JsonResponse
    {
        $this->authorizeManage($request->user());

        $category = HelpCategory::create($request->validated());

        return response()->json(['success' => true, 'category' => $category], 201);
    }

    /** PUT /api/admin/help/categories/{category} */
    public function updateCategory(UpdateHelpCategoryRequest $request, HelpCategory $category): JsonResponse
    {
        $this->authorizeManage($request->user());

        $category->update($request->validated());

        return response()->json(['success' => true, 'category' => $category]);
    }

    /** DELETE /api/admin/help/categories/{category} — soft-delete. */
    public function destroyCategory(Request $request, HelpCategory $category): JsonResponse
    {
        $this->authorizeManage($request->user());

        $category->delete();

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // ARTICLES
    // =========================================================================

    /** GET /api/admin/help/articles — inclut les brouillons, filtres. */
    public function indexArticles(Request $request): JsonResponse
    {
        $this->authorizeManage($request->user());

        $query = HelpArticle::with('category:id,nom_fr,nom_en,slug');

        // Filtre statut : published | draft | all (défaut all).
        match ($request->string('status')->value()) {
            'published' => $query->published(),
            'draft' => $query->whereNull('published_at'),
            default => null,
        };

        if ($categoryId = $request->integer('category_id')) {
            $query->where('category_id', $categoryId);
        }

        if ($term = $request->string('q')->trim()->value()) {
            $query->search($term);
        } else {
            $query->orderByDesc('created_at');
        }

        $articles = $query->paginate($request->integer('per_page', 20))
            ->appends($request->only(['status', 'category_id', 'q', 'per_page']));

        return response()->json(['success' => true, 'articles' => $articles]);
    }

    /** POST /api/admin/help/articles — crée un brouillon. */
    public function storeArticle(StoreHelpArticleRequest $request): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user);

        $article = HelpArticle::create(array_merge($request->validated(), [
            'created_by' => $user->id,
            'published_at' => null,
        ]));

        return response()->json(['success' => true, 'article' => $article->load('category')], 201);
    }

    /** GET /api/admin/help/articles/{article} — détail (brouillon inclus). */
    public function showArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeManage($request->user());

        return response()->json([
            'success' => true,
            'article' => $article->load(['category', 'images']),
        ]);
    }

    /** PUT /api/admin/help/articles/{article} */
    public function updateArticle(UpdateHelpArticleRequest $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user);

        $article->update(array_merge($request->validated(), [
            'updated_by' => $user->id,
        ]));

        return response()->json(['success' => true, 'article' => $article->load('category')]);
    }

    /** POST /api/admin/help/articles/{article}/publish */
    public function publishArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeManage($request->user());

        $article->update([
            'published_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'article' => $article]);
    }

    /** POST /api/admin/help/articles/{article}/unpublish */
    public function unpublishArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeManage($request->user());

        $article->update([
            'published_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'article' => $article]);
    }

    /** DELETE /api/admin/help/articles/{article} — soft-delete. */
    public function destroyArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeManage($request->user());

        $article->delete();

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // IMAGES
    // =========================================================================

    /** POST /api/admin/help/articles/{article}/images — upload (max 5 Mo). */
    public function uploadImage(Request $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user);

        $request->validate([
            'image' => ['required', 'file', 'image', 'max:5120', 'mimes:png,jpg,jpeg,webp,gif'],
        ]);

        $file = $request->file('image');
        $path = $file->store('help/images', 'public');

        $image = HelpArticleImage::create([
            'article_id' => $article->id,
            'path' => $path,
            'mime' => $file->getClientMimeType(),
            'size_bytes' => $file->getSize(),
            'uploaded_by' => $user->id,
        ]);

        return response()->json(['success' => true, 'image' => $image], 201);
    }

    /** DELETE /api/admin/help/article-images/{image} — uploader ou super_admin. */
    public function destroyImage(Request $request, HelpArticleImage $image): JsonResponse
    {
        $user = $request->user();
        $this->authorizeManage($user);

        // Seul l'uploader d'origine ou un super_admin peut supprimer l'image.
        if (! $user->isSuperAdmin() && $image->uploaded_by !== $user->id) {
            abort(403, 'Vous ne pouvez supprimer que vos propres images.');
        }

        Storage::disk('public')->delete($image->path);
        $image->delete();

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // AUTORISATION
    // =========================================================================

    /**
     * Vérifie que l'utilisateur peut gérer le centre d'aide.
     * Autorité : super_admin, ou propriétaire d'au moins un workspace.
     */
    private function authorizeManage(User $user): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $ownsWorkspace = $user->ownedWorkspaces()->exists();
        if (! $ownsWorkspace) {
            abort(403, "Vous n'êtes pas autorisé à gérer le centre d'aide.");
        }
    }
}
