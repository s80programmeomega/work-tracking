<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CommentResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
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
                'name' => $this->user->name,
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
                            'name' => $reaction->user->name,
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
                            'name' => $mention->mentionedUser->name,
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
