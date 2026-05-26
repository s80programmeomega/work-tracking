<?php

namespace App\Notifications;

use App\Models\Tache;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Alerte envoyée au directeur/owner quand le drapeau escalades_abusives
 * est activé pour un membre du workspace (Task 10).
 * Canal : in-app + email (inline MailMessage).
 */
class AbusiveEscalationAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $abuser,
        public readonly Tache $tache,
        public readonly int $bypassCount,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'escalades_abusives');
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('evaluation.notifications.abusive_escalation_alert.subject'))
            ->greeting("Bonjour {$notifiable->nom},")
            ->line(__('evaluation.notifications.abusive_escalation_alert.line1', [
                'nom' => "{$this->abuser->prenom} {$this->abuser->nom}",
                'tache' => $this->tache->titre,
            ]))
            ->line(__('evaluation.notifications.abusive_escalation_alert.line2', [
                'count' => $this->bypassCount,
            ]))
            ->action(
                __('evaluation.notifications.abusive_escalation_alert.action'),
                url('/evaluations/tableau-de-bord')
            );
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'abusive_escalation_alert',
            'dedup_key' => app(NotificationService::class)->dedupKey('escalades_abusives', $this->tache->id),
            'abuser_id' => $this->abuser->id,
            'abuser_nom' => "{$this->abuser->prenom} {$this->abuser->nom}",
            'tache_id' => $this->tache->id,
            'tache_titre' => $this->tache->titre,
            'bypass_count' => $this->bypassCount,
        ];
    }
}
