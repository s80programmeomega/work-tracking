<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class SessionRevokedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public readonly int $tokenId) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail', 'database'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('notifications.session_revoked.subject'))
            ->greeting(__('notifications.session_revoked.greeting', ['name' => $notifiable->name]))
            ->line(__('notifications.session_revoked.line1'))
            ->line(__('notifications.session_revoked.line2', ['time' => now()->format('d/m/Y H:i')]))
            ->line(__('notifications.session_revoked.line3'));
    }

    /**
     * @return array<string, string|int>
     */
    public function toDatabase(object $notifiable): array
    {
        return [
            'type' => 'session_revoked',
            'token_id' => $this->tokenId,
            'message' => __('notifications.session_revoked.line1'),
            'time' => now()->toISOString(),
        ];
    }
}
