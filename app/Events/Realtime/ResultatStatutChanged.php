<?php

declare(strict_types=1);

namespace App\Events\Realtime;

use App\Models\TacheResultat;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ResultatStatutChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public readonly TacheResultat $resultat) {}

    public function broadcastOn(): array
    {
        $this->resultat->loadMissing('tache.activite.projet');
        $workspaceId = $this->resultat->tache?->activite?->projet?->workspace_id;

        return $workspaceId
            ? [new PrivateChannel("workspace.{$workspaceId}")]
            : [];
    }

    public function broadcastWith(): array
    {
        return [
            'resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'user_id' => $this->resultat->user_id,
            'statut' => $this->resultat->statut instanceof \BackedEnum
                ? $this->resultat->statut->value
                : $this->resultat->statut,
        ];
    }

    public function broadcastAs(): string
    {
        return 'resultat.statut.changed';
    }
}
