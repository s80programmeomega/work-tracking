<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\TeamEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamEventNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Team $team;
    protected TeamEvent $event;
    protected string $action; // 'created', 'updated', 'reminder'

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, TeamEvent $event, string $action = 'created')
    {
        $this->team = $team;
        $this->event = $event;
        $this->action = $action;
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

        if ($preferences && $preferences->email_enabled && $preferences->team_events) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $actionLabel = match($this->action) {
            'created' => 'Nouvel événement',
            'updated' => 'Événement modifié',
            'reminder' => 'Rappel d\'événement',
            default => 'Événement',
        };

        $typeLabel = match($this->event->type) {
            'meeting' => '👥 Réunion',
            'deadline' => '⏰ Échéance',
            'event' => '🎉 Événement',
            'reminder' => '🔔 Rappel',
            default => 'Événement',
        };

        $mail = (new MailMessage)
            ->subject("{$actionLabel} dans {$this->team->name}")
            ->greeting("Bonjour {$notifiable->nom}!")
            ->line("{$typeLabel}: **{$this->event->title}**");

        if ($this->event->description) {
            $mail->line($this->event->description);
        }

        $mail->line("📅 Date: " . $this->event->start_date->format('d/m/Y à H:i'));

        if ($this->event->location) {
            $mail->line("📍 Lieu: {$this->event->location}");
        }

        return $mail
            ->action("Voir l'événement", url("/teams/{$this->team->uuid}"))
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
            'type' => 'team_event',
            'title' => $this->event->title,
            'message' => match($this->action) {
                'created' => "Nouvel événement dans {$this->team->name}",
                'updated' => "Événement modifié dans {$this->team->name}",
                'reminder' => "Rappel: {$this->event->title}",
                default => "Événement dans {$this->team->name}",
            },
            'team_id' => $this->team->id,
            'team_uuid' => $this->team->uuid,
            'team_name' => $this->team->name,
            'event_id' => $this->event->id,
            'event_title' => $this->event->title,
            'event_type' => $this->event->type,
            'event_description' => $this->event->description,
            'event_start_date' => $this->event->start_date->toISOString(),
            'event_end_date' => $this->event->end_date?->toISOString(),
            'event_location' => $this->event->location,
            'action' => $this->action,
            'url' => "/teams/{$this->team->uuid}",
            'icon' => 'calendar',
            'color' => 'indigo',
        ];
    }
}
