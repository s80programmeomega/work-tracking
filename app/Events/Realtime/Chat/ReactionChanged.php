<?php

declare(strict_types=1);

namespace App\Events\Realtime\Chat;

use App\Models\TeamMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ReactionChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly TeamMessage $message,
        public readonly string $emoji,
        public readonly string $action,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("team.{$this->message->team_id}")];
    }

    public function broadcastWith(): array
    {
        $count = $this->message->reactions()->where('emoji', $this->emoji)->count();

        return [
            'message_uuid' => $this->message->uuid,
            'emoji' => $this->emoji,
            'count' => $count,
            'action' => $this->action,
        ];
    }

    public function broadcastAs(): string
    {
        return 'reaction.changed';
    }
}
