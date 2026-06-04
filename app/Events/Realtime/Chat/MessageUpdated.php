<?php

declare(strict_types=1);

namespace App\Events\Realtime\Chat;

use App\Models\TeamMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly TeamMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("team.{$this->message->team_id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'uuid' => $this->message->uuid,
            'content' => $this->message->content,
            'is_edited' => $this->message->is_edited,
            'edited_at' => $this->message->edited_at?->toISOString(),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.updated';
    }
}
