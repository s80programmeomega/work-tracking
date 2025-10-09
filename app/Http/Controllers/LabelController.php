<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelResource;
use App\Models\Label;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class LabelController extends Controller
{
    /**
     * Display a listing of labels.
     */
    public function index(): AnonymousResourceCollection
    {
        $labels = Label::ordered()->withCount('taches')->get();
        return LabelResource::collection($labels);
    }

    /**
     * Store a newly created label.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255', 'unique:labels,nom'],
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

        $label = Label::create($validator->validated());

        return response()->json([
            'message' => 'Label créé avec succès.',
            'data' => new LabelResource($label),
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
            'nom' => ['sometimes', 'string', 'max:255', 'unique:labels,nom,' . $label->id],
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

        $label->update($validator->validated());

        return response()->json([
            'message' => 'Label mis à jour avec succès.',
            'data' => new LabelResource($label),
        ]);
    }

    /**
     * Remove the specified label.
     */
    public function destroy(Label $label): JsonResponse
    {
        $label->delete();

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
            'ordered_ids' => ['required', 'array'],
            'ordered_ids.*' => ['required', 'exists:labels,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        foreach ($request->ordered_ids as $ordre => $id) {
            Label::where('id', $id)->update(['ordre' => $ordre]);
        }

        return response()->json([
            'message' => 'Labels réordonnés avec succès.',
        ]);
    }
}
