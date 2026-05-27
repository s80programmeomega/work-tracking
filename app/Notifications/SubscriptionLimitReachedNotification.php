<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\Workspace;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SubscriptionLimitReachedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Workspace $workspace,
        public readonly string $limitType,
        public readonly int|string $currentValue,
        public readonly int|string $maxValue,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'subscription_limit_reached');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();

        return (new MailMessage)
            ->subject(__('subscription.notifications.limit_reached.subject', ['workspace' => $this->workspace->nom], $locale))
            ->greeting('Bonjour '.$notifiable->nom.',')
            ->line(__('subscription.notifications.limit_reached.line1', [
                'limit' => $this->limitType,
                'workspace' => $this->workspace->nom,
            ], $locale))
            ->line(__('subscription.notifications.limit_reached.line2', [
                'current' => $this->currentValue,
                'max' => $this->maxValue,
            ], $locale))
            ->action(__('subscription.notifications.limit_reached.action', [], $locale), url('/'));
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'subscription_limit_reached',
            'workspace_id' => $this->workspace->id,
            'workspace_nom' => $this->workspace->nom,
            'limit_type' => $this->limitType,
            'current_value' => $this->currentValue,
            'max_value' => $this->maxValue,
            'dedup_key' => app(NotificationService::class)->dedupKey('subscription_limit_reached', $this->workspace->id),
        ];
    }
}
