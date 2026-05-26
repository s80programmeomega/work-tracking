<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tache;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Task 11 — Notifie les intervenants des ressources (fichiers + liens) attachées à la tâche
 * lors de sa création via le wizard. Envoyé uniquement si des ressources existent.
 */
class TacheResourcesNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tache $tache,
        public readonly User $attachedBy,
        /** @var array<int, array{nom: string, url: string|null}> */
        public readonly array $resources = [],
    ) {}

    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tache_resources',
            'tache_id' => $this->tache->id,
            'tache_titre' => $this->tache->titre,
            'attached_by' => $this->attachedBy->nom_complet,
            'resources' => $this->resources,
            'dedup_key' => app(NotificationService::class)
                ->dedupKey('tache_resources', $this->tache->id),
        ];
    }
}
