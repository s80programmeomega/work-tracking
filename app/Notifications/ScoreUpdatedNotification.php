<?php

namespace App\Notifications;

use App\Models\EvaluationScore;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

/**
 * Notifies the task responsable that an N1 decision affected their score.
 *
 * In-app only, no email — per Task 7 spec ("score changes are silent, no email").
 * The frontend reads the database row via the notification bell.
 */
class ScoreUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly EvaluationScore $score,
    ) {}

    /**
     * Database-only delivery. The mail and broadcast channels are
     * deliberately absent — score changes shouldn't spam mailboxes.
     */
    public function via(object $notifiable): array
    {
        return ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'score_updated',
            'score_id' => $this->score->id,
            'critere' => $this->score->critere,
            'valeur' => (float) $this->score->valeur,
            'periode_start' => $this->score->periode_start->format('Y-m-d'),
            'periode_end' => $this->score->periode_end->format('Y-m-d'),
            'tache_id' => $this->score->meta['tache_id'] ?? null,
            'tache_resultat_id' => $this->score->meta['tache_resultat_id'] ?? null,
            'decision' => $this->score->meta['decision'] ?? null,
        ];
    }
}
