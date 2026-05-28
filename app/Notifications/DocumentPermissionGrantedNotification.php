<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentPermissionGrantedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly User $sharedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'document_shared');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Document partagé : {$this->document->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->sharedBy->nom_complet} a partagé le document \"{$this->document->nom}\" avec vous.")
            ->action('Voir le document', url('/documents?tab=shared'))
            ->line('Vous pouvez le consulter dès maintenant dans votre espace de travail.');
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Document partagé avec vous',
            'body' => "{$this->sharedBy->nom_complet} a partagé « {$this->document->nom} » avec vous.",
            'url' => url('/documents?tab=shared'),
            'tag' => "document-shared-{$this->document->id}",
            'icon' => url('/favicon.ico'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_shared',
            'document_id' => $this->document->id,
            'document_nom' => $this->document->nom,
            'shared_by_id' => $this->sharedBy->id,
            'shared_by' => $this->sharedBy->nom_complet,
        ];
    }
}
