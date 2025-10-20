<?php

namespace App\Services;

use App\Models\User;
use App\Models\NotificationPreference;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class NotificationService
{
    /**
     * Get user's unread notifications
     */
    public function getUnreadNotifications(User $user, int $limit = 50): Collection
    {
        return $user->unreadNotifications()
            ->limit($limit)
            ->get()
            ->map(fn($notification) => $this->formatNotification($notification));
    }

    /**
     * Get user's all notifications with pagination
     */
    public function getAllNotifications(User $user, int $perPage = 20)
    {
        return $user->notifications()
            ->paginate($perPage)
            ->through(fn($notification) => $this->formatNotification($notification));
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(User $user, string $notificationId): bool
    {
        $notification = $user->notifications()->find($notificationId);

        if (!$notification) {
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

        if (!$notification) {
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
                'items' => $group->map(fn($n) => $this->formatNotification($n)),
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
