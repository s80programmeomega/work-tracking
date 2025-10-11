<?php

namespace App\Services;

use App\Models\Comment;
use App\Models\CommentMention;
use App\Models\CommentReaction;
use App\Models\CommentAttachment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Pagination\LengthAwarePaginator;

class CommentService
{
    /**
     * Get comments for a commentable entity
     */
    public function getComments(
        string $commentableType,
        int $commentableId,
        int $perPage = 15
    ): LengthAwarePaginator {
        return Comment::with(['user', 'replies.user', 'reactions.user', 'mentions.mentionedUser', 'attachments'])
            ->forCommentable($commentableType, $commentableId)
            ->rootComments()
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Create a new comment
     */
    public function createComment(array $data): Comment
    {
        return DB::transaction(function () use ($data) {
            // Parse and convert markdown to HTML if needed
            $content = $data['content'];
            $contentHtml = $this->parseMarkdown($content);

            // Create the comment
            $comment = Comment::create([
                'commentable_type' => $data['commentable_type'],
                'commentable_id' => $data['commentable_id'],
                'user_id' => $data['user_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'content' => $content,
                'content_html' => $contentHtml,
            ]);

            // Extract and create mentions
            $mentions = $this->extractMentions($content);
            if (!empty($mentions)) {
                $this->createMentions($comment->id, $mentions);
            }

            // Handle attachments
            if (!empty($data['attachments'])) {
                $this->handleAttachments($comment->id, $data['attachments']);
            }

            // Reload relationships
            $comment->load(['user', 'mentions.mentionedUser', 'attachments']);

            return $comment;
        });
    }

    /**
     * Update an existing comment
     */
    public function updateComment(Comment $comment, array $data): Comment
    {
        return DB::transaction(function () use ($comment, $data) {
            $content = $data['content'];
            $contentHtml = $this->parseMarkdown($content);

            $comment->update([
                'content' => $content,
                'content_html' => $contentHtml,
            ]);

            // Mark as edited
            $comment->markAsEdited();

            // Update mentions
            $mentions = $this->extractMentions($content);
            $this->updateMentions($comment->id, $mentions);

            // Reload relationships
            $comment->load(['user', 'mentions.mentionedUser', 'attachments']);

            return $comment;
        });
    }

    /**
     * Delete a comment
     */
    public function deleteComment(Comment $comment): bool
    {
        return DB::transaction(function () use ($comment) {
            // Soft delete will cascade to mentions, reactions, and attachments
            return $comment->delete();
        });
    }

    /**
     * Toggle a reaction on a comment
     */
    public function toggleReaction(int $commentId, int $userId, string $emoji): array
    {
        $added = CommentReaction::toggle($commentId, $userId, $emoji);

        // Get updated reaction counts
        $comment = Comment::find($commentId);
        $reactionsGrouped = $comment->reactions_grouped;

        return [
            'added' => $added,
            'reactions' => $reactionsGrouped,
        ];
    }

    /**
     * Add attachment to comment
     */
    public function addAttachment(int $commentId, $file): CommentAttachment
    {
        $fileName = $file->getClientOriginalName();
        $fileType = $file->getMimeType();
        $fileSize = $file->getSize();

        // Store file
        $path = $file->store('comment-attachments', 'public');

        return CommentAttachment::create([
            'comment_id' => $commentId,
            'nom' => $fileName,
            'type' => $fileType,
            'taille' => $fileSize,
            'chemin' => $path,
        ]);
    }

    /**
     * Delete attachment
     */
    public function deleteAttachment(CommentAttachment $attachment): bool
    {
        return $attachment->delete();
    }

    /**
     * Mark mentions as read for a user
     */
    public function markMentionsAsRead(int $userId, ?int $commentId = null): int
    {
        $query = CommentMention::forUser($userId)->unread();

        if ($commentId) {
            $query->where('comment_id', $commentId);
        }

        return $query->update(['is_read' => true]);
    }

    /**
     * Get unread mentions for a user
     */
    public function getUnreadMentions(int $userId): Collection
    {
        return CommentMention::with(['comment.user', 'comment.commentable'])
            ->forUser($userId)
            ->unread()
            ->orderBy('created_at', 'desc')
            ->get();
    }

    /**
     * Parse markdown content to HTML
     */
    protected function parseMarkdown(string $content): string
    {
        // Basic markdown parsing - can be enhanced with a library like CommonMark
        $html = $content;

        // Bold: **text** or __text__
        $html = preg_replace('/\*\*(.*?)\*\*/', '<strong>$1</strong>', $html);
        $html = preg_replace('/__(.*?)__/', '<strong>$1</strong>', $html);

        // Italic: *text* or _text_
        $html = preg_replace('/\*(.*?)\*/', '<em>$1</em>', $html);
        $html = preg_replace('/_(.*?)_/', '<em>$1</em>', $html);

        // Code: `code`
        $html = preg_replace('/`(.*?)`/', '<code>$1</code>', $html);

        // Links: [text](url)
        $html = preg_replace('/\[(.*?)\]\((.*?)\)/', '<a href="$2" target="_blank">$1</a>', $html);

        // Line breaks
        $html = nl2br($html);

        return $html;
    }

    /**
     * Extract @mentions from content
     */
    protected function extractMentions(string $content): array
    {
        preg_match_all('/@(\w+)/', $content, $matches);
        return array_unique($matches[1]);
    }

    /**
     * Create mentions for a comment
     */
    protected function createMentions(int $commentId, array $usernames): void
    {
        foreach ($usernames as $username) {
            // Find user by username (assuming User model has a 'name' field)
            $user = \App\Models\User::where('name', $username)->first();

            if ($user) {
                CommentMention::create([
                    'comment_id' => $commentId,
                    'mentioned_user_id' => $user->id,
                    'is_read' => false,
                ]);
            }
        }
    }

    /**
     * Update mentions for a comment
     */
    protected function updateMentions(int $commentId, array $usernames): void
    {
        // Delete existing mentions
        CommentMention::where('comment_id', $commentId)->delete();

        // Create new mentions
        $this->createMentions($commentId, $usernames);
    }

    /**
     * Handle multiple attachments
     */
    protected function handleAttachments(int $commentId, array $files): void
    {
        foreach ($files as $file) {
            $this->addAttachment($commentId, $file);
        }
    }
}
