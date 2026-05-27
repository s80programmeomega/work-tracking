<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WorkspaceSuspendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly ?string $reason = null,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'workspace_suspended');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        $mail = (new MailMessage)
            ->subject(__('admin.notifications.workspace_suspended.subject', ['workspace' => $this->workspace->nom], $locale))
            ->greeting('Bonjour '.$notifiable->nom.',')
            ->line(__('admin.notifications.workspace_suspended.line1', ['workspace' => $this->workspace->nom], $locale));

        if ($this->reason) {
            $mail->line(__('admin.notifications.workspace_suspended.reason', ['reason' => $this->reason], $locale));
        }

        return $mail
            ->line(__('admin.notifications.workspace_suspended.line2', [], $locale))
            ->action(__('admin.notifications.workspace_suspended.action', [], $locale), url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'workspace_suspended',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'reason' => $this->reason,
            'dedup_key' => app(NotificationService::class)->dedupKey('workspace_suspended', $this->workspace->id),
        ];
    }
}
