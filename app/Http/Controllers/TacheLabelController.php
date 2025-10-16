<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelResource;
use App\Models\Tache;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class TacheLabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    /**
     * Get labels for a specific task.
     */
    public function index(Tache $tache): AnonymousResourceCollection
    {
        $labels = $tache->labels()->ordered()->get();

        return LabelResource::collection($labels);
    }

    /**
     * Attach/sync labels to a task.
     */
    public function sync(Request $request, Tache $tache): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label_ids' => ['required', 'array'],
            'label_ids.*' => ['required', 'exists:labels,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->labelService->attachLabelsToTask($tache, $validator->validated()['label_ids']);

        $tache->load('labels');

        return response()->json([
            'message' => 'Labels mis à jour avec succès.',
            'data' => LabelResource::collection($tache->labels),
        ]);
    }

    /**
     * Detach all labels from a task.
     */
    public function detachAll(Tache $tache): JsonResponse
    {
        $this->labelService->detachAllLabelsFromTask($tache);

        return response()->json([
            'message' => 'Tous les labels ont été supprimés avec succès.',
        ]);
    }

    /**
     * Attach a single label to a task.
     */
    public function attach(Request $request, Tache $tache): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label_id' => ['required', 'exists:labels,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get current label IDs
        $currentLabelIds = $tache->labels()->pluck('labels.id')->toArray();
        $newLabelId = $validator->validated()['label_id'];

        // Check if label is already attached
        if (in_array($newLabelId, $currentLabelIds)) {
            return response()->json([
                'message' => 'Ce label est déjà attaché à cette tâche.',
            ], 400);
        }

        // Add the new label to the list
        $currentLabelIds[] = $newLabelId;

        $this->labelService->attachLabelsToTask($tache, $currentLabelIds);

        $tache->load('labels');

        return response()->json([
            'message' => 'Label ajouté avec succès.',
            'data' => LabelResource::collection($tache->labels),
        ]);
    }

    /**
     * Detach a single label from a task.
     */
    public function detach(Request $request, Tache $tache): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'label_id' => ['required', 'exists:labels,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Get current label IDs
        $currentLabelIds = $tache->labels()->pluck('labels.id')->toArray();
        $labelToRemove = $validator->validated()['label_id'];

        // Remove the label from the list
        $newLabelIds = array_diff($currentLabelIds, [$labelToRemove]);

        $this->labelService->attachLabelsToTask($tache, $newLabelIds);

        $tache->load('labels');

        return response()->json([
            'message' => 'Label supprimé avec succès.',
            'data' => LabelResource::collection($tache->labels),
        ]);
    }
}
