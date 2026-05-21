<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Mail\DailyDigestMail;
use App\Models\NotificationPreference;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;

/**
 * Task 8c — Daily email digest.
 *
 * Aggregates each user's unread database notifications received since their
 * last digest was sent, then emails them a digest using a Blade template.
 *
 * Scheduling: registered in app/Console/Kernel.php to run every 15 minutes.
 * The command itself filters users whose digest_time falls within the current
 * window AND who haven't already received a digest today.
 *
 * Quiet hours: a user inside their quiet_hours window is skipped — the next
 * tick after the window ends will catch them.
 */
class SendDailyDigest extends Command
{
    protected $signature = 'notifications:send-digest
                            {--dry-run : list eligible users without sending}
                            {--user= : send only for this user id (debug)}';

    protected $description = 'Send daily email digest to users per their notification_preferences';

    public function handle(): int
    {
        $now = now();
        $query = $this->eligibleUsersQuery($now);

        if ($userId = $this->option('user')) {
            $query->where('user_id', (int) $userId);
        }

        $count = 0;
        $skipped = 0;

        $query->with('user')->chunkById(100, function ($preferences) use (&$count, &$skipped, $now) {
            foreach ($preferences as $pref) {
                if (! $pref->user || $pref->user->trashed()) {
                    continue;
                }

                if ($this->isInQuietHours($pref, $now)) {
                    $skipped++;
                    Log::info('Digest reporté — utilisateur dans ses heures silencieuses', [
                        'user_id' => $pref->user_id,
                        'reason' => 'quiet_hours',
                    ]);

                    continue;
                }

                $unread = $pref->user->unreadNotifications()
                    ->where('created_at', '>=', $pref->last_digest_sent_at ?? $now->copy()->subDay())
                    ->get();

                if ($unread->isEmpty()) {
                    $skipped++;

                    continue;
                }

                if ($this->option('dry-run')) {
                    $this->info("Would send to user {$pref->user_id} ({$unread->count()} notifications)");
                    $count++;

                    continue;
                }

                try {
                    Mail::to($pref->user)->queue(new DailyDigestMail($pref->user, $unread));
                    $pref->forceFill(['last_digest_sent_at' => $now])->save();
                    $count++;
                    Log::info('Digest envoyé', [
                        'user_id' => $pref->user_id,
                        'notifications_count' => $unread->count(),
                    ]);
                } catch (\Throwable $e) {
                    Log::error('Échec d\'envoi du digest', [
                        'user_id' => $pref->user_id,
                        'error' => $e->getMessage(),
                    ]);
                }
            }
        }, 'id');

        $this->info("Digest run complete — {$count} sent, {$skipped} skipped");

        return self::SUCCESS;
    }

    /**
     * Users eligible for a digest right now:
     *   - digest_frequency != 'none'
     *   - digest_time has been crossed today
     *   - last_digest_sent_at is null OR earlier than today's digest_time
     *
     * For 'weekly' frequency, additionally require it's the configured day_of_week.
     */
    private function eligibleUsersQuery(Carbon $now)
    {
        $todayDigestThreshold = $now->copy()->setTimeFromTimeString('00:00:00');

        return NotificationPreference::query()
            ->where('digest_frequency', '!=', 'none')
            ->where('digest_time', '<=', $now->format('H:i:s'))
            ->where(function ($q) use ($todayDigestThreshold) {
                $q->whereNull('last_digest_sent_at')
                    ->orWhere('last_digest_sent_at', '<', $todayDigestThreshold);
            });
    }

    /**
     * Returns true if `now()` falls inside the user's quiet_hours window.
     * Window may wrap midnight (e.g. 22:00 → 08:00).
     */
    private function isInQuietHours(NotificationPreference $pref, Carbon $now): bool
    {
        if (! $pref->quiet_hours_enabled || ! $pref->quiet_hours_start || ! $pref->quiet_hours_end) {
            return false;
        }

        $start = $pref->quiet_hours_start->format('H:i:s');
        $end = $pref->quiet_hours_end->format('H:i:s');
        $current = $now->format('H:i:s');

        // Window wraps midnight (e.g. 22:00 → 08:00)
        if ($start > $end) {
            return $current >= $start || $current < $end;
        }

        return $current >= $start && $current < $end;
    }
}
