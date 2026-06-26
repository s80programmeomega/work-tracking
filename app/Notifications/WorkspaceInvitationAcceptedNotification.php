<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceInvitation;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceInvitationAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly WorkspaceInvitation $invitation,
        public readonly User $acceptedBy,
        public readonly Workspace $workspace,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'workspace_invitation_accepted');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("{$this->acceptedBy->nom_complet} a rejoint {$this->workspace->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("**{$this->acceptedBy->nom_complet}** a accepté votre invitation et a rejoint le workspace **{$this->workspace->nom}**.")
            ->action('Voir les membres', url('/workspace/members'))
            ->salutation('Cordialement, '.config('app.name'));
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Invitation acceptée',
            'body' => "{$this->acceptedBy->nom_complet} a rejoint « {$this->workspace->nom} ».",
            'url' => url('/workspace/members'),
            'tag' => "invitation-accepted-{$this->invitation->id}",
            'icon' => url('/favicon.ico'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workspace_invitation_accepted',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'accepted_by_id' => $this->acceptedBy->id,
            'accepted_by' => $this->acceptedBy->nom_complet,
            'invitation_id' => $this->invitation->id,
        ];
    }
}
