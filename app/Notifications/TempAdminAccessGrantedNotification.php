<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TempAdminAccessGrantedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $grantedBy,
        public readonly Workspace $workspace,
        public readonly Carbon $expiresAt,
        public readonly string $expiryAction,
        public readonly ?string $plainPassword = null,
    ) {}

    /** Toujours mail + database — accès admin = notification critique. */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $expiryLabel = $this->expiryAction === 'delete'
            ? __('notifications.temp_admin_expiry_delete')
            : __('notifications.temp_admin_expiry_suspend');

        $mail = (new MailMessage)
            ->subject(__('notifications.temp_admin_subject'))
            ->greeting(__('notifications.greeting', ['name' => $notifiable->prenom ?? $notifiable->nom_complet]))
            ->line(__('notifications.temp_admin_intro', [
                'granted_by' => $this->grantedBy->nom_complet,
                'workspace' => $this->workspace->nom,
            ]))
            ->line(__('notifications.temp_admin_expiry_line', [
                'date' => $this->expiresAt->translatedFormat('d F Y à H:i'),
                'action' => $expiryLabel,
            ]));

        if ($this->plainPassword !== null) {
            $mail->line(__('notifications.temp_admin_credentials', [
                'email' => $notifiable->email,
                'password' => $this->plainPassword,
            ]));
        }

        return $mail
            ->action(__('notifications.temp_admin_cta'), url('/workspaces/select'))
            ->line(__('notifications.temp_admin_warning'))
            ->salutation(__('notifications.salutation', ['app' => config('app.name')]));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'temp_admin_access_granted',
            'granted_by_id' => $this->grantedBy->id,
            'granted_by' => $this->grantedBy->nom_complet,
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'expires_at' => $this->expiresAt->toIso8601String(),
            'expiry_action' => $this->expiryAction,
        ];
    }
}
