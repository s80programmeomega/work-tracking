<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class DocumentSharedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly User $sharedBy,
        public readonly string $recipientEmail,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $downloadUrl = url("/documents/{$this->document->id}/download");

        return (new MailMessage)
            ->subject(__('documents.share.email_subject', ['nom' => $this->document->nom]))
            ->greeting(__('documents.share.email_greeting'))
            ->line(__('documents.share.email_intro', ['user' => $this->sharedBy->nom_complet, 'nom' => $this->document->nom]))
            ->action(__('documents.actions.download'), $downloadUrl)
            ->line(__('documents.share.email_footer'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_shared',
            'document_id' => $this->document->id,
            'document_nom' => $this->document->nom,
            'shared_by' => $this->sharedBy->nom_complet,
            'recipient' => $this->recipientEmail,
        ];
    }
}
