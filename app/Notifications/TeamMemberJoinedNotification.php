<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamMemberJoinedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Team $team;
    protected User $newMember;
    protected string $role;

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, User $newMember, string $role)
    {
        $this->team = $team;
        $this->newMember = $newMember;
        $this->role = $role;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    /**
     * Get the array representation of the notification.
     *
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'team_member_joined',
            'title' => 'Nouveau membre',
            'message' => "{$this->newMember->nom} a rejoint l'équipe {$this->team->name}",
            'team_id' => $this->team->id,
            'team_uuid' => $this->team->uuid,
            'team_name' => $this->team->name,
            'new_member' => [
                'id' => $this->newMember->id,
                'nom' => $this->newMember->nom,
                'email' => $this->newMember->email,
            ],
            'role' => $this->role,
            'url' => "/teams/{$this->team->uuid}",
            'icon' => 'user-plus',
            'color' => 'green',
        ];
    }
}
