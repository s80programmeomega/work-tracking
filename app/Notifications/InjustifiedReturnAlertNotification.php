<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Task 9 — notification envoyée au manager (notifiable) quand un de
 * ses responsables (agent) franchit le seuil de renvois injustifiés
 * (> 40% par défaut). Canal: in-app + email inline (pas de Blade —
 * c'est un signal compact, pas un rapport complet).
 */
class InjustifiedReturnAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $agent,
        public readonly float $rate,
        public readonly string $periodeStart,
        public readonly string $periodeEnd,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'unjustified_return_alert');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $ratePct = (int) round($this->rate * 100);

        return (new MailMessage)
            ->subject(__('evaluation.notifications.unjustified_return_alert.subject'))
            ->greeting("Bonjour {$notifiable->nom},")
            ->line(__('evaluation.notifications.unjustified_return_alert.line1', [
                'nom' => $this->agent->nom,
                'rate' => $ratePct,
            ]))
            ->line(__('evaluation.notifications.unjustified_return_alert.line2', [
                'start' => $this->periodeStart,
                'end' => $this->periodeEnd,
            ]))
            ->action(
                __('evaluation.notifications.unjustified_return_alert.action'),
                url("/evaluations/personnel/{$this->agent->id}/historique")
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'unjustified_return_alert',
            'dedup_key' => app(NotificationService::class)->dedupKey('unjustified_return_alert', $this->agent->id),
            'agent_id' => $this->agent->id,
            'agent_nom' => $this->agent->nom,
            'rate' => $this->rate,
            'periode_start' => $this->periodeStart,
            'periode_end' => $this->periodeEnd,
        ];
    }
}
