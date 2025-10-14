<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMemberAddedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Team $team;
    protected User $addedBy;
    protected string $role;

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, User $addedBy, string $role)
    {
        $this->team = $team;
        $this->addedBy = $addedBy;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Vous avez été ajouté à l'équipe {$this->team->name}")
            ->greeting("Bonjour {$notifiable->nom}!")
            ->line("{$this->addedBy->nom} vous a ajouté à l'équipe **{$this->team->name}**.")
            ->line("Votre rôle: **{$this->role}**")
            ->action("Voir l'équipe", url("/teams/{$this->team->uuid}"))
            ->line('Merci d\'utiliser notre application!');
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'team_member_added',
            'title' => 'Nouvelle équipe',
            'message' => "{$this->addedBy->nom} vous a ajouté à l'équipe {$this->team->name}",
            'team_id' => $this->team->id,
            'team_uuid' => $this->team->uuid,
            'team_name' => $this->team->name,
            'added_by' => [
                'id' => $this->addedBy->id,
                'nom' => $this->addedBy->nom,
                'email' => $this->addedBy->email,
            ],
            'role' => $this->role,
            'url' => "/teams/{$this->team->uuid}",
            'icon' => 'users',
            'color' => 'green',
        ];
    }
}
