<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use App\Models\Tache;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TacheStatutChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly Tache $tache) {}

    public function broadcastOn(): array
    {
        $this->tache->loadMissing('activite.projet');
        $workspaceId = $this->tache->activite?->projet?->workspace_id;

        return $workspaceId
            ? [new PrivateChannel("workspace.{$workspaceId}")]
            : [];
    }

    public function broadcastWith(): array
    {
        $statut = $this->tache->statut;

        return [
            'tache_id' => $this->tache->id,
            'statut' => $statut instanceof \BackedEnum ? $statut->value : $statut,
            'taux_realisation' => $this->tache->taux_realisation,
        ];
    }

    public function broadcastAs(): string
    {
        return 'tache.statut.changed';
    }
}
