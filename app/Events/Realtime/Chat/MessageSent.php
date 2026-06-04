<?php

declare(strict_types=1);

namespace App\Events\Realtime\Chat;

use App\Models\TeamMessage;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly TeamMessage $message) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("team.{$this->message->team_id}")];
    }

    public function broadcastWith(): array
    {
        $this->message->loadMissing(['user', 'replyTo.user', 'reactions.user']);

        $reactions = $this->message->reactions
            ->groupBy('emoji')
            ->map(fn ($group, $emoji) => [
                'emoji' => $emoji,
                'count' => $group->count(),
            ])
            ->values()
            ->toArray();

        return [
            'message' => [
                'uuid' => $this->message->uuid,
                'team_id' => $this->message->team_id,
                'content' => $this->message->content,
                'mentions' => $this->message->mentions ?? [],
                'attachments' => $this->message->attachments ?? [],
                'is_pinned' => $this->message->is_pinned,
                'is_edited' => $this->message->is_edited,
                'edited_at' => $this->message->edited_at?->toISOString(),
                'created_at' => $this->message->created_at->toISOString(),
                'user' => [
                    'id' => $this->message->user->id,
                    'nom' => $this->message->user->nom,
                    'email' => $this->message->user->email,
                    'avatar' => $this->message->user->avatar ?? null,
                ],
                'reply_to' => $this->message->replyTo ? [
                    'uuid' => $this->message->replyTo->uuid,
                    'content_snippet' => mb_substr($this->message->replyTo->content, 0, 100),
                    'user_nom' => $this->message->replyTo->user?->nom,
                ] : null,
                'reactions' => $reactions,
            ],
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.sent';
    }
}
