<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use App\Models\User;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SessionRevoked implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly User $user,
        public readonly int $tokenId,
    ) {}

    public function broadcastOn(): array
    {
        return [new PrivateChannel("App.Models.User.{$this->user->id}")];
    }

    public function broadcastWith(): array
    {
        return [
            'type' => 'session_revoked',
            'token_id' => $this->tokenId,
        ];
    }

    public function broadcastAs(): string
    {
        return 'session.revoked';
    }
}
