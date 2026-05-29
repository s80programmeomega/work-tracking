<?php

namespace App\Services;

use App\Models\Label;
use App\Models\Tache;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Collection as SupportCollection;
use Illuminate\Support\Facades\DB;

class LabelService
{
    /**
     * Get all global labels
     */
    public function getGlobalLabels(): Collection
    {
        return Label::global()->ordered()->get();
    }

    /**
     * Get labels for a specific project (global + project-specific)
     */
    public function getLabelsForProject(int $projetId): Collection
    {
        return Label::forProject($projetId)->ordered()->get();
    }

    /**
     * Get only project-specific labels
     */
    public function getProjectSpecificLabels(int $projetId): Collection
    {
        return Label::projectSpecific($projetId)->ordered()->get();
    }

    /**
     * Create a new label
     */
    public function createLabel(array $data): Label
    {
        // Set creator
        $data['created_by'] = $data['created_by'] ?? auth()->id();

        // If projet_id is provided, it's a project-specific label
        if (isset($data['projet_id'])) {
            $data['is_global'] = false;
        } else {
            $data['is_global'] = true;
        }

        return Label::create($data);
    }

    /**
     * Update a label
     */
    public function updateLabel(Label $label, array $data): Label
    {
        // Don't allow changing is_global or projet_id after creation
        unset($data['is_global'], $data['projet_id']);

        $label->update($data);

        return $label->fresh();
    }

    /**
     * Delete a label
     */
    public function deleteLabel(Label $label): bool
    {
        // Detach from all tasks first
        $label->taches()->detach();

        return $label->delete();
    }

    /**
     * Attach labels to a task
     */
    public function attachLabelsToTask(Tache $tache, array $labelIds): void
    {
        DB::transaction(function () use ($tache, $labelIds) {
            // Get current label IDs
            $currentLabelIds = $tache->labels()->pluck('labels.id')->toArray();

            // Find labels to add and remove
            $labelsToAdd = array_diff($labelIds, $currentLabelIds);
            $labelsToRemove = array_diff($currentLabelIds, $labelIds);

            // Increment usage for new labels
            Label::whereIn('id', $labelsToAdd)->each(function ($label) {
                $label->incrementUsage();
            });

            // Decrement usage for removed labels
            Label::whereIn('id', $labelsToRemove)->each(function ($label) {
                $label->decrementUsage();
            });

            // Sync labels
            $tache->labels()->sync($labelIds);
        });
    }

    /**
     * Detach all labels from a task
     */
    public function detachAllLabelsFromTask(Tache $tache): void
    {
        DB::transaction(function () use ($tache) {
            // Decrement usage for all current labels
            $tache->labels->each(function ($label) {
                $label->decrementUsage();
            });

            // Detach all labels
            $tache->labels()->detach();
        });
    }

    /**
     * Reorder labels
     */
    public function reorderLabels(array $labelOrders): void
    {
        DB::transaction(function () use ($labelOrders) {
            foreach ($labelOrders as $order) {
                Label::where('id', $order['id'])
                    ->update(['ordre' => $order['ordre']]);
            }
        });
    }

    /**
     * Get label usage statistics
     */
    public function getLabelUsageStats(?int $projetId = null): array
    {
        $query = Label::query();

        if ($projetId) {
            $query->forProject($projetId);
        }

        return $query->ordered()
            ->get()
            ->map(function ($label) {
                return [
                    'id' => $label->id,
                    'nom' => $label->nom,
                    'couleur' => $label->couleur,
                    'usage_count' => $label->usage_count,
                    'is_global' => $label->is_global,
                ];
            })
            ->sortByDesc('usage_count')
            ->values()
            ->toArray();
    }

    /**
     * Duplicate a label
     */
    public function duplicateLabel(Label $label, ?int $targetProjetId = null): Label
    {
        $newLabel = $label->replicate();
        $newLabel->nom = $label->nom.' (copie)';
        $newLabel->usage_count = 0;
        $newLabel->created_by = auth()->id();

        if ($targetProjetId) {
            $newLabel->projet_id = $targetProjetId;
            $newLabel->is_global = false;
        }

        $newLabel->save();

        return $newLabel;
    }

    /**
     * Bulk create labels from array
     */
    public function bulkCreateLabels(array $labelsData, ?int $projetId = null): SupportCollection
    {
        $createdLabels = collect();

        DB::transaction(function () use ($labelsData, $projetId, &$createdLabels) {
            foreach ($labelsData as $labelData) {
                if ($projetId) {
                    $labelData['projet_id'] = $projetId;
                }

                $createdLabels->push($this->createLabel($labelData));
            }
        });

        return $createdLabels;
    }
}
