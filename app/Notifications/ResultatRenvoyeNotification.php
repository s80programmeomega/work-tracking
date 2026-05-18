<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ResultatRenvoyeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TacheResultat $resultat,
        public readonly User $n0Actor,
        public readonly string $commentaire,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('circuit_validation.notifications.renvoye_n0.subject'))
            ->view("emails.resultat-renvoye.{$locale}", [
                'resultat' => $this->resultat,
                'n0Actor' => $this->n0Actor,
                'commentaire' => $this->commentaire,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'resultat_renvoye_n0',
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
            'n0_actor_id' => $this->n0Actor->id,
            'n0_actor_nom' => $this->n0Actor->nom,
            'commentaire' => $this->commentaire,
        ];
    }
}
