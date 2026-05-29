<?php

namespace App\Http\Resources;

use App\Models\Comment;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Comment $resource
 *
 * @mixin Comment
 */
class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Comment unique identifier.
     * @responseField commentable_type string Fully-qualified class name of the commentable model.
     * @responseField commentable_id integer ID of the commentable model instance.
     * @responseField user_id integer Author user ID.
     * @responseField parent_id integer|null Parent comment ID for threaded replies.
     * @responseField content string Raw comment text (markdown).
     * @responseField content_html string|null Rendered HTML content.
     * @responseField is_edited boolean Whether the comment has been edited.
     * @responseField edited_at string|null ISO 8601 datetime of last edit.
     * @responseField created_at string ISO 8601 creation datetime.
     * @responseField updated_at string ISO 8601 last-update datetime.
     * @responseField user object Author summary (id, name, email).
     * @responseField replies CommentResource[]|null Nested reply comments (when loaded).
     * @responseField reactions object[]|null Reaction list with emoji and user — when loaded.
     * @responseField reactions_grouped object Emoji to count map for grouped display.
     * @responseField mentions object[]|null Mention list with read state — when loaded.
     * @responseField attachments object[]|null Attached files — when loaded.
     * @responseField is_reply boolean Whether this comment is a reply.
     * @responseField replies_count integer|null Number of replies (when counted).
     * @responseField reactions_count integer|null Total reactions (when counted).
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'commentable_type' => $this->commentable_type,
            'commentable_id' => $this->commentable_id,
            'user_id' => $this->user_id,
            'parent_id' => $this->parent_id,
            'content' => $this->content,
            'content_html' => $this->content_html,
            'is_edited' => $this->is_edited,
            'edited_at' => $this->edited_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),
            'updated_at' => $this->updated_at->toISOString(),

            // Relationships
            'user' => [
                'id' => $this->user->id,
                'name' => $this->user->nom,
                'email' => $this->user->email,
            ],

            'replies' => CommentResource::collection($this->whenLoaded('replies')),

            'reactions' => $this->whenLoaded('reactions', function () {
                return $this->reactions->map(function ($reaction) {
                    return [
                        'id' => $reaction->id,
                        'emoji' => $reaction->emoji,
                        'user' => [
                            'id' => $reaction->user->id,
                            'name' => $reaction->user->nom,
                        ],
                        'created_at' => $reaction->created_at->toISOString(),
                    ];
                });
            }),

            'reactions_grouped' => $this->reactions_grouped,

            'mentions' => $this->whenLoaded('mentions', function () {
                return $this->mentions->map(function ($mention) {
                    return [
                        'id' => $mention->id,
                        'user' => [
                            'id' => $mention->mentionedUser->id,
                            'name' => $mention->mentionedUser->nom,
                        ],
                        'is_read' => $mention->is_read,
                    ];
                });
            }),

            'attachments' => $this->whenLoaded('attachments', function () {
                return $this->attachments->map(function ($attachment) {
                    return [
                        'id' => $attachment->id,
                        'nom' => $attachment->nom,
                        'type' => $attachment->type,
                        'taille' => $attachment->taille,
                        'human_size' => $attachment->human_size,
                        'url' => $attachment->url,
                        'is_image' => $attachment->isImage(),
                        'is_document' => $attachment->isDocument(),
                        'created_at' => $attachment->created_at->toISOString(),
                    ];
                });
            }),

            // Computed
            'is_reply' => $this->isReply(),
            'replies_count' => $this->whenCounted('replies'),
            'reactions_count' => $this->whenCounted('reactions'),
        ];
    }
}
