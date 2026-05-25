<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class PendingValidationCountChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  int[]  $notifyUserIds  User IDs whose pending count may have changed
     */
    public function __construct(
        public readonly int $workspaceId,
        public readonly array $notifyUserIds = []
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("workspace.{$this->workspaceId}")];
    }

    public function broadcastWith(): array
    {
        return [
            'workspace_id' => $this->workspaceId,
            'notify_user_ids' => $this->notifyUserIds,
        ];
    }

    public function broadcastAs(): string
    {
        return 'pending-validation.count.changed';
    }
}
