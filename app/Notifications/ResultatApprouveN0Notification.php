<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class ResultatApprouveN0Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TacheResultat $resultat,
        public readonly User $n0Actor,
    ) {}

    public function via(object $notifiable): array
    {
        // channelsFor('approuve_n0') returns ['database', 'broadcast'] — in-app only by design.
        return app(NotificationService::class)->channelsFor($notifiable, 'approuve_n0');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resultat_approuve_n0',
            'dedup_key' => app(NotificationService::class)->dedupKey('approuve_n0', $this->resultat->id),
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
            'n0_actor_id' => $this->n0Actor->id,
            'n0_actor_nom' => $this->n0Actor->nom,
        ];
    }
}
