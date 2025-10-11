<?php

namespace App\Http\Controllers;

use App\Models\Document;
use App\Services\DocumentService;
use App\Http\Resources\DocumentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DocumentController extends Controller
{
    protected DocumentService $documentService;

    public function __construct(DocumentService $documentService)
    {
        $this->documentService = $documentService;
    }

    /**
     * Get documents for an entity
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'with_versions' => 'boolean',
        ]);

        $documents = $this->documentService->getForEntity(
            $request->documentable_type,
            $request->documentable_id,
            [
                'with_versions' => $request->boolean('with_versions', false),
            ]
        );

        return response()->json([
            'success' => true,
            'data' => DocumentResource::collection($documents),
        ]);
    }

    /**
     * Search documents
     */
    public function search(Request $request): JsonResponse
    {
        $request->validate([
            'query' => 'required|string|min:2',
            'type' => 'nullable|string',
            'user_id' => 'nullable|integer',
            'documentable_type' => 'nullable|string',
            'documentable_id' => 'nullable|integer',
            'mime_type' => 'nullable|string',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        $results = $this->documentService->search(
            $request->query,
            $request->only(['type', 'user_id', 'documentable_type', 'documentable_id', 'mime_type', 'per_page'])
        );

        return response()->json([
            'success' => true,
            'data' => DocumentResource::collection($results->items()),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page' => $results->lastPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
            ],
        ]);
    }

    /**
     * Upload document(s)
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'documentable_type' => 'required|string',
            'documentable_id' => 'required|integer',
            'files' => 'required|array',
            'files.*' => 'required|file|max:' . config('documents.max_file_size', 10240),
            'description' => 'nullable|string',
            'visibility' => 'nullable|in:private,team,public',
            'disk' => 'nullable|string',
            'allow_duplicates' => 'nullable|boolean',
        ]);

        $user = $request->user();
        $files = $request->file('files');

        $options = [
            'description' => $request->description,
            'visibility' => $request->visibility ?? 'team',
            'disk' => $request->disk ?? config('documents.default_disk', 'local'),
            'allow_duplicates' => $request->boolean('allow_duplicates', false),
        ];

        if (count($files) === 1) {
            $document = $this->documentService->upload(
                $files[0],
                $request->documentable_type,
                $request->documentable_id,
                $user,
                $options
            );

            return response()->json([
                'success' => true,
                'message' => 'Document téléchargé avec succès',
                'data' => new DocumentResource($document),
            ], 201);
        }

        $documents = $this->documentService->uploadMultiple(
            $files,
            $request->documentable_type,
            $request->documentable_id,
            $user,
            $options
        );

        return response()->json([
            'success' => true,
            'message' => count($documents) . ' documents téléchargés avec succès',
            'data' => DocumentResource::collection($documents),
        ], 201);
    }

    /**
     * Get document details
     */
    public function show(Document $document): JsonResponse
    {
        $user = auth()->user();

        if (!$document->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir ce document',
            ], 403);
        }

        $document->load(['user:id,name', 'versions', 'permissions']);

        return response()->json([
            'success' => true,
            'data' => new DocumentResource($document),
        ]);
    }

    /**
     * Update document metadata
     */
    public function update(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        if (!$document->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de modifier ce document',
            ], 403);
        }

        $request->validate([
            'nom' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'visibility' => 'nullable|in:private,team,public',
        ]);

        $document->update($request->only(['nom', 'description', 'visibility']));

        return response()->json([
            'success' => true,
            'message' => 'Document mis à jour avec succès',
            'data' => new DocumentResource($document),
        ]);
    }

    /**
     * Delete document
     */
    public function destroy(Document $document): JsonResponse
    {
        $user = auth()->user();

        if (!$document->canBeDeletedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de supprimer ce document',
            ], 403);
        }

        $this->documentService->delete($document);

        return response()->json([
            'success' => true,
            'message' => 'Document supprimé avec succès',
        ]);
    }

    /**
     * Download document
     */
    public function download(Document $document): StreamedResponse
    {
        $user = auth()->user();

        if (!$document->canBeDownloadedBy($user)) {
            abort(403, 'Vous n\'avez pas la permission de télécharger ce document');
        }

        // Record download
        $this->documentService->recordDownload($document, $user);

        // Increment download count
        $document->incrementDownloadCount();

        // Stream the file
        return Storage::disk($document->disk)->download(
            $document->chemin,
            $document->nom
        );
    }

    /**
     * Create new version of document
     */
    public function createVersion(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        if (!$document->canBeEditedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de créer une nouvelle version',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:' . config('documents.max_file_size', 10240),
        ]);

        $newVersion = $this->documentService->createVersion(
            $document,
            $request->file('file'),
            $user
        );

        return response()->json([
            'success' => true,
            'message' => 'Nouvelle version créée avec succès',
            'data' => new DocumentResource($newVersion),
        ], 201);
    }

    /**
     * Get document download statistics
     */
    public function stats(Document $document): JsonResponse
    {
        $user = auth()->user();

        if (!$document->canBeViewedBy($user)) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir les statistiques',
            ], 403);
        }

        $stats = $this->documentService->getDownloadStats($document);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }

    /**
     * Grant permission to user
     */
    public function grantPermission(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        // Only owner can grant permissions
        if ($document->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut accorder des permissions',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
            'can_view' => 'nullable|boolean',
            'can_download' => 'nullable|boolean',
            'can_edit' => 'nullable|boolean',
            'can_delete' => 'nullable|boolean',
            'can_share' => 'nullable|boolean',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);

        $permission = $this->documentService->grantPermission(
            $document,
            $targetUser,
            $request->only(['can_view', 'can_download', 'can_edit', 'can_delete', 'can_share']),
            $request->expires_at ? new \DateTime($request->expires_at) : null
        );

        return response()->json([
            'success' => true,
            'message' => 'Permission accordée avec succès',
            'data' => $permission,
        ]);
    }

    /**
     * Revoke permission from user
     */
    public function revokePermission(Request $request, Document $document): JsonResponse
    {
        $user = $request->user();

        // Only owner can revoke permissions
        if ($document->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Seul le propriétaire peut révoquer des permissions',
            ], 403);
        }

        $request->validate([
            'user_id' => 'required|exists:users,id',
        ]);

        $targetUser = \App\Models\User::findOrFail($request->user_id);

        $this->documentService->revokePermission($document, $targetUser);

        return response()->json([
            'success' => true,
            'message' => 'Permission révoquée avec succès',
        ]);
    }
}
