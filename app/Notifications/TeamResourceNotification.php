<?php

namespace App\Notifications;

use App\Models\Team;
use App\Models\TeamResource;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TeamResourceNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected Team $team;

    protected TeamResource $resource;

    /**
     * Create a new notification instance.
     */
    public function __construct(Team $team, TeamResource $resource)
    {
        $this->team = $team;
        $this->resource = $resource;
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

        if ($preferences && $preferences->email_enabled && $preferences->team_resources) {
            $channels[] = 'mail';
        }

        return $channels;
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        $typeLabel = match ($this->resource->type) {
            'file' => '📎 Fichier',
            'link' => '🔗 Lien',
            'document' => '📄 Document',
            'template' => '📋 Modèle',
            default => 'Ressource',
        };

        $mail = (new MailMessage)
            ->subject("Nouvelle ressource dans {$this->team->name}")
            ->greeting("Bonjour {$notifiable->nom}!")
            ->line($typeLabel.': **'.($this->resource->title ?? $this->resource->name).'**');

        if ($this->resource->description) {
            $mail->line($this->resource->description);
        }

        return $mail
            ->action('Voir la ressource', url("/teams/{$this->team->uuid}"))
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
            'type' => 'team_resource',
            'title' => $this->resource->title ?? $this->resource->name,
            'message' => "Nouvelle ressource dans {$this->team->name}",
            'team_id' => $this->team->id,
            'team_uuid' => $this->team->uuid,
            'team_name' => $this->team->name,
            'resource_id' => $this->resource->id,
            'resource_title' => $this->resource->title ?? $this->resource->name,
            'resource_type' => $this->resource->type,
            'resource_description' => $this->resource->description,
            'resource_url' => $this->resource->url,
            'url' => "/teams/{$this->team->uuid}",
            'icon' => 'folder',
            'color' => 'blue',
        ];
    }
}
