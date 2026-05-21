<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultatTransmisAutoNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly TacheResultat $resultat) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'transmis_auto');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('circuit_validation.notifications.transmis_auto.subject'))
            ->greeting("Bonjour {$notifiable->nom},")
            ->line(__('circuit_validation.notifications.transmis_auto.line1', ['titre' => $this->resultat->tache->titre]))
            ->line(__('circuit_validation.notifications.transmis_auto.line2'))
            ->action(__('circuit_validation.notifications.transmis_auto.action'), url("/taches/{$this->resultat->tache_id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resultat_transmis_auto',
            'dedup_key' => app(NotificationService::class)->dedupKey('transmis_auto', $this->resultat->id),
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
        ];
    }
}
