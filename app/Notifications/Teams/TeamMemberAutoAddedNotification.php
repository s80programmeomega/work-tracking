<?php

namespace App\Notifications\Teams;

use App\Models\Projet;
use App\Models\Team;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMemberAutoAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Projet $projet,
        public readonly Team $team,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'projet_member_added');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Vous avez été ajouté au projet : {$this->projet->nom}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("Vous avez été automatiquement ajouté au projet **{$this->projet->nom}** suite à votre appartenance à l'équipe **{$this->team->name}**.")
            ->action('Voir le projet', config('app.frontend_url')."/projets/{$this->projet->id}")
            ->line('Vous pouvez maintenant accéder au projet et collaborer avec l\'équipe.');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'team_member_auto_added',
            'projet_id' => $this->projet->id,
            'projet_nom' => $this->projet->nom,
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
        ];
    }
}
