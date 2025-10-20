<?php

namespace App\Http\Controllers;

use App\Http\Resources\LabelTemplateResource;
use App\Models\LabelTemplate;
use App\Services\LabelTemplateService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\Validator;

class LabelTemplateController extends Controller
{
    public function __construct(
        protected LabelTemplateService $templateService
    ) {}

    /**
     * Display a listing of label templates.
     */
    public function index(Request $request): AnonymousResourceCollection
    {
        $type = $request->query('type');

        $templates = $type
            ? $this->templateService->getTemplatesByType($type)
            : $this->templateService->getAllTemplates();

        return LabelTemplateResource::collection($templates);
    }

    /**
     * Store a newly created label template.
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_workflow' => ['required', 'in:agile,kanban,waterfall,custom'],
            'is_default' => ['nullable', 'boolean'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.nom' => ['required', 'string', 'max:255'],
            'items.*.couleur' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = $this->templateService->createTemplate($validator->validated());

        return response()->json([
            'message' => 'Template créé avec succès.',
            'data' => new LabelTemplateResource($template),
        ], 201);
    }

    /**
     * Display the specified label template.
     */
    public function show(LabelTemplate $labelTemplate): JsonResponse
    {
        $labelTemplate->load('items', 'creator');

        return response()->json([
            'data' => new LabelTemplateResource($labelTemplate),
        ]);
    }

    /**
     * Update the specified label template.
     */
    public function update(Request $request, LabelTemplate $labelTemplate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type_workflow' => ['sometimes', 'in:agile,kanban,waterfall,custom'],
            'is_default' => ['nullable', 'boolean'],
            'items' => ['sometimes', 'array', 'min:1'],
            'items.*.nom' => ['required', 'string', 'max:255'],
            'items.*.couleur' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'items.*.description' => ['nullable', 'string'],
            'items.*.ordre' => ['nullable', 'integer', 'min:0'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $template = $this->templateService->updateTemplate($labelTemplate, $validator->validated());

        return response()->json([
            'message' => 'Template mis à jour avec succès.',
            'data' => new LabelTemplateResource($template),
        ]);
    }

    /**
     * Remove the specified label template.
     */
    public function destroy(LabelTemplate $labelTemplate): JsonResponse
    {
        $this->templateService->deleteTemplate($labelTemplate);

        return response()->json([
            'message' => 'Template supprimé avec succès.',
        ]);
    }

    /**
     * Apply a template to a project.
     */
    public function apply(Request $request, LabelTemplate $labelTemplate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'projet_id' => ['required', 'exists:projets,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $labels = $this->templateService->applyTemplateToProject(
            $labelTemplate,
            $validator->validated()['projet_id']
        );

        return response()->json([
            'message' => 'Template appliqué avec succès.',
            'data' => $labels,
        ]);
    }

    /**
     * Duplicate a template.
     */
    public function duplicate(Request $request, LabelTemplate $labelTemplate): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'nom' => ['nullable', 'string', 'max:255'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $validator->errors(),
            ], 422);
        }

        $newTemplate = $this->templateService->duplicateTemplate(
            $labelTemplate,
            $validator->validated()['nom'] ?? null
        );

        return response()->json([
            'message' => 'Template dupliqué avec succès.',
            'data' => new LabelTemplateResource($newTemplate),
        ], 201);
    }

    /**
     * Set a template as default.
     */
    public function setDefault(LabelTemplate $labelTemplate): JsonResponse
    {
        $template = $this->templateService->setAsDefault($labelTemplate);

        return response()->json([
            'message' => 'Template défini comme défaut avec succès.',
            'data' => new LabelTemplateResource($template),
        ]);
    }

    /**
     * Get the default template.
     */
    public function getDefault(): JsonResponse
    {
        $template = $this->templateService->getDefaultTemplate();

        if (!$template) {
            return response()->json([
                'message' => 'Aucun template par défaut trouvé.',
                'data' => null,
            ]);
        }

        return response()->json([
            'data' => new LabelTemplateResource($template),
        ]);
    }

    /**
     * Get predefined templates for setup.
     */
    public function predefined(): JsonResponse
    {
        $templates = $this->templateService->getPredefinedTemplates();

        return response()->json([
            'data' => $templates,
        ]);
    }
}
