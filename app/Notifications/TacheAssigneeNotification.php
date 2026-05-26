<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Tache;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Task 11 — Notifie chaque intervenant assigné lors de la création de la tâche via le wizard.
 * Inclut la liste des ressources attachées (fichiers + liens externes).
 */
class TacheAssigneeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Tache $tache,
        public readonly User $assignedBy,
        /** @var array<int, array{nom: string, url: string|null}> */
        public readonly array $resources = [],
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'tache_assignee');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->locale ?? app()->getLocale();
        $view = "emails.tache-assigned.{$locale}";

        if (! view()->exists($view)) {
            $view = 'emails.tache-assigned.fr';
        }

        return (new MailMessage)
            ->subject(__('taches.notifications.assignee.subject', [], $locale))
            ->view($view, [
                'notifiable' => $notifiable,
                'tache' => $this->tache,
                'assignedBy' => $this->assignedBy,
                'resources' => $this->resources,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'tache_assignee',
            'tache_id' => $this->tache->id,
            'tache_titre' => $this->tache->titre,
            'tache_echeance' => $this->tache->echeance?->format('Y-m-d'),
            'assigned_by' => $this->assignedBy->nom_complet,
            'resources_count' => count($this->resources),
            'dedup_key' => app(NotificationService::class)
                ->dedupKey('tache_assignee', $this->tache->id),
        ];
    }
}
