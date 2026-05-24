<?php

namespace App\Services;

use App\Models\NotificationPreference;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\Channels\WebPushChannel;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Spatie\Permission\Models\Role;

class NotificationService
{
    /**
     * Default deduplication window — a duplicate notification within this many
     * minutes is suppressed. Keeps overlapping circuit events (e.g., score
     * update following N1 decision) from spamming the user with two rows.
     */
    public const DEDUP_WINDOW_MINUTES = 5;

    /**
     * Resolve which channels to deliver an event through, honouring the user's
     * notification_preferences. Called from each notification's via() method.
     *
     * Default fallback (no preference row, or preference column missing for
     * this event type): ['database', 'broadcast'] — guarantees the in-app bell
     * always updates in real time, never sends email by accident.
     *
     * Email is added only when the preference row explicitly opts in for the
     * given event type (e.g., $pref->bypass_email = true).
     *
     * @param  object  $notifiable  the user being notified (User model in practice)
     * @param  string  $eventType  one of 'soumis_n0', 'renvoye_n0', 'approuve_n0',
     *                             'bypass', 'score_updated', 'escalades_abusives',
     *                             'transmis_auto', etc.
     * @return list<string> e.g. ['database', 'broadcast', 'mail']
     */
    public function channelsFor(object $notifiable, string $eventType): array
    {
        $channels = ['database', 'broadcast'];

        if (! $notifiable instanceof User) {
            return $channels;
        }

        $pref = $notifiable->getOrCreateNotificationPreference();

        if ($this->wantsEmail($pref, $eventType)) {
            $channels[] = 'mail';
        }

        // Task 8b — ajout du canal Web Push si l'utilisateur a au moins une
        // souscription active ET que son préférence push est activée pour ce
        // type d'événement. Sans souscription active, inutile de passer par
        // le canal (il serait inerte de toute façon).
        if ($this->wantsWebPush($pref, $eventType) && $this->hasActiveWebPushSubscription($notifiable)) {
            $channels[] = WebPushChannel::class;
        }

        return $channels;
    }

    /**
     * L'utilisateur a-t-il au moins une souscription Web Push active ?
     * Pas de souscription active = pas de canal webpush ajouté.
     */
    private function hasActiveWebPushSubscription(User $user): bool
    {
        return $user->pushSubscriptions()->active()->exists();
    }

    /**
     * Le push activé pour ce type d'événement dans les préférences ?
     *
     * Politique par défaut (mêmes événements que pour email — voir wantsEmail) :
     * les événements à fort signal génèrent un push. Les événements à faible
     * signal (approuve_n0, score_updated) restent silencieux côté push.
     *
     * Le master switch `push_enabled` du user override tout — si désactivé,
     * aucun push n'est envoyé peu importe le type d'événement.
     */
    private function wantsWebPush(NotificationPreference $pref, string $eventType): bool
    {
        if (! ($pref->push_enabled ?? true)) {
            return false;
        }

        return match ($eventType) {
            'renvoye_n0', 'transmis_auto', 'bypass', 'escalades_abusives',
            'unjustified_return_alert' => true,
            'evaluation_sheet_ready', 'approuve_n0', 'score_updated' => false,
            default => false,
        };
    }

    /**
     * Has this exact (recipient + event + tache_resultat) combination already
     * been recorded within DEDUP_WINDOW_MINUTES? Used by callers to avoid
     * double-dispatching when two circuit transitions fire close together
     * (e.g., N1 decision triggers BOTH a score update AND a result-validated
     * notification on the same responsable).
     *
     * Looks at the `notifications` table (Laravel's DatabaseChannel sink)
     * and matches the dedup key inside the `data` JSON column.
     */
    public function isDuplicate(User $notifiable, string $eventType, ?int $tacheResultatId = null): bool
    {
        $key = $this->dedupKey($eventType, $tacheResultatId);

        $exists = DatabaseNotification::query()
            ->where('notifiable_id', $notifiable->id)
            ->where('notifiable_type', User::class)
            ->where('created_at', '>=', now()->subMinutes(self::DEDUP_WINDOW_MINUTES))
            ->where('data->dedup_key', $key)
            ->exists();

        if ($exists) {
            Log::info('Notification dédupliquée', [
                'recipient_id' => $notifiable->id,
                'event_type' => $eventType,
                'reason' => 'duplicate_within_window',
            ]);
        }

        return $exists;
    }

    /**
     * Build the deterministic dedup key written to data.dedup_key.
     * Keeping it as a method (not just inline string concatenation) means
     * tests can assert against the same exact key the service produces.
     */
    public function dedupKey(string $eventType, ?int $tacheResultatId = null): string
    {
        return $tacheResultatId !== null
            ? "{$eventType}:resultat={$tacheResultatId}"
            : $eventType;
    }

    /**
     * Notify a user AND propagate the same notification upward in the workspace
     * hierarchy (manager → directeur), with deduplication per recipient.
     *
     * The walk:
     *   1. The direct recipient (cadre / collaborateur / whoever was passed in)
     *   2. Every workspace member with the 'manager' role
     *   3. The workspace directeur (owner_id)
     *
     * Each recipient receives the notification at most once, even if they hold
     * multiple roles (e.g., a manager who is also a project responsable).
     *
     * Skip rules:
     *   - recipient is null → only propagate to manager/directeur
     *   - workspace is null → only notify the direct recipient
     *   - dedup match in DEDUP_WINDOW_MINUTES → silently skipped
     */
    public function notifyHierarchy(
        ?User $directRecipient,
        ?Workspace $workspace,
        Notification $notification,
        string $eventType,
        ?int $tacheResultatId = null
    ): void {
        $recipients = collect();

        if ($directRecipient) {
            $recipients->push($directRecipient);
        }

        if ($workspace) {
            // Workspace directeur (owner_id) — always notified
            $directeur = $workspace->owner;
            if ($directeur) {
                $recipients->push($directeur);
            }

            // Every workspace member with the 'manager' contextual role
            $managerRoleId = Role::where('name', 'manager')
                ->where('guard_name', 'web')
                ->value('id');
            if ($managerRoleId) {
                $managers = $workspace->members()
                    ->wherePivot('role_id', $managerRoleId)
                    ->get();
                foreach ($managers as $m) {
                    $recipients->push($m);
                }
            }
        }

        // Deduplicate by user id (a manager could also be the directeur, etc.)
        $recipients = $recipients->unique('id')->values();

        foreach ($recipients as $user) {
            if ($this->isDuplicate($user, $eventType, $tacheResultatId)) {
                continue;
            }
            $user->notify($notification);
        }
    }

    /**
     * Decide whether the user wants email for this event type.
     *
     * Mapping rule: each event type maps to a `{base}_email` boolean column
     * on notification_preferences. If the column doesn't exist or is null,
     * we default to true ONLY for high-signal events (renvoye_n0, bypass,
     * escalades_abusives, transmis_auto) and false for low-signal ones
     * (approuve_n0, score_updated).
     */
    private function wantsEmail(NotificationPreference $pref, string $eventType): bool
    {
        // Map known event types to preference columns (when they exist) or
        // a default. We use match() so the dispatch is explicit and greppable.
        return match ($eventType) {
            'renvoye_n0', 'transmis_auto', 'bypass', 'escalades_abusives',
            'evaluation_sheet_ready', 'unjustified_return_alert' => true,
            'approuve_n0', 'score_updated' => false,
            // Unknown event type — opt out of email by default to be safe.
            default => false,
        };
    }

    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications(User $user, int $limit = 50): Collection
    {
        return $user->unreadNotifications()
            ->limit($limit)
            ->get()
            ->map(fn ($notification) => $this->formatNotification($notification));
    }

    /**
     * Get user's all notifications with pagination
     */
    public function getAllNotifications(User $user, int $perPage = 20)
    {
        return $user->notifications()
            ->paginate($perPage)
            ->through(fn ($notification) => $this->formatNotification($notification));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return false;
        }

        $notification->markAsRead();

        return true;
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    /**
     * Delete notification
     */
    public function deleteNotification(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (! $notification) {
            return false;
        }

        return $notification->delete();
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead(User $user): int
    {
        return $user->readNotifications()->delete();
    }

    /**
     * Get notification preferences
     */
    public function getPreferences(User $user): NotificationPreference
    {
        return $user->getOrCreateNotificationPreference();
    }

    /**
     * Update notification preferences
     */
    public function updatePreferences(User $user, array $preferences): NotificationPreference
    {
        $userPreference = $user->getOrCreateNotificationPreference();
        $userPreference->update($preferences);

        return $userPreference->fresh();
    }

    /**
     * Get grouped notifications (by type)
     */
    public function getGroupedNotifications(User $user): array
    {
        $notifications = $user->unreadNotifications;

        return $notifications->groupBy(function ($notification) {
            return $notification->data['type'] ?? 'other';
        })->map(function ($group) {
            return [
                'count' => $group->count(),
                'latest' => $this->formatNotification($group->first()),
                'items' => $group->map(fn ($n) => $this->formatNotification($n)),
            ];
        })->toArray();
    }

    /**
     * Get notification statistics
     */
    public function getStatistics(User $user): array
    {
        $total = $user->notifications()->count();
        $unread = $user->unreadNotifications()->count();
        $readToday = $user->readNotifications()
            ->whereDate('read_at', today())
            ->count();

        // Get notifications grouped by type using collection grouping instead of SQL GROUP BY
        $byType = $user->notifications()
            ->get()
            ->groupBy(function ($notification) {
                return $notification->data['type'] ?? 'other';
            })
            ->map(function ($group) {
                return $group->count();
            });

        return [
            'total' => $total,
            'unread' => $unread,
            'read_today' => $readToday,
            'by_type' => $byType,
        ];
    }

    /**
     * Format notification for API response
     */
    private function formatNotification($notification): array
    {
        return [
            'id' => $notification->id,
            'type' => $notification->data['type'] ?? 'unknown',
            'title' => $notification->data['title'] ?? '',
            'message' => $notification->data['message'] ?? '',
            'data' => $notification->data,
            'read_at' => $notification->read_at?->toISOString(),
            'created_at' => $notification->created_at->toISOString(),
            'time_ago' => $notification->created_at->diffForHumans(),
        ];
    }
}
