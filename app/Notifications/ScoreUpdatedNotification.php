<?php

namespace App\Notifications;

use App\Models\EvaluationScore;
use App\Services\NotificationService;
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
     * channelsFor('score_updated') returns ['database', 'broadcast'] —
     * in-app + live badge update. No mail (score changes shouldn't spam mailboxes).
     */
    public function via(object $notifiable): array
    {
        return app(NotificationService::class)->channelsFor($notifiable, 'score_updated');
    }

    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'score_updated',
            'dedup_key' => app(NotificationService::class)->dedupKey(
                'score_updated',
                $this->score->meta['tache_resultat_id'] ?? null,
            ),
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
