<?php

namespace App\Notifications;

use App\Models\TeamMessage;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Notifie un utilisateur mentionné (@mention) dans un message d'équipe.
 */
class ChatMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly TeamMessage $message,
        public readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'chat_mention');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $teamName = $this->message->team?->name ?? 'équipe';
        $snippet = mb_substr($this->message->content, 0, 200);

        return (new MailMessage)
            ->subject("{$this->actor->nom} vous a mentionné dans #{$teamName}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("{$this->actor->nom} vous a mentionné dans la discussion **#{$teamName}**.")
            ->line("**Message :** {$snippet}")
            ->action('Voir la discussion', rtrim(config('app.frontend_url'), '/').'/teams/'.$this->message->team?->uuid);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'chat_mention',
            'message_uuid' => $this->message->uuid,
            'team_uuid' => $this->message->team?->uuid,
            'team_name' => $this->message->team?->name,
            'actor_nom' => $this->actor->nom,
            'content_snippet' => mb_substr($this->message->content, 0, 100),
            'url' => '/teams/'.$this->message->team?->uuid,
        ];
    }
}
