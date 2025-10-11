<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\CommentAttachment;
use App\Services\CommentService;
use App\Http\Resources\CommentResource;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

class CommentController extends Controller
{
    protected CommentService $commentService;

    public function __construct(CommentService $commentService)
    {
        $this->commentService = $commentService;
    }

    /**
     * Display a listing of comments for a commentable entity
     */
    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $comments = $this->commentService->getComments(
            $request->commentable_type,
            $request->commentable_id,
            $request->get('per_page', 15)
        );

        return response()->json([
            'success' => true,
            'data' => CommentResource::collection($comments),
            'meta' => [
                'current_page' => $comments->currentPage(),
                'last_page' => $comments->lastPage(),
                'per_page' => $comments->perPage(),
                'total' => $comments->total(),
            ],
        ]);
    }

    /**
     * Store a newly created comment
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'commentable_type' => 'required|string',
            'commentable_id' => 'required|integer',
            'parent_id' => 'nullable|exists:comments,id',
            'content' => 'required|string|min:1|max:10000',
            'attachments.*' => 'sometimes|file|max:10240', // 10MB max per file
        ]);

        $validated['user_id'] = $request->user()->id;
        $validated['attachments'] = $request->file('attachments', []);

        $comment = $this->commentService->createComment($validated);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire créé avec succès',
            'data' => new CommentResource($comment),
        ], 201);
    }

    /**
     * Display the specified comment
     */
    public function show(Comment $comment): JsonResponse
    {
        $comment->load(['user', 'replies.user', 'reactions.user', 'mentions.mentionedUser', 'attachments']);

        return response()->json([
            'success' => true,
            'data' => new CommentResource($comment),
        ]);
    }

    /**
     * Update the specified comment
     */
    public function update(Request $request, Comment $comment): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à modifier ce commentaire',
            ], 403);
        }

        $validated = $request->validate([
            'content' => 'required|string|min:1|max:10000',
        ]);

        $updatedComment = $this->commentService->updateComment($comment, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire modifié avec succès',
            'data' => new CommentResource($updatedComment),
        ]);
    }

    /**
     * Remove the specified comment
     */
    public function destroy(Request $request, Comment $comment): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à supprimer ce commentaire',
            ], 403);
        }

        $this->commentService->deleteComment($comment);

        return response()->json([
            'success' => true,
            'message' => 'Commentaire supprimé avec succès',
        ]);
    }

    /**
     * Toggle a reaction on a comment
     */
    public function toggleReaction(Request $request, Comment $comment): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => 'required|string|max:10',
        ]);

        $result = $this->commentService->toggleReaction(
            $comment->id,
            $request->user()->id,
            $validated['emoji']
        );

        return response()->json([
            'success' => true,
            'message' => $result['added'] ? 'Réaction ajoutée' : 'Réaction supprimée',
            'data' => [
                'added' => $result['added'],
                'reactions' => $result['reactions'],
            ],
        ]);
    }

    /**
     * Add attachment to comment
     */
    public function addAttachment(Request $request, Comment $comment): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à ajouter des pièces jointes à ce commentaire',
            ], 403);
        }

        $request->validate([
            'file' => 'required|file|max:10240', // 10MB max
        ]);

        $attachment = $this->commentService->addAttachment($comment->id, $request->file('file'));

        return response()->json([
            'success' => true,
            'message' => 'Pièce jointe ajoutée avec succès',
            'data' => [
                'id' => $attachment->id,
                'nom' => $attachment->nom,
                'type' => $attachment->type,
                'taille' => $attachment->taille,
                'human_size' => $attachment->human_size,
                'url' => $attachment->url,
            ],
        ], 201);
    }

    /**
     * Delete attachment from comment
     */
    public function deleteAttachment(Request $request, Comment $comment, CommentAttachment $attachment): JsonResponse
    {
        // Check authorization
        if ($request->user()->id !== $comment->user_id) {
            return response()->json([
                'success' => false,
                'message' => 'Non autorisé à supprimer cette pièce jointe',
            ], 403);
        }

        // Verify attachment belongs to comment
        if ($attachment->comment_id !== $comment->id) {
            return response()->json([
                'success' => false,
                'message' => 'Pièce jointe introuvable pour ce commentaire',
            ], 404);
        }

        $this->commentService->deleteAttachment($attachment);

        return response()->json([
            'success' => true,
            'message' => 'Pièce jointe supprimée avec succès',
        ]);
    }

    /**
     * Get unread mentions for authenticated user
     */
    public function unreadMentions(Request $request): JsonResponse
    {
        $mentions = $this->commentService->getUnreadMentions($request->user()->id);

        return response()->json([
            'success' => true,
            'data' => $mentions->map(function ($mention) {
                return [
                    'id' => $mention->id,
                    'comment' => new CommentResource($mention->comment),
                    'created_at' => $mention->created_at->toISOString(),
                ];
            }),
        ]);
    }

    /**
     * Mark mentions as read
     */
    public function markMentionsAsRead(Request $request): JsonResponse
    {
        $request->validate([
            'comment_id' => 'nullable|exists:comments,id',
        ]);

        $count = $this->commentService->markMentionsAsRead(
            $request->user()->id,
            $request->get('comment_id')
        );

        return response()->json([
            'success' => true,
            'message' => "{$count} mention(s) marquée(s) comme lue(s)",
            'data' => ['count' => $count],
        ]);
    }
}
