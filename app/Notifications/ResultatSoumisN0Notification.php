<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultatSoumisN0Notification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TacheResultat $resultat,
        public readonly User $author,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'soumis_n0');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('circuit_validation.notifications.soumis_n0.subject'))
            ->greeting("Bonjour {$notifiable->nom},")
            ->line(__('circuit_validation.notifications.soumis_n0.line1', ['titre' => $this->resultat->tache->titre]))
            ->line(__('circuit_validation.notifications.soumis_n0.line2', [
                'by' => $this->author->nom,
                'taux' => $this->resultat->taux_realisation,
            ]))
            ->action(__('circuit_validation.notifications.soumis_n0.action'), url("/taches/{$this->resultat->tache_id}"));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resultat_soumis_n0',
            'dedup_key' => app(NotificationService::class)->dedupKey('soumis_n0', $this->resultat->id),
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
            'author_id' => $this->author->id,
            'author_nom' => $this->author->nom,
            'taux_realisation' => $this->resultat->taux_realisation,
        ];
    }
}
