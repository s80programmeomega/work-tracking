<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExtendedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly int $newDurationDays,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'trial_extended');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();
        $expiresAt = $this->workspace->trial_started_at?->addDays($this->newDurationDays);

        return (new MailMessage)
            ->subject(__('admin.notifications.trial_extended.subject', ['workspace' => $this->workspace->nom], $locale))
            ->greeting('Bonjour '.$notifiable->nom.',')
            ->line(__('admin.notifications.trial_extended.line1', ['workspace' => $this->workspace->nom], $locale))
            ->line(__('admin.notifications.trial_extended.line2', [
                'days' => $this->newDurationDays,
                'expires_at' => $expiresAt?->format('d/m/Y') ?? '—',
            ], $locale))
            ->action(__('admin.notifications.trial_extended.action', [], $locale), url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_extended',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'new_duration_days' => $this->newDurationDays,
            'dedup_key' => app(NotificationService::class)->dedupKey('trial_extended', $this->workspace->id),
        ];
    }
}
