<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TrialExpiringNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly int $remainingDays,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'trial_expiring');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('subscription.notifications.trial_expiring.subject', [], $locale))
            ->greeting('Bonjour '.$notifiable->nom.',')
            ->line(__('subscription.notifications.trial_expiring.line1', [
                'workspace' => $this->workspace->nom,
                'days' => $this->remainingDays,
            ], $locale))
            ->line(__('subscription.notifications.trial_expiring.line2', [], $locale))
            ->action(__('subscription.notifications.trial_expiring.action', [], $locale), url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'trial_expiring',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'remaining_days' => $this->remainingDays,
            'dedup_key' => app(NotificationService::class)->dedupKey('trial_expiring', $this->workspace->id),
        ];
    }
}
