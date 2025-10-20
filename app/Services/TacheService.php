<?php

namespace App\Services;

use App\Enums\TacheStatut;
use App\Models\Tache;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class TacheService
{
    /**
     * Get all tasks with filters
     */
    public function getAllTaches(array $filters = []): Collection
    {
        $query = Tache::query()->with(['activite', 'assignees', 'validateur', 'labels']);

        if (isset($filters['activite_id'])) {
            $query->forActivite($filters['activite_id']);
        }

        if (isset($filters['statut'])) {
            $query->where('statut', $filters['statut']);
        }

        if (isset($filters['priorite'])) {
            $query->where('priorite', $filters['priorite']);
        }

        if (isset($filters['user_id'])) {
            $query->assignedTo($filters['user_id']);
        }

        if (isset($filters['overdue']) && $filters['overdue']) {
            $query->overdue();
        }

        // Filter by archive status
        if (isset($filters['archive_status'])) {
            if ($filters['archive_status'] === 'archived') {
                $query->archived();
            } else {
                $query->active();
            }
        } else {
            // Default: only show active tasks
            $query->active();
        }

        return $query->ordered()->get();
    }

    /**
     * Get tasks for a specific activity grouped by status (Kanban)
     */
    public function getKanbanForActivite(int $activiteId): array
    {
        $taches = Tache::forActivite($activiteId)
            ->active() // Only show active (non-archived) tasks
            ->with(['assignees', 'validateur', 'labels'])
            ->ordered()
            ->get();

        return [
            'a_faire' => $taches->where('statut', TacheStatut::A_FAIRE)->values(),
            'en_cours' => $taches->where('statut', TacheStatut::EN_COURS)->values(),
            'termine' => $taches->where('statut', TacheStatut::TERMINE)->values(),
        ];
    }

    /**
     * Get tasks assigned to current user
     */
    public function getMyTaches(int $userId): Collection
    {
        return Tache::assignedTo($userId)
            ->with(['activite', 'assignees', 'validateur', 'labels'])
            ->ordered()
            ->get();
    }

    /**
     * Create a new task
     */
    public function createTache(array $data): Tache
    {
        return DB::transaction(function () use ($data) {
            // Extract assignees and labels
            $assigneeIds = $data['assignee_ids'] ?? [];
            $labelIds = $data['label_ids'] ?? [];
            unset($data['assignee_ids'], $data['label_ids']);

            // Set default order if not provided
            if (!isset($data['position'])) {
                $maxPosition = Tache::where('activite_id', $data['activite_id'])
                    ->where('statut', $data['statut'] ?? TacheStatut::A_FAIRE->value)
                    ->max('position');
                $data['position'] = ($maxPosition ?? -1) + 1;
            }

            // Create task
            $tache = Tache::create($data);

            // Attach assignees
            if (!empty($assigneeIds)) {
                $tache->assignees()->attach($assigneeIds);
            }

            // Attach labels
            if (!empty($labelIds)) {
                $tache->labels()->attach($labelIds);
            }

            return $tache->load(['activite', 'assignees', 'validateur', 'labels']);
        });
    }

    /**
     * Update a task
     */
    public function updateTache(Tache $tache, array $data): Tache
    {
        return DB::transaction(function () use ($tache, $data) {
            // Extract assignees and labels if provided
            $assigneeIds = $data['assignee_ids'] ?? null;
            $labelIds = $data['label_ids'] ?? null;
            unset($data['assignee_ids'], $data['label_ids']);

            // Update task
            $tache->update($data);

            // Sync assignees if provided
            if ($assigneeIds !== null) {
                $tache->assignees()->sync($assigneeIds);
            }

            // Sync labels if provided
            if ($labelIds !== null) {
                $tache->labels()->sync($labelIds);
            }

            return $tache->fresh(['activite', 'assignees', 'validateur', 'labels']);
        });
    }

    /**
     * Delete a task
     */
    public function deleteTache(Tache $tache): void
    {
        $tache->delete();
    }

    /**
     * Move task to a different status (Kanban)
     */
    public function moveTache(Tache $tache, TacheStatut $newStatut, int $newPosition): Tache
    {
        return DB::transaction(function () use ($tache, $newStatut, $newPosition) {
            $oldStatut = $tache->statut;
            $oldPosition = $tache->position;

            // If moving to same status, just reorder
            if ($oldStatut === $newStatut) {
                $this->reorderTachesInStatus($tache->activite_id, $newStatut, $oldPosition, $newPosition);
            } else {
                // Moving to different status
                // Adjust order in old status
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $oldStatut)
                    ->where('position', '>', $oldPosition)
                    ->decrement('position');

                // Adjust order in new status
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $newStatut)
                    ->where('position', '>=', $newPosition)
                    ->increment('position');
            }

            // Update task
            $tache->update([
                'statut' => $newStatut,
                'position' => $newPosition,
            ]);

            return $tache->fresh(['activite', 'assignees', 'validateur']);
        });
    }

    /**
     * Reorder tasks within the same status
     */
    protected function reorderTachesInStatus(int $activiteId, TacheStatut $statut, int $oldPosition, int $newPosition): void
    {
        if ($oldPosition === $newPosition) {
            return;
        }

        if ($oldPosition < $newPosition) {
            // Moving down: decrement tasks between old and new position
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('position', '>', $oldPosition)
                ->where('position', '<=', $newPosition)
                ->decrement('position');
        } else {
            // Moving up: increment tasks between new and old position
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('position', '>=', $newPosition)
                ->where('position', '<', $oldPosition)
                ->increment('position');
        }
    }

    /**
     * Duplicate a task
     */
    public function duplicateTache(Tache $tache): Tache
    {
        return DB::transaction(function () use ($tache) {
            $newTache = $tache->replicate([
                'validation_superieur',
                'validateur_id',
            ]);

            $newTache->titre = $tache->titre . ' (Copie)';
            $newTache->taux_realisation = 0;
            $newTache->statut = TacheStatut::A_FAIRE;

            // Set position to end of list
            $maxPosition = Tache::where('activite_id', $tache->activite_id)
                ->where('statut', TacheStatut::A_FAIRE)
                ->max('position');
            $newTache->position = ($maxPosition ?? -1) + 1;

            $newTache->save();

            // Copy assignees
            $assigneeIds = $tache->assignees->pluck('id')->toArray();
            $newTache->assignees()->attach($assigneeIds);

            return $newTache->load(['activite', 'assignees', 'validateur']);
        });
    }

    /**
     * Archive a task
     */
    public function archiveTache(Tache $tache): Tache
    {
        $tache->archive();
        return $tache->load(['activite', 'assignees', 'validateur', 'labels']);
    }

    /**
     * Restore archived task
     */
    public function unarchiveTache(Tache $tache): Tache
    {
        $tache->unarchive();
        return $tache->load(['activite', 'assignees', 'validateur', 'labels']);
    }

    /**
     * Validate a task by superior
     */
    public function validateTache(Tache $tache, User $validator): Tache
    {
        $tache->validate($validator);
        return $tache->fresh(['activite', 'assignees', 'validateur']);
    }

    /**
     * Assign user to task
     */
    public function assignUser(Tache $tache, int $userId): Tache
    {
        if (!$tache->assignees->contains($userId)) {
            $tache->assignees()->attach($userId);
        }
        return $tache->fresh(['activite', 'assignees', 'validateur']);
    }

    /**
     * Unassign user from task
     */
    public function unassignUser(Tache $tache, int $userId): Tache
    {
        $tache->assignees()->detach($userId);
        return $tache->fresh(['activite', 'assignees', 'validateur']);
    }

    /**
     * Update task progress
     */
    public function updateProgress(Tache $tache, int $taux): Tache
    {
        $tache->updateProgress($taux);
        return $tache->fresh(['activite', 'assignees', 'validateur']);
    }
}
