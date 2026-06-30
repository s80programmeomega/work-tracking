<?php

declare(strict_types=1);

namespace App\Events\Realtime\WorkspaceChat;

use App\Models\WorkspaceMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceMessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly WorkspaceMessage $message) {}

    public function broadcastOn(): array
    {
        $channelType = $this->message->channel?->type ?? 'global';

        return [new PrivateChannel("workspace.{$this->message->workspace_id}.{$channelType}")];
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->message->uuid,
            'content' => $this->message->content,
            'is_edited' => $this->message->is_edited,
            'edited_at' => $this->message->edited_at?->toISOString(),
            'channel_type' => $this->message->channel?->type ?? 'global',
        ];
    }

    public function broadcastAs(): string
    {
        return 'workspace.message.updated';
    }
}
