<?php

namespace App\Notifications\WorkspaceChat;

use App\Models\User;
use App\Models\WorkspaceMessage;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceMessageMentionNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WorkspaceMessage $message,
        public readonly User $actor,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'workspace_chat_mention');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->message->loadMissing('channel.workspace');

        $channelLabel = match ($this->message->channel?->type) {
            'responsibles' => 'Responsables',
            default => 'Global',
        };
        $workspaceName = $this->message->channel?->workspace?->nom ?? 'Workspace';
        $snippet = mb_substr($this->message->content, 0, 200);

        return (new MailMessage)
            ->subject("[{$workspaceName}] {$this->actor->nom} vous a mentionné dans #{$channelLabel}")
            ->greeting("Bonjour {$notifiable->prenom},")
            ->line("{$this->actor->nom} vous a mentionné dans le canal **#{$channelLabel}** du workspace **{$workspaceName}**.")
            ->line("**Message :** {$snippet}")
            ->action('Voir la discussion', rtrim(config('app.frontend_url'), '/').'/workspace/chat');
    }

    public function toArray(object $notifiable): array
    {
        $this->message->loadMissing('channel.workspace');
        $workspaceName = $this->message->channel?->workspace?->nom ?? 'Workspace';

        return [
            'type' => 'workspace_chat_mention',
            'title' => "{$this->actor->nom} vous a mentionné",
            'message_uuid' => $this->message->uuid,
            'channel_type' => $this->message->channel?->type,
            'workspace_id' => $this->message->workspace_id,
            'workspace_name' => $workspaceName,
            'actor_nom' => $this->actor->nom,
            'content_snippet' => mb_substr($this->message->content, 0, 100),
            'url' => '/workspace/chat',
        ];
    }

    public function toWebPush(object $notifiable): array
    {
        $this->message->loadMissing('channel.workspace');
        $channelLabel = $this->message->channel?->type === 'responsibles' ? 'Responsables' : 'Global';
        $workspaceName = $this->message->channel?->workspace?->nom ?? 'Workspace';

        return [
            'title' => "{$this->actor->nom} vous a mentionné",
            'body' => "[{$workspaceName} · #{$channelLabel}] ".mb_substr($this->message->content, 0, 120),
            'url' => '/workspace/chat',
            'tag' => 'workspace_chat_mention',
            'icon' => url('/favicon.ico'),
        ];
    }
}
