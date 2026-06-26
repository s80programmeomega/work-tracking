<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceMemberUnbannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $unbannedBy,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'workspace_member_unbanned');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Accès rétabli : {$this->workspace->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Votre accès au workspace **{$this->workspace->nom}** a été rétabli par {$this->unbannedBy->nom_complet}.")
            ->line('Vous pouvez de nouveau accéder à toutes les ressources de ce workspace.')
            ->salutation('Cordialement, '.config('app.name'));
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Accès workspace rétabli',
            'body' => "Votre accès à « {$this->workspace->nom} » a été rétabli.",
            'url' => url('/'),
            'tag' => "unban-{$this->workspace->id}-{$notifiable->id}",
            'icon' => url('/favicon.ico'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workspace_member_unbanned',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'unbanned_by_id' => $this->unbannedBy->id,
            'unbanned_by' => $this->unbannedBy->nom_complet,
        ];
    }
}
