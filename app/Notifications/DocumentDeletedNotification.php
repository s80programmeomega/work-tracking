<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

class DocumentDeletedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $documentNom,
        public readonly User $deletedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'document_deleted');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'document_deleted',
            'document_nom' => $this->documentNom,
            'deleted_by' => $this->deletedBy->nom_complet,
            'dedup_key' => app(NotificationService::class)->dedupKey('document_deleted', 0),
        ];
    }
}
