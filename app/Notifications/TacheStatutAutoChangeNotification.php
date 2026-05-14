<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class TacheStatutAutoChangeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tache $tache,
        public readonly string $oldStatut,
        public readonly string $newStatut,
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tache_statut_auto_changed',
            'tache_id' => $this->tache->id,
            'titre' => $this->tache->titre,
            'old_statut' => $this->oldStatut,
            'new_statut' => $this->newStatut,
        ];
    }
}
