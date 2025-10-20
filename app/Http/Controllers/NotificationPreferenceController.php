<?php

namespace App\Http\Controllers;

use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class NotificationPreferenceController extends Controller
{
    public function __construct(
        protected NotificationService $notificationService
    ) {
    }

    /**
     * Get user's notification preferences
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();
        $preferences = $this->notificationService->getPreferences($user);

        return response()->json([
            'success' => true,
            'data' => $preferences,
        ]);
    }

    /**
     * Update notification preferences
     */
    public function update(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'in_app_enabled' => 'sometimes|boolean',
            'email_enabled' => 'sometimes|boolean',
            'push_enabled' => 'sometimes|boolean',
            'task_assigned_in_app' => 'sometimes|boolean',
            'task_assigned_email' => 'sometimes|boolean',
            'task_assigned_push' => 'sometimes|boolean',
            'task_due_soon_in_app' => 'sometimes|boolean',
            'task_due_soon_email' => 'sometimes|boolean',
            'task_due_soon_push' => 'sometimes|boolean',
            'task_completed_in_app' => 'sometimes|boolean',
            'task_completed_email' => 'sometimes|boolean',
            'task_completed_push' => 'sometimes|boolean',
            'mentioned_in_comment_in_app' => 'sometimes|boolean',
            'mentioned_in_comment_email' => 'sometimes|boolean',
            'mentioned_in_comment_push' => 'sometimes|boolean',
            'comment_added_in_app' => 'sometimes|boolean',
            'comment_added_email' => 'sometimes|boolean',
            'comment_added_push' => 'sometimes|boolean',
            'project_updated_in_app' => 'sometimes|boolean',
            'project_updated_email' => 'sometimes|boolean',
            'project_updated_push' => 'sometimes|boolean',
            'deadline_approaching_in_app' => 'sometimes|boolean',
            'deadline_approaching_email' => 'sometimes|boolean',
            'deadline_approaching_push' => 'sometimes|boolean',
            'document_uploaded_in_app' => 'sometimes|boolean',
            'document_uploaded_email' => 'sometimes|boolean',
            'document_uploaded_push' => 'sometimes|boolean',
            'digest_frequency' => 'sometimes|in:none,daily,weekly',
            'digest_time' => 'sometimes|date_format:H:i:s',
            'digest_day_of_week' => 'sometimes|integer|between:1,7',
            'quiet_hours_enabled' => 'sometimes|boolean',
            'quiet_hours_start' => 'sometimes|date_format:H:i:s',
            'quiet_hours_end' => 'sometimes|date_format:H:i:s',
        ]);

        $user = $request->user();
        $preferences = $this->notificationService->updatePreferences($user, $validated);

        return response()->json([
            'success' => true,
            'message' => 'Préférences mises à jour avec succès',
            'data' => $preferences,
        ]);
    }
}
