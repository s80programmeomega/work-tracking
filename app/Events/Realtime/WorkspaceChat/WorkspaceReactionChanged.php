<?php

declare(strict_types=1);

namespace App\Events\Realtime\WorkspaceChat;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class WorkspaceReactionChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly string $messageUuid,
        public readonly int $workspaceId,
        public readonly string $channelType,
        public readonly string $emoji,
        public readonly string $action, // 'added' | 'removed'
        public readonly int $userId,
        /** @var array<array{emoji: string, count: int}> */
        public readonly array $reactions,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("workspace.{$this->workspaceId}.{$this->channelType}")];
    }

    public function broadcastWith(): array
    {
        return [
            'message_uuid' => $this->messageUuid,
            'emoji' => $this->emoji,
            'action' => $this->action,
            'user_id' => $this->userId,
            'reactions' => $this->reactions,
        ];
    }

    public function broadcastAs(): string
    {
        return 'workspace.reaction.changed';
    }
}
