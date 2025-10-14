<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\TeamAnnouncement;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamAnnouncementNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Team $team;
    protected TeamAnnouncement $announcement;

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, TeamAnnouncement $announcement)
    {
        $this->team = $team;
        $this->announcement = $announcement;
    }

    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        // Check user's notification preferences
        $preferences = $notifiable->notificationPreference;

        $channels = ['database'];

        if ($preferences && $preferences->email_enabled && $preferences->team_announcements) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $priorityLabel = match($this->announcement->priority) {
            'urgent' => '🚨 URGENT',
            'high' => '⚠️ Haute',
            'normal' => 'Normale',
            default => 'Normale',
        };

        return (new MailMessage)
            ->subject("[{$priorityLabel}] Nouvelle annonce dans {$this->team->name}")
            ->greeting("Bonjour {$notifiable->nom}!")
            ->line("Une nouvelle annonce a été publiée dans l'équipe **{$this->team->name}**.")
            ->line("**{$this->announcement->title}**")
            ->line($this->announcement->content)
            ->action("Voir l'annonce", url("/teams/{$this->team->uuid}"))
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
            'type' => 'team_announcement',
            'title' => $this->announcement->title,
            'message' => "Nouvelle annonce dans {$this->team->name}",
            'team_id' => $this->team->id,
            'team_uuid' => $this->team->uuid,
            'team_name' => $this->team->name,
            'announcement_id' => $this->announcement->id,
            'announcement_title' => $this->announcement->title,
            'announcement_content' => $this->announcement->content,
            'priority' => $this->announcement->priority,
            'url' => "/teams/{$this->team->uuid}",
            'icon' => 'megaphone',
            'color' => $this->announcement->priority === 'urgent' ? 'red' : 'amber',
        ];
    }
}
