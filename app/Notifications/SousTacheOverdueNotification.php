<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SousTache;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class SousTacheOverdueNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly SousTache $sousTache) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sous_tache_overdue',
            'sous_tache_id' => $this->sousTache->id,
            'tache_id' => $this->sousTache->tache_id,
            'titre' => $this->sousTache->titre,
            'date_echeance' => $this->sousTache->date_echeance?->format('Y-m-d'),
        ];
    }
}
