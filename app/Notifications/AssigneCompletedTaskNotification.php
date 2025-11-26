<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * 📧 Notification: Assigné a terminé sa partie
 */
class AssigneCompletedTaskNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public Tache $tache,
        public User $assigneWhoCompleted
    ) {}

    public function via($notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("✅ {$this->assigneWhoCompleted->nom} a terminé sa partie")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("{$this->assigneWhoCompleted->nom} a terminé sa partie de la tâche **{$this->tache->titre}**.")
            ->line("Activité: {$this->tache->activite->nom}")
            ->action('Voir la tâche', url("/taches/{$this->tache->id}"))
            ->line('Vous serez notifié quand tous les membres auront terminé.');
    }

    public function toArray($notifiable): array
    {
        return [
            'type' => 'tache_partie_terminee',
            'tache_id' => $this->tache->id,
            'tache_titre' => $this->tache->titre,
            'assigne_id' => $this->assigneWhoCompleted->id,
            'assigne_nom' => $this->assigneWhoCompleted->nom,
            'activite_nom' => $this->tache->activite->nom,
            'url' => "/taches/{$this->tache->id}"
        ];
    }
}