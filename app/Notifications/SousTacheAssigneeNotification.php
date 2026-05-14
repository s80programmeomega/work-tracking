<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\SousTache;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SousTacheAssigneeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly SousTache $sousTache,
        public readonly User $assignedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('sous_taches.notifications.assigned.subject'))
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line(__('sous_taches.notifications.assigned.line1', ['titre' => $this->sousTache->titre]))
            ->line(__('sous_taches.notifications.assigned.line2', ['by' => $this->assignedBy->nom_complet]))
            ->when($this->sousTache->date_echeance, fn ($m) => $m->line(
                __('sous_taches.notifications.assigned.due', ['date' => $this->sousTache->date_echeance->format('d/m/Y')])
            ));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'sous_tache_assigned',
            'sous_tache_id' => $this->sousTache->id,
            'tache_id' => $this->sousTache->tache_id,
            'titre' => $this->sousTache->titre,
            'assigned_by' => $this->assignedBy->nom_complet,
        ];
    }
}
