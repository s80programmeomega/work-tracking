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
        $query = Tache::query()->with(['activite', 'assignees', 'validateur']);

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

        return $query->ordered()->get();
    }

    /**
     * Get tasks for a specific activity grouped by status (Kanban)
     */
    public function getKanbanForActivite(int $activiteId): array
    {
        $taches = Tache::forActivite($activiteId)
            ->with(['assignees', 'validateur'])
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
            ->with(['activite', 'assignees', 'validateur'])
            ->ordered()
            ->get();
    }

    /**
     * Create a new task
     */
    public function createTache(array $data): Tache
    {
        return DB::transaction(function () use ($data) {
            // Extract assignees
            $assigneeIds = $data['assignee_ids'] ?? [];
            unset($data['assignee_ids']);

            // Set default order if not provided
            if (!isset($data['ordre'])) {
                $maxOrdre = Tache::where('activite_id', $data['activite_id'])
                    ->where('statut', $data['statut'] ?? TacheStatut::A_FAIRE->value)
                    ->max('ordre');
                $data['ordre'] = ($maxOrdre ?? -1) + 1;
            }

            // Create task
            $tache = Tache::create($data);

            // Attach assignees
            if (!empty($assigneeIds)) {
                $tache->assignees()->attach($assigneeIds);
            }

            return $tache->load(['activite', 'assignees', 'validateur']);
        });
    }

    /**
     * Update a task
     */
    public function updateTache(Tache $tache, array $data): Tache
    {
        return DB::transaction(function () use ($tache, $data) {
            // Extract assignees if provided
            $assigneeIds = $data['assignee_ids'] ?? null;
            unset($data['assignee_ids']);

            // Update task
            $tache->update($data);

            // Sync assignees if provided
            if ($assigneeIds !== null) {
                $tache->assignees()->sync($assigneeIds);
            }

            return $tache->fresh(['activite', 'assignees', 'validateur']);
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
    public function moveTache(Tache $tache, TacheStatut $newStatut, int $newOrdre): Tache
    {
        return DB::transaction(function () use ($tache, $newStatut, $newOrdre) {
            $oldStatut = $tache->statut;
            $oldOrdre = $tache->ordre;

            // If moving to same status, just reorder
            if ($oldStatut === $newStatut) {
                $this->reorderTachesInStatus($tache->activite_id, $newStatut, $oldOrdre, $newOrdre);
            } else {
                // Moving to different status
                // Adjust order in old status
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $oldStatut)
                    ->where('ordre', '>', $oldOrdre)
                    ->decrement('ordre');

                // Adjust order in new status
                Tache::forActivite($tache->activite_id)
                    ->where('statut', $newStatut)
                    ->where('ordre', '>=', $newOrdre)
                    ->increment('ordre');
            }

            // Update task
            $tache->update([
                'statut' => $newStatut,
                'ordre' => $newOrdre,
            ]);

            return $tache->fresh(['activite', 'assignees', 'validateur']);
        });
    }

    /**
     * Reorder tasks within the same status
     */
    protected function reorderTachesInStatus(int $activiteId, TacheStatut $statut, int $oldOrdre, int $newOrdre): void
    {
        if ($oldOrdre === $newOrdre) {
            return;
        }

        if ($oldOrdre < $newOrdre) {
            // Moving down: decrement tasks between old and new position
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('ordre', '>', $oldOrdre)
                ->where('ordre', '<=', $newOrdre)
                ->decrement('ordre');
        } else {
            // Moving up: increment tasks between new and old position
            Tache::forActivite($activiteId)
                ->where('statut', $statut)
                ->where('ordre', '>=', $newOrdre)
                ->where('ordre', '<', $oldOrdre)
                ->increment('ordre');
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

            // Set ordre to end of list
            $maxOrdre = Tache::where('activite_id', $tache->activite_id)
                ->where('statut', TacheStatut::A_FAIRE)
                ->max('ordre');
            $newTache->ordre = ($maxOrdre ?? -1) + 1;

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
        $tache->delete(); // Soft delete
        return $tache;
    }

    /**
     * Restore archived task
     */
    public function restoreTache(int $tacheId): Tache
    {
        $tache = Tache::withTrashed()->findOrFail($tacheId);
        $tache->restore();
        return $tache->load(['activite', 'assignees', 'validateur']);
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
