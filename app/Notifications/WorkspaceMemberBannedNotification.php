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

class WorkspaceMemberBannedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly User $bannedBy,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'workspace_member_banned');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = (new MailMessage)
            ->subject("Accès retiré : {$this->workspace->nom}")
            ->greeting("Bonjour {$notifiable->nom},")
            ->line("Votre accès au workspace **{$this->workspace->nom}** a été révoqué par {$this->bannedBy->nom_complet}.");

        if ($this->reason) {
            $mail->line("**Motif :** {$this->reason}");
        }

        return $mail
            ->line("Si vous pensez qu'il s'agit d'une erreur, veuillez contacter l'administrateur.")
            ->salutation('Cordialement, '.config('app.name'));
    }

    public function toWebPush(object $notifiable): array
    {
        return [
            'title' => 'Accès workspace révoqué',
            'body' => "Votre accès à « {$this->workspace->nom} » a été révoqué.",
            'url' => url('/dashboard'),
            'tag' => "ban-{$this->workspace->id}-{$notifiable->id}",
            'icon' => url('/favicon.ico'),
        ];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workspace_member_banned',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'banned_by_id' => $this->bannedBy->id,
            'banned_by' => $this->bannedBy->nom_complet,
            'reason' => $this->reason,
        ];
    }
}
