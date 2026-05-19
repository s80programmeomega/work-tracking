<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class EscaladesAbusivesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TacheResultat $resultat,
        public readonly User $abuser,
        public readonly int $consecutiveCount,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('circuit_validation.notifications.escalades_abusives.subject'))
            ->greeting("Bonjour {$notifiable->nom},")
            ->line(__('circuit_validation.notifications.escalades_abusives.line1', [
                'nom' => $this->abuser->nom,
                'tache' => $this->resultat->tache->titre,
            ]))
            ->line(__('circuit_validation.notifications.escalades_abusives.line2', [
                'count' => $this->consecutiveCount,
            ]))
            ->action(__('circuit_validation.notifications.escalades_abusives.action'), url("/taches/{$this->resultat->tache_id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'escalades_abusives',
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
            'abuser_id' => $this->abuser->id,
            'abuser_nom' => $this->abuser->nom,
            'consecutive_count' => $this->consecutiveCount,
        ];
    }
}
