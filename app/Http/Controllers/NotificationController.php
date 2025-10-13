<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Get user's unread notifications
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $limit = $request->input('limit', 50);

        $notifications = $this->notificationService->getUnreadNotifications($user, $limit);

        return response()->json([
            'success' => true,
            'data' => $notifications,
            'count' => $notifications->count(),
        ]);
    }

    /**
     * Get all notifications with pagination
     */
    public function all(Request $request): JsonResponse
    {
        $user = $request->user();
        $perPage = $request->input('per_page', 20);

        $notifications = $this->notificationService->getAllNotifications($user, $perPage);

        return response()->json([
            'success' => true,
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'last_page' => $notifications->lastPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    /**
     * Get grouped notifications
     */
    public function grouped(Request $request): JsonResponse
    {
        $user = $request->user();
        $grouped = $this->notificationService->getGroupedNotifications($user);

        return response()->json([
            'success' => true,
            'data' => $grouped,
        ]);
    }

    /**
     * Mark notification as read
     */
    public function markAsRead(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->markAsRead($user, $id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marquée comme lue',
        ]);
    }

    /**
     * Mark all notifications as read
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->markAllAsRead($user);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) marquée(s) comme lues",
            'count' => $count,
        ]);
    }

    /**
     * Delete notification
     */
    public function destroy(Request $request, string $id): JsonResponse
    {
        $user = $request->user();
        $success = $this->notificationService->deleteNotification($user, $id);

        if (!$success) {
            return response()->json([
                'success' => false,
                'message' => 'Notification non trouvée',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification supprimée',
        ]);
    }

    /**
     * Delete all read notifications
     */
    public function deleteAllRead(Request $request): JsonResponse
    {
        $user = $request->user();
        $count = $this->notificationService->deleteAllRead($user);

        return response()->json([
            'success' => true,
            'message' => "{$count} notification(s) supprimée(s)",
            'count' => $count,
        ]);
    }

    /**
     * Get notification statistics
     */
    public function statistics(Request $request): JsonResponse
    {
        $user = $request->user();
        $stats = $this->notificationService->getStatistics($user);

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
