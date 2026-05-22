<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NotificationPreference>
 *
 * Task 8 / 8b — produit un jeu de préférences par défaut "tout activé"
 * (in-app + email + push pour les événements clés), pas d'heures
 * silencieuses, digest désactivé. Les states couvrent les variantes
 * utiles aux tests:
 *   - withDigestDaily() / withDigestWeekly() pour Task 8c
 *   - withQuietHours()  pour vérifier la suppression des push la nuit
 *   - pushDisabled()    pour vérifier le master switch off
 */
class NotificationPreferenceFactory extends Factory
{
    protected $model = NotificationPreference::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),

            // Masters
            'in_app_enabled' => true,
            'email_enabled' => true,
            'push_enabled' => true,

            // Par type — tous canaux on par défaut
            'task_assigned_in_app' => true,
            'task_assigned_email' => true,
            'task_assigned_push' => true,
            'task_due_soon_in_app' => true,
            'task_due_soon_email' => true,
            'task_due_soon_push' => true,
            'task_completed_in_app' => true,
            'task_completed_email' => false,
            'task_completed_push' => false,
            'mentioned_in_comment_in_app' => true,
            'mentioned_in_comment_email' => true,
            'mentioned_in_comment_push' => true,
            'comment_added_in_app' => true,
            'comment_added_email' => false,
            'comment_added_push' => false,
            'project_updated_in_app' => true,
            'project_updated_email' => false,
            'project_updated_push' => false,
            'deadline_approaching_in_app' => true,
            'deadline_approaching_email' => true,
            'deadline_approaching_push' => true,
            'document_uploaded_in_app' => true,
            'document_uploaded_email' => false,
            'document_uploaded_push' => false,

            // Team channels
            'team_announcements' => true,
            'team_events' => true,
            'team_resources' => false,
            'team_member_added' => true,

            // Digest — off par défaut (enum 'none'|'daily'|'weekly')
            'digest_frequency' => 'none',
            'digest_time' => '08:00:00',
            'digest_day_of_week' => 1,
            'last_digest_sent_at' => null,

            // Quiet hours — off par défaut
            'quiet_hours_enabled' => false,
            'quiet_hours_start' => '22:00:00',
            'quiet_hours_end' => '07:00:00',
        ];
    }

    /** Master switch push désactivé (Cas 5 de la doc Task 8b). */
    public function pushDisabled(): static
    {
        return $this->state(['push_enabled' => false]);
    }

    /** Heures silencieuses activées (22h–7h par défaut). */
    public function withQuietHours(string $start = '22:00:00', string $end = '07:00:00'): static
    {
        return $this->state([
            'quiet_hours_enabled' => true,
            'quiet_hours_start' => $start,
            'quiet_hours_end' => $end,
        ]);
    }

    /** Digest quotidien à 8h. */
    public function withDigestDaily(string $time = '08:00:00'): static
    {
        return $this->state([
            'digest_frequency' => 'daily',
            'digest_time' => $time,
        ]);
    }

    /** Digest hebdomadaire le lundi à 8h. */
    public function withDigestWeekly(int $dayOfWeek = 1, string $time = '08:00:00'): static
    {
        return $this->state([
            'digest_frequency' => 'weekly',
            'digest_day_of_week' => $dayOfWeek,
            'digest_time' => $time,
        ]);
    }

    /** Préférences rattachées à un utilisateur précis. */
    public function forUser(User $user): static
    {
        return $this->state(['user_id' => $user->id]);
    }
}
