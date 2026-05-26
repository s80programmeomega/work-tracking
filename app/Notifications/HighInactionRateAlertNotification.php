<?php

namespace App\Notifications;

use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Alerte envoyée au manager quand le taux d'inaction N0 d'un responsable
 * dépasse 33 % sur la période (Task 10).
 * Canal : in-app uniquement (pas d'email — alerte de suivi interne).
 */
class HighInactionRateAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly User $responsable,
        public readonly float $inactionRate,
        public readonly int $inactionCount,
        public readonly int $totalResultats,
    ) {}

    public function via(object $notifiable): array
    {
        // Alerte interne uniquement — pas d'email pour éviter le bruit.
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'high_inaction_rate_alert',
            'dedup_key' => app(NotificationService::class)->dedupKey('high_inaction_rate', $this->responsable->id),
            'responsable_id' => $this->responsable->id,
            'responsable_nom' => "{$this->responsable->prenom} {$this->responsable->nom}",
            'inaction_rate' => round($this->inactionRate, 2),
            'inaction_count' => $this->inactionCount,
            'total_resultats' => $this->totalResultats,
        ];
    }
}
