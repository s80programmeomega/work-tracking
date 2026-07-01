<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Models\User;
use App\Models\Workspace;
use App\Permissions\ContextualPermissionGate;
use App\Permissions\Permission;
use App\Services\DocumentAccessResolver;
use App\Services\DocumentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProfileCvController extends Controller
{
    public function __construct(
        private readonly DocumentService $documentService,
        private readonly DocumentAccessResolver $accessResolver,
    ) {}

    /** Liste les CVs du profil de l'utilisateur connecté. */
    public function index(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        $documents = Document::where('documentable_type', User::class)
            ->where('documentable_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['data' => DocumentResource::collection($documents)]);
    }

    /** Upload un CV pour l'utilisateur connecté. */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'max:10240', 'mimes:pdf,doc,docx'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        /** @var User $user */
        $user = $request->user();

        $document = $this->documentService->upload(
            $request->file('file'),
            User::class,
            $user->id,
            $user,
            [
                'description' => $request->input('description'),
                'allow_duplicates' => false,
            ]
        );

        return response()->json(['data' => new DocumentResource($document)], 201);
    }

    /** Supprime un CV (propriétaire ou superadmin/directeur uniquement). */
    public function destroy(Request $request, Document $document): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();

        abort_unless(
            $this->accessResolver->canDelete($user, $document),
            403,
            'Accès non autorisé'
        );

        abort_unless(
            $document->documentable_type === User::class,
            403,
            'Ce document n\'est pas un CV'
        );

        $this->documentService->delete($document);

        return response()->json(null, 204);
    }

    /** Liste les CVs d'un autre utilisateur (directeur/manager/superadmin). */
    public function indexFor(Request $request, User $user): JsonResponse
    {
        $this->authorizeViewAccess($request, $user);

        $documents = Document::where('documentable_type', User::class)
            ->where('documentable_id', $user->id)
            ->latest()
            ->get();

        return response()->json(['data' => DocumentResource::collection($documents)]);
    }

    private function authorizeViewAccess(Request $request, User $target): void
    {
        /** @var User $viewer */
        $viewer = $request->user();

        if ($viewer->isSuperAdmin() || $viewer->id === $target->id) {
            return;
        }

        $gate = app(ContextualPermissionGate::class);
        $sharedWorkspace = Workspace::whereHas('members', fn ($q) => $q->where('users.id', $viewer->id))
            ->whereHas('members', fn ($q) => $q->where('users.id', $target->id))
            ->first();

        abort_unless(
            $sharedWorkspace && $gate->userCan($viewer, Permission::WORKSPACES_VIEW_MEMBERS, $sharedWorkspace),
            403,
            'Accès non autorisé'
        );
    }
}
