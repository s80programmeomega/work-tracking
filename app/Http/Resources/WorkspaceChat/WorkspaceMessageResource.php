<?php

namespace App\Http\Resources\WorkspaceChat;

use App\Http\Resources\UserResource;
use App\Models\WorkspaceMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceMessage
 */
class WorkspaceMessageResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WorkspaceMessage $message */
        $message = $this->resource;

        $currentUserId = $request->user()?->id;

        $reactions = $message->relationLoaded('reactions')
            ? $message->reactions
                ->groupBy('emoji')
                ->map(fn ($group, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'did_react' => $group->contains('user_id', $currentUserId),
                ])
                ->values()
                ->toArray()
            : [];

        return [
            'uuid' => $message->uuid,
            'workspace_id' => $message->workspace_id,
            'workspace_channel_id' => $message->workspace_channel_id,
            'channel_type' => $message->channel?->type,
            'content' => $message->content,
            'mentions' => $message->mentions ?? [],
            'attachments' => $message->attachments ?? [],
            'is_pinned' => $message->is_pinned,
            'is_edited' => $message->is_edited,
            'edited_at' => $message->edited_at?->toISOString(),
            'created_at' => $message->created_at?->toISOString(),
            'user' => $message->relationLoaded('user')
                ? new UserResource($message->user)
                : null,
            'reply_to' => $message->relationLoaded('replyTo') && $message->replyTo
                ? [
                    'uuid' => $message->replyTo->uuid,
                    'content_snippet' => mb_substr($message->replyTo->content ?? '', 0, 100),
                    'user_nom' => $message->replyTo->user?->nom,
                ]
                : null,
            'reactions' => $reactions,
        ];
    }
}
