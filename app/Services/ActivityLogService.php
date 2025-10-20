<?php

namespace App\Services;

use Spatie\Activitylog\Models\Activity;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class ActivityLogService
{
    /**
     * Get activity logs for a subject (Project, Activity, Task)
     */
    public function getActivitiesForSubject(
        string $subjectType,
        int $subjectId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Activity::with(['causer', 'subject'])
            ->where('subject_type', $subjectType)
            ->where('subject_id', $subjectId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs caused by a user
     */
    public function getActivitiesByUser(
        int $userId,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Activity::with(['causer', 'subject'])
            ->where('causer_type', 'App\\Models\\User')
            ->where('causer_id', $userId)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity logs for multiple subjects (e.g., all tasks in a project)
     */
    public function getActivitiesForSubjects(
        string $subjectType,
        array $subjectIds,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Activity::with(['causer', 'subject'])
            ->where('subject_type', $subjectType)
            ->whereIn('subject_id', $subjectIds)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get recent activities across all entities
     */
    public function getRecentActivities(int $perPage = 20): LengthAwarePaginator
    {
        return Activity::with(['causer', 'subject'])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activities by log name (e.g., 'created', 'updated', 'deleted')
     */
    public function getActivitiesByLogName(
        string $logName,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Activity::with(['causer', 'subject'])
            ->where('log_name', $logName)
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activities for a date range
     */
    public function getActivitiesByDateRange(
        string $startDate,
        string $endDate,
        int $perPage = 20
    ): LengthAwarePaginator {
        return Activity::with(['causer', 'subject'])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Get activity feed for a user's dashboard
     * Includes activities on projects/tasks they are involved in
     */
    public function getUserActivityFeed(
        int $userId,
        array $projectIds = [],
        array $taskIds = [],
        int $perPage = 20
    ): LengthAwarePaginator {
        $query = Activity::with(['causer', 'subject'])
            ->where(function ($q) use ($userId, $projectIds, $taskIds) {
                // Activities caused by the user
                $q->where(function ($subQ) use ($userId) {
                    $subQ->where('causer_type', 'App\\Models\\User')
                        ->where('causer_id', $userId);
                });

                // Activities on projects they're involved in
                if (!empty($projectIds)) {
                    $q->orWhere(function ($subQ) use ($projectIds) {
                        $subQ->where('subject_type', 'App\\Models\\Projet')
                            ->whereIn('subject_id', $projectIds);
                    });
                }

                // Activities on tasks they're involved in
                if (!empty($taskIds)) {
                    $q->orWhere(function ($subQ) use ($taskIds) {
                        $subQ->where('subject_type', 'App\\Models\\Tache')
                            ->whereIn('subject_id', $taskIds);
                    });
                }
            });

        return $query->orderBy('created_at', 'desc')
            ->paginate($perPage);
    }

    /**
     * Format activity for display
     */
    public function formatActivity(Activity $activity): array
    {
        return [
            'id' => $activity->id,
            'log_name' => $activity->log_name,
            'description' => $activity->description,
            'subject_type' => $activity->subject_type,
            'subject_id' => $activity->subject_id,
            'subject' => $activity->subject,
            'causer_type' => $activity->causer_type,
            'causer_id' => $activity->causer_id,
            'causer' => $activity->causer,
            'properties' => $activity->properties,
            'created_at' => $activity->created_at,
            'human_readable' => $this->getHumanReadableDescription($activity),
        ];
    }

    /**
     * Get human-readable description of activity
     */
    protected function getHumanReadableDescription(Activity $activity): string
    {
        $causer = $activity->causer ? $activity->causer->name : 'Système';
        $subject = $this->getSubjectName($activity);

        switch ($activity->description) {
            case 'created':
                return "{$causer} a créé {$subject}";
            case 'updated':
                return "{$causer} a modifié {$subject}";
            case 'deleted':
                return "{$causer} a supprimé {$subject}";
            case 'assigned':
                return "{$causer} a assigné {$subject}";
            case 'completed':
                return "{$causer} a terminé {$subject}";
            case 'status_changed':
                return "{$causer} a changé le statut de {$subject}";
            case 'comment_added':
                return "{$causer} a commenté sur {$subject}";
            default:
                return "{$causer} a effectué une action sur {$subject}";
        }
    }

    /**
     * Get subject name for display
     */
    protected function getSubjectName(Activity $activity): string
    {
        if (!$activity->subject) {
            return 'un élément supprimé';
        }

        $type = class_basename($activity->subject_type);

        switch ($type) {
            case 'Projet':
                return "le projet \"{$activity->subject->nom}\"";
            case 'Activite':
                return "l'activité \"{$activity->subject->nom}\"";
            case 'Tache':
                return "la tâche \"{$activity->subject->titre}\"";
            case 'Comment':
                return "un commentaire";
            default:
                return "un {$type}";
        }
    }

    /**
     * Delete old activities
     */
    public function deleteOldActivities(int $daysOld = 90): int
    {
        $date = now()->subDays($daysOld);
        return Activity::where('created_at', '<', $date)->delete();
    }

    /**
     * Get activity statistics
     */
    public function getActivityStats(string $startDate, string $endDate): array
    {
        $activities = Activity::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'total' => $activities->count(),
            'by_type' => $activities->groupBy('description')->map->count(),
            'by_date' => $activities->groupBy(function ($activity) {
                return $activity->created_at->format('Y-m-d');
            })->map->count(),
            'by_user' => $activities->groupBy('causer_id')->map->count(),
        ];
    }
}
