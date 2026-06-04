<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie l'utilisateur que son export de recherche est prêt au téléchargement.
 * Envoyée à la fin de SearchExportJob.
 */
class SearchExportReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly string $downloadUrl) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Votre export de recherche est prêt')
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line('Votre export de résultats de recherche est disponible.')
            ->action('Télécharger le fichier Excel', $this->downloadUrl)
            ->line('Le lien expire dans 24 heures.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'search_export_ready',
            'download_url' => $this->downloadUrl,
            'url' => $this->downloadUrl,
        ];
    }
}
