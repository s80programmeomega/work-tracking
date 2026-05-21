<?php

namespace App\Notifications;

use App\Models\TacheResultat;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BypassActivatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TacheResultat $resultat,
        public readonly User $author,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'bypass');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('circuit_validation.notifications.bypass_active.subject'))
            ->view("emails.bypass-activated.{$locale}", [
                'resultat' => $this->resultat,
                'author' => $this->author,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'bypass_activated',
            'dedup_key' => app(NotificationService::class)->dedupKey('bypass', $this->resultat->id),
            'tache_resultat_id' => $this->resultat->id,
            'tache_id' => $this->resultat->tache_id,
            'tache_titre' => $this->resultat->tache->titre,
            'author_id' => $this->author->id,
            'author_nom' => $this->author->nom,
            'motif_bypass' => $this->resultat->motif_bypass,
        ];
    }
}
