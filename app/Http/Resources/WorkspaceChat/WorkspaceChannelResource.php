<?php

namespace App\Http\Resources\WorkspaceChat;

use App\Models\WorkspaceChannel;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin WorkspaceChannel
 */
class WorkspaceChannelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        /** @var WorkspaceChannel $channel */
        $channel = $this->resource;

        return [
            'id' => $channel->id,
            'workspace_id' => $channel->workspace_id,
            'type' => $channel->type,
            'name' => $channel->name,
            'messages_count' => $channel->messages_count ?? null,
            'created_at' => $channel->created_at,
        ];
    }
}
