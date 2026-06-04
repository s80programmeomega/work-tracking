<?php

namespace App\Http\Controllers;

use App\Http\Resources\ActivityResource;
use App\Services\ActivityLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Spatie\Activitylog\Models\Activity;

class ActivityController extends Controller
{
    protected ActivityLogService $activityLogService;

    public function __construct(ActivityLogService $activityLogService)
    {
        $this->activityLogService = $activityLogService;
    }

    /**
     * Get activities for a specific subject (Project, Activity, Task)
     */
    public function forSubject(Request $request): JsonResponse
    {
        $request->validate([
            'subject_type' => 'required|string',
            'subject_id' => 'required|integer',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getActivitiesForSubject(
            $request->subject_type,
            $request->subject_id,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activities by a specific user
     */
    public function byUser(Request $request): JsonResponse
    {
        $request->validate([
            'user_id' => 'required|integer|exists:users,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getActivitiesByUser(
            $request->user_id,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activity feed for authenticated user's dashboard
     */
    public function feed(Request $request): JsonResponse
    {
        $request->validate([
            'project_ids' => 'sometimes|array',
            'project_ids.*' => 'integer|exists:projets,id',
            'task_ids' => 'sometimes|array',
            'task_ids.*' => 'integer|exists:taches,id',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getUserActivityFeed(
            $request->user()->id,
            $request->get('project_ids', []),
            $request->get('task_ids', []),
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get recent activities across all entities
     */
    public function recent(Request $request): JsonResponse
    {
        $request->validate([
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getRecentActivities(
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activities by log name (created, updated, deleted, etc.)
     */
    public function byLogName(Request $request): JsonResponse
    {
        $request->validate([
            'log_name' => 'required|string',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getActivitiesByLogName(
            $request->log_name,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activities by date range
     */
    public function byDateRange(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $activities = $this->activityLogService->getActivitiesByDateRange(
            $request->start_date,
            $request->end_date,
            $request->get('per_page', 20)
        );

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Journal d'activité cross-app pour le super-admin.
     * Filtre par auteur, type de sujet, événement, plage de dates et texte libre.
     */
    public function adminFeed(Request $request): JsonResponse
    {
        $request->validate([
            'search' => 'sometimes|string|max:255',
            'causer_id' => 'sometimes|integer|exists:users,id',
            'subject_type' => 'sometimes|string',
            'event' => 'sometimes|string',
            'date_from' => 'sometimes|date',
            'date_to' => 'sometimes|date|after_or_equal:date_from',
            'per_page' => 'sometimes|integer|min:1|max:100',
        ]);

        $query = Activity::query()
            ->with(['causer', 'subject'])
            ->latest();

        if ($request->filled('causer_id')) {
            $query->where('causer_id', $request->causer_id)
                ->where('causer_type', 'App\\Models\\User');
        }

        if ($request->filled('subject_type')) {
            // Accepte le nom court (ex: "Tache") ou le FQCN
            $type = str_contains($request->subject_type, '\\')
                ? $request->subject_type
                : 'App\\Models\\'.$request->subject_type;
            $query->where('subject_type', $type);
        }

        if ($request->filled('event')) {
            $query->where('event', $request->event);
        }

        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }

        if ($request->filled('search')) {
            $q = $request->search;
            $query->where(function ($sub) use ($q) {
                $sub->where('description', 'like', "%{$q}%")
                    ->orWhere('log_name', 'like', "%{$q}%");
            });
        }

        $activities = $query->paginate($request->integer('per_page', 25));

        return response()->json([
            'success' => true,
            'data' => ActivityResource::collection($activities),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'last_page' => $activities->lastPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ],
        ]);
    }

    /**
     * Get activity statistics for a date range
     */
    public function stats(Request $request): JsonResponse
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $stats = $this->activityLogService->getActivityStats(
            $request->start_date,
            $request->end_date
        );

        return response()->json([
            'success' => true,
            'data' => $stats,
        ]);
    }
}
