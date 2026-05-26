<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Document;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentUploadedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Document $document,
        public readonly User $uploadedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'document_uploaded');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_uploaded',
            'document_id' => $this->document->id,
            'document_nom' => $this->document->nom,
            'uploaded_by' => $this->uploadedBy->nom_complet,
            'dedup_key' => app(NotificationService::class)->dedupKey('document_uploaded', $this->document->id),
        ];
    }
}
