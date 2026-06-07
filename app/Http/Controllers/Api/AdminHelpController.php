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
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Centre d'aide — gestion (permissions granulaires par action).
 *
 * Chaque action vérifie sa propre permission via authorize() :
 *   - help_articles.create / edit / publish / delete / upload_image
 *   - help_categories.manage
 *
 * Le contenu d'aide est global (non rattaché à un workspace). L'autorisation
 * est donc accordée si l'utilisateur est super_admin OU détient la permission
 * dans au moins un de ses workspaces (les directeurs sont owner du workspace
 * qu'ils créent, et owner reçoit toutes les permissions d'aide).
 */
class AdminHelpController extends Controller
{
    public function __construct(private ContextualPermissionGate $gate) {}

    // =========================================================================
    // CATÉGORIES
    // =========================================================================

    /** GET /api/admin/help/categories — inclut les brouillons. */
    public function indexCategories(Request $request): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_CATEGORIES_MANAGE);

        $categories = HelpCategory::withCount('articles')
            ->orderBy('position')
            ->get();

        return response()->json(['success' => true, 'categories' => $categories]);
    }

    /** POST /api/admin/help/categories */
    public function storeCategory(StoreHelpCategoryRequest $request): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_CATEGORIES_MANAGE);

        $category = HelpCategory::create($request->validated());

        return response()->json(['success' => true, 'category' => $category], 201);
    }

    /** PUT /api/admin/help/categories/{category} */
    public function updateCategory(UpdateHelpCategoryRequest $request, HelpCategory $category): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_CATEGORIES_MANAGE);

        $category->update($request->validated());

        return response()->json(['success' => true, 'category' => $category]);
    }

    /** DELETE /api/admin/help/categories/{category} — soft-delete. */
    public function destroyCategory(Request $request, HelpCategory $category): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_CATEGORIES_MANAGE);

        $category->delete();

        return response()->json(['success' => true]);
    }

    // =========================================================================
    // ARTICLES
    // =========================================================================

    /**
     * GET /api/admin/help/articles — inclut les brouillons, filtres.
     * Accessible à quiconque détient au moins une permission de gestion d'article.
     */
    public function indexArticles(Request $request): JsonResponse
    {
        $this->authorizeHelpAny($request->user(), [
            Permission::HELP_ARTICLES_CREATE,
            Permission::HELP_ARTICLES_EDIT,
            Permission::HELP_ARTICLES_PUBLISH,
            Permission::HELP_ARTICLES_DELETE,
        ]);

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
        $this->authorizeHelp($user, Permission::HELP_ARTICLES_CREATE);

        $article = HelpArticle::create(array_merge($request->validated(), [
            'created_by' => $user->id,
            'published_at' => null,
        ]));

        return response()->json(['success' => true, 'article' => $article->load('category')], 201);
    }

    /**
     * GET /api/admin/help/articles/{article} — détail (brouillon inclus).
     * Lecture côté gestion : quiconque peut créer ou éditer.
     */
    public function showArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeHelpAny($request->user(), [
            Permission::HELP_ARTICLES_CREATE,
            Permission::HELP_ARTICLES_EDIT,
        ]);

        return response()->json([
            'success' => true,
            'article' => $article->load(['category', 'images']),
        ]);
    }

    /** PUT /api/admin/help/articles/{article} */
    public function updateArticle(UpdateHelpArticleRequest $request, HelpArticle $article): JsonResponse
    {
        $user = $request->user();
        $this->authorizeHelp($user, Permission::HELP_ARTICLES_EDIT);

        $article->update(array_merge($request->validated(), [
            'updated_by' => $user->id,
        ]));

        return response()->json(['success' => true, 'article' => $article->load('category')]);
    }

    /** POST /api/admin/help/articles/{article}/publish */
    public function publishArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_ARTICLES_PUBLISH);

        $article->update([
            'published_at' => now(),
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'article' => $article]);
    }

    /** POST /api/admin/help/articles/{article}/unpublish */
    public function unpublishArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_ARTICLES_PUBLISH);

        $article->update([
            'published_at' => null,
            'updated_by' => $request->user()->id,
        ]);

        return response()->json(['success' => true, 'article' => $article]);
    }

    /** DELETE /api/admin/help/articles/{article} — soft-delete. */
    public function destroyArticle(Request $request, HelpArticle $article): JsonResponse
    {
        $this->authorizeHelp($request->user(), Permission::HELP_ARTICLES_DELETE);

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
        $this->authorizeHelp($user, Permission::HELP_ARTICLES_UPLOAD_IMAGE);

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
        $this->authorizeHelp($user, Permission::HELP_ARTICLES_UPLOAD_IMAGE);

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
     * Vérifie que l'utilisateur détient la permission d'aide donnée.
     * Le contenu d'aide étant global, on accorde si super_admin OU si la
     * permission est détenue dans au moins un workspace de l'utilisateur.
     */
    private function authorizeHelp(User $user, string $permission): void
    {
        if (! $this->userHasHelpPermission($user, $permission)) {
            abort(403, "Vous n'êtes pas autorisé à effectuer cette action sur le centre d'aide.");
        }
    }

    /**
     * Variante : accorde si l'utilisateur détient AU MOINS UNE des permissions.
     *
     * @param  list<string>  $permissions
     */
    private function authorizeHelpAny(User $user, array $permissions): void
    {
        foreach ($permissions as $permission) {
            if ($this->userHasHelpPermission($user, $permission)) {
                return;
            }
        }

        abort(403, "Vous n'êtes pas autorisé à effectuer cette action sur le centre d'aide.");
    }

    /**
     * super_admin → toujours ; sinon vrai si la permission est accordée dans
     * l'un des workspaces possédés ou rejoints par l'utilisateur.
     */
    private function userHasHelpPermission(User $user, string $permission): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Workspaces possédés (owner) + workspaces rejoints (membre).
        $workspaces = Workspace::query()
            ->where('owner_id', $user->id)
            ->orWhereHas('members', fn ($q) => $q->where('user_id', $user->id))
            ->get();

        foreach ($workspaces as $workspace) {
            if ($this->gate->userCan($user, $permission, $workspace)) {
                return true;
            }
        }

        return false;
    }
}
