<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Task 9 — notification envoyée à un agent lorsque sa fiche d'évaluation
 * est (re)calculée pour une période. Canal principal: in-app + email.
 * Pas de push (signal moyen, pas urgent).
 *
 * Le score global et la période sont injectés au constructeur pour que
 * l'email puisse les afficher sans relire la base.
 */
class EvaluationSheetReadyNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $agent,
        public readonly float $scoreGlobal,
        public readonly string $periodeStart,
        public readonly string $periodeEnd,
    ) {}

    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'evaluation_sheet_ready');
    }

    public function toMail(object $notifiable): MailMessage
    {
        $locale = $notifiable->preferred_locale ?? app()->getLocale();
        $scorePct = (int) round($this->scoreGlobal * 100);

        return (new MailMessage)
            ->subject(__('evaluation.notifications.sheet_ready.subject'))
            ->view("emails.evaluation-sheet-ready.{$locale}", [
                'agent' => $this->agent,
                'score_pct' => $scorePct,
                'periode_start' => $this->periodeStart,
                'periode_end' => $this->periodeEnd,
                'notifiable' => $notifiable,
            ]);
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'evaluation_sheet_ready',
            'dedup_key' => app(NotificationService::class)->dedupKey('evaluation_sheet_ready', $this->agent->id),
            'agent_id' => $this->agent->id,
            'agent_nom' => $this->agent->nom,
            'score_global' => $this->scoreGlobal,
            'periode_start' => $this->periodeStart,
            'periode_end' => $this->periodeEnd,
        ];
    }
}
