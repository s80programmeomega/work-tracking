<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelResource;
use App\Models\Label;
use App\Services\LabelService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class LabelController extends Controller
{
    public function __construct(
        protected LabelService $labelService
    ) {}

    /**
     * Display a listing of labels.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $projetId = $request->query('projet_id') ? (int) $request->query('projet_id') : null;
        $scope = $request->query('scope', 'all'); // all, global, project

        $labels = match ($scope) {
            'global' => $this->labelService->getGlobalLabels(),
            'project' => $projetId ? $this->labelService->getProjectSpecificLabels($projetId) : collect(),
            default => $projetId ? $this->labelService->getLabelsForProject($projetId) : $this->labelService->getGlobalLabels(),
        };

        $labels->load('creator')->loadCount('taches');

        return LabelResource::collection($labels);
    }

    /**
     * Store a newly created label.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'projet_id' => ['nullable', 'exists:projets,id'],
            'nom' => ['required', 'string', 'max:255'],
            'couleur' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $label = $this->labelService->createLabel($validator->validated());

        return response()->json([
            'message' => 'Label créé avec succès.',
            'data' => new LabelResource($label->load('creator')),
        ], 201);
    }

    /**
     * Display the specified label.
     */
    public function show(Label $label): JsonResponse
    {
        $label->loadCount('taches');

        return response()->json([
            'data' => new LabelResource($label),
        ]);
    }

    /**
     * Update the specified label.
     */
    public function update(Request $request, Label $label): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['sometimes', 'string', 'max:255'],
            'couleur' => ['sometimes', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'description' => ['nullable', 'string'],
            'ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $label = $this->labelService->updateLabel($label, $validator->validated());

        return response()->json([
            'message' => 'Label mis à jour avec succès.',
            'data' => new LabelResource($label->load('creator')),
        ]);
    }

    /**
     * Remove the specified label.
     */
    public function destroy(Label $label): JsonResponse
    {
        $this->labelService->deleteLabel($label);

        return response()->json([
            'message' => 'Label supprimé avec succès.',
        ]);
    }

    /**
     * Reorder labels.
     */
    public function reorder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'labels' => ['required', 'array'],
            'labels.*.id' => ['required', 'exists:labels,id'],
            'labels.*.ordre' => ['required', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $this->labelService->reorderLabels($validator->validated()['labels']);

        return response()->json([
            'message' => 'Labels réordonnés avec succès.',
        ]);
    }

    /**
     * Duplicate a label.
     */
    public function duplicate(Request $request, Label $label): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'projet_id' => ['nullable', 'exists:projets,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newLabel = $this->labelService->duplicateLabel(
            $label,
            $validator->validated()['projet_id'] ?? null
        );

        return response()->json([
            'message' => 'Label dupliqué avec succès.',
            'data' => new LabelResource($newLabel->load('creator')),
        ], 201);
    }

    /**
     * Get label usage statistics.
     */
    public function stats(Request $request): JsonResponse
    {
        $projetId = $request->query('projet_id');
        $stats = $this->labelService->getLabelUsageStats($projetId);

        return response()->json([
            'data' => $stats,
        ]);
    }
}
