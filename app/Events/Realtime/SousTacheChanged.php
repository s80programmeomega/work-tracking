<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use App\Models\SousTache;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SousTacheChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly SousTache $sousTache,
        public readonly string $action = 'updated'
    ) {}

    public function broadcastOn(): array
    {
        $this->sousTache->loadMissing('tache.activite.projet');
        $workspaceId = $this->sousTache->tache?->activite?->projet?->workspace_id;

        return $workspaceId
            ? [new PrivateChannel("workspace.{$workspaceId}")]
            : [];
    }

    public function broadcastWith(): array
    {
        return [
            'sous_tache_id' => $this->sousTache->id,
            'tache_id' => $this->sousTache->tache_id,
            'action' => $this->action,
            'statut' => $this->sousTache->statut instanceof \BackedEnum
                ? $this->sousTache->statut->value
                : $this->sousTache->statut,
        ];
    }

    public function broadcastAs(): string
    {
        return 'sous-tache.changed';
    }
}
