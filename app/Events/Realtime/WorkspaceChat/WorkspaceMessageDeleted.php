<?php

declare(strict_types=1);

namespace App\Events\Realtime\WorkspaceChat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceMessageDeleted implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $uuid,
        public readonly int $workspaceId,
        public readonly string $channelType,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("workspace.{$this->workspaceId}.{$this->channelType}")];
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->uuid,
            'channel_type' => $this->channelType,
        ];
    }

    public function broadcastAs(): string
    {
        return 'workspace.message.deleted';
    }
}
