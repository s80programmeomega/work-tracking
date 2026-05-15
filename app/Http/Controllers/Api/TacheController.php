<?php

namespace App\Http\Controllers\Api;

use App\Enums\TacheStatut;
use App\Http\Controllers\Controller;
use App\Http\Resources\SousTacheResource;
use App\Http\Resources\TacheAttachmentResource;
use App\Http\Resources\TacheResource;
use App\Http\Resources\TacheResultatResource;
use App\Models\Activite;
use App\Models\Document;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\TacheAttachment;
use App\Models\TacheExternalLink;
use App\Models\TacheResultat;
use App\Models\User;
use App\Notifications\ResultatIndividuelSoumisNotification;
use App\Services\PermissionService;
use App\Services\TacheService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpFoundation\Response;

class TacheController extends Controller
{
    public function __construct(
        protected TacheService $tacheService,
        protected PermissionService $permissionService,
    ) {
        $this->middleware('auth:sanctum');
    }

    /**
     * Liste toutes les tâches avec filtres
     */
    public function index(Request $request): JsonResponse
    {
        // All authenticated users can list tasks (filtered by access);

        $filters = $request->only([
            'activite_id',
            'statut',
            'priorite',
            'user_id',
            'overdue',
            'archive_status',
            'week_number',
            'year',
            'validation_status',
        ]);

        $taches = $this->tacheService->getAllTaches($request->user(), $filters);

        return response()->json([
            'data' => TacheResource::collection($taches),
            'meta' => [
                'total' => $taches->count(),
                'filtered' => count($filters) > 0,
            ],
        ]);
    }

    /**
     * ✅ CORRIGÉ : Kanban pour une activité avec logs détaillés
     */
    public function forActivite(Request $request, int $activiteId): JsonResponse
    {
        try {
            $activite = Activite::with(['projet', 'membres'])->findOrFail($activiteId);
            $user = $request->user();

            Log::info('Chargement Kanban', [
                'activite_id' => $activiteId,
                'user_id' => $user->id,
                'is_super_admin' => $user->isSuperAdmin(),
            ]);

            // ✅ Vérification d'accès simplifiée
            if (! $user->isSuperAdmin() && ! $this->canUserAccessActivite($user, $activite)) {
                Log::warning('Accès refusé au kanban', [
                    'user_id' => $user->id,
                    'activite_id' => $activiteId,
                ]);

                return response()->json([
                    'message' => 'Accès non autorisé',
                    'a_faire' => [],
                    'en_cours' => [],
                    'termine' => [],
                    'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0],
                ], 403);
            }

            // ✅ Récupérer le kanban
            $kanban = $this->tacheService->getKanbanForActivite($activiteId);

            // ✅ GARANTIR la structure complète
            $kanbanData = [
                'a_faire' => $kanban['a_faire'] ?? [],
                'en_cours' => $kanban['en_cours'] ?? [],
                'termine' => $kanban['termine'] ?? [],
            ];

            // ✅ Calculer les stats
            $stats = [
                'total' => count($kanbanData['a_faire']) + count($kanbanData['en_cours']) + count($kanbanData['termine']),
                'a_faire' => count($kanbanData['a_faire']),
                'en_cours' => count($kanbanData['en_cours']),
                'termine' => count($kanbanData['termine']),
            ];

            Log::info('Kanban chargé avec succès', [
                'activite_id' => $activiteId,
                'stats' => $stats,
            ]);

            return response()->json([
                'a_faire' => TacheResource::collection($kanbanData['a_faire']),
                'en_cours' => TacheResource::collection($kanbanData['en_cours']),
                'termine' => TacheResource::collection($kanbanData['termine']),
                'stats' => $stats,
            ]);

        } catch (ModelNotFoundException $e) {
            Log::error('Activité non trouvée', ['activite_id' => $activiteId]);

            return response()->json([
                'message' => 'Activité non trouvée',
                'a_faire' => [],
                'en_cours' => [],
                'termine' => [],
                'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0],
            ], 404);
        } catch (\Exception $e) {
            Log::error('Erreur chargement Kanban', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du chargement du kanban',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
                'a_faire' => [],
                'en_cours' => [],
                'termine' => [],
                'stats' => ['total' => 0, 'a_faire' => 0, 'en_cours' => 0, 'termine' => 0],
            ], 500);
        }
    }

    /**
     * ✅ Endpoint pour récupérer MES tâches en tant que RESPONSABLE
     */
    public function myTasksAsResponsable(Request $request): JsonResponse
    {
        $user = $request->user();

        try {
            // Récupérer toutes les tâches où je suis responsable
            $taches = Tache::where('responsable_id', $user->id)
                ->with([
                    'activite:id,nom,code,projet_id',
                    'activite.projet:id,nom',
                    'assignees:id,nom,email,avatar',
                    'labels:id,nom,couleur',
                    'responsable:id,nom,email,avatar',
                ])
                ->active() // Seulement les tâches actives (non archivées)
                ->orderBy('created_at', 'desc')
                ->get();

            Log::info('✅ Tâches en responsabilité récupérées', [
                'user_id' => $user->id,
                'total' => $taches->count(),
            ]);

            return response()->json([
                'message' => 'Tâches en responsabilité récupérées avec succès',
                'data' => TacheResource::collection($taches),
                'meta' => [
                    'total' => $taches->count(),
                    'a_faire' => $taches->where('statut', 'a_faire')->count(),
                    'en_cours' => $taches->where('statut', 'en_cours')->count(),
                    'termine' => $taches->where('statut', 'termine')->count(),
                ],
            ]);

        } catch (\Exception $e) {
            Log::error('❌ Erreur récupération tâches responsable', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la récupération des tâches',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * ✅ Mes tâches (assignées à moi)
     */
    public function myTasks(Request $request): JsonResponse
    {
        $filters = $request->only(['week_number', 'year', 'activite_id']);
        $filters['user_id'] = $request->user()->id;

        $taches = $this->tacheService->getAllTaches($request->user(), $filters);

        return response()->json([
            'data' => TacheResource::collection($taches),
        ]);
    }

    /**
     * ✅ Tâches assignées à moi
     */
    // Dans votre TacheController
    public function assignedToMe(Request $request)
    {
        $user = $request->user();

        $taches = Tache::with([
            'activite.projet',
            'assignees',
            'resultatsIndividuels.user',
            'labels',
            'sousTaches',
            'attachments',
            'externalLinks',
        ])
            ->assignedTo($user->id)
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'success' => true,
            'data' => TacheResource::collection($taches),
        ]);
    }

    /**
     * ✅ Tâches en attente de validation (que JE peux valider)
     */
    public function pending(Request $request): JsonResponse
    {
        $user = $request->user();

        $with = ['activite.projet', 'assignees', 'labels', 'validatedN1By', 'validatedN2By'];

        if ($user->isSuperAdmin()) {
            $pendingN1 = Tache::pendingValidationN1()->with($with)->get();
            $pendingN2 = Tache::pendingValidationN2()->with($with)->get();
        } else {
            // N1: activités où l'utilisateur est responsable ou validateur
            $pendingN1 = Tache::pendingValidationN1()
                ->whereHas('activite', function ($q) use ($user) {
                    $q->where('responsable_id', $user->id)
                        ->orWhereHas('membres', function ($mq) use ($user) {
                            $mq->where('user_id', $user->id)
                                ->where('can_validate_results', true);
                        });
                })
                ->with($with)
                ->get()
                ->filter(fn ($tache) => Gate::allows('validateN1', $tache));

            // N2: projets dont l'utilisateur est responsable
            $pendingN2 = Tache::pendingValidationN2()
                ->whereHas('activite.projet', function ($q) use ($user) {
                    $q->where('responsable_id', $user->id);
                })
                ->with($with)
                ->get()
                ->filter(fn ($tache) => Gate::allows('validateN2', $tache));
        }

        return response()->json([
            'pending_n1' => TacheResource::collection($pendingN1),
            'pending_n2' => TacheResource::collection($pendingN2),
            'counts' => [
                'n1' => $pendingN1->count(),
                'n2' => $pendingN2->count(),
                'total' => $pendingN1->count() + $pendingN2->count(),
            ],
        ]);
    }

    /**
     * ✅ Tâches en retard
     */
    public function overdue(Request $request): JsonResponse
    {
        $user = $request->user();

        $query = Tache::overdue()->with(['activite.projet', 'labels', 'assignees'])->ordered();

        if (! $user->isSuperAdmin()) {
            $query->where(function ($q) use ($user) {
                $q->whereHas('assignees', function ($aq) use ($user) {
                    $aq->where('user_id', $user->id);
                })
                    ->orWhereHas('activite', function ($actq) use ($user) {
                        $actq->where('responsable_id', $user->id);
                    });
            });
        }

        $taches = $query->get();

        return response()->json([
            'data' => TacheResource::collection($taches),
            'count' => $taches->count(),
        ]);
    }

    /**
     * ✅ NOUVEAU : Helper pour vérifier l'accès à une activité
     */
    private function canUserAccessActivite(User $user, Activite $activite): bool
    {
        // Responsable de l'activité
        if ($activite->responsable_id === $user->id) {
            return true;
        }

        // Responsable du projet
        if ($activite->projet && $activite->projet->responsable_id === $user->id) {
            return true;
        }

        // Membre de l'activité
        if ($activite->membres()->where('user_id', $user->id)->exists()) {
            return true;
        }

        // Membre du workspace (owner/admin)
        if ($activite->projet && $activite->projet->workspace) {
            $workspace = $activite->projet->workspace;
            $member = $workspace->membres()->where('user_id', $user->id)->first();
            if ($member && in_array($member->pivot->role, ['owner', 'admin'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * ✅ Ajouter des fichiers à une tâche
     */
    public function addAttachments(Request $request, Tache $tache)
    {
        $this->authorize('update', $tache);

        $request->validate([
            'files.*' => 'required|file|max:10240|mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip,txt',
        ]);

        try {
            $uploadedFiles = [];

            if ($request->hasFile('files')) {
                foreach ($request->file('files') as $file) {
                    $attachment = $this->tacheService->uploadFile($tache, $file, $request->user());
                    $uploadedFiles[] = new TacheAttachmentResource($attachment);
                }
            }

            return response()->json([
                'message' => 'Fichiers ajoutés avec succès',
                'data' => $uploadedFiles,
            ], 201);

        } catch (\Exception $e) {
            Log::error('Erreur ajout fichiers', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'ajout des fichiers',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ Récupérer les fichiers d'une tâche
     */
    public function getAttachments(Tache $tache)
    {
        $this->authorize('view', $tache);

        $attachments = $tache->attachments()->with('uploadedBy')->get();

        return response()->json([
            'data' => TacheAttachmentResource::collection($attachments),
        ]);
    }

    /**
     * ✅ Télécharger un fichier attaché
     */
    public function downloadAttachment(Tache $tache, TacheAttachment $attachment): Response
    {
        $this->authorize('view', $tache);

        if ($attachment->tache_id !== $tache->id) {
            abort(404, 'Fichier non trouvé pour cette tâche');
        }

        if (! Storage::disk('uploads')->exists($attachment->file_path)) {
            abort(404, 'Fichier non trouvé sur le serveur');
        }

        return Storage::disk('uploads')->download(
            $attachment->file_path,
            $attachment->original_name
        );
    }

    /**
     * ✅ Ajouter un lien externe
     */
    public function addExternalLink(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'url' => 'required|url|max:500',
        ]);

        try {
            $link = TacheExternalLink::create([
                'tache_id' => $tache->id,
                'title' => $validated['title'],
                'url' => $validated['url'],
                'created_by' => $request->user()->id,
            ]);

            return response()->json([
                'message' => 'Lien ajouté avec succès',
                'data' => $link,
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de l\'ajout du lien',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Méthode helper pour valider et synchroniser responsable/assignés
     */
    protected function validateAndSyncResponsable(array &$validated): void
    {
        // Si un responsable est défini
        if (isset($validated['responsable_id']) && $validated['responsable_id']) {
            // S'assurer que assignee_ids existe et est un tableau
            if (! isset($validated['assignee_ids']) || ! is_array($validated['assignee_ids'])) {
                $validated['assignee_ids'] = [];
            }

            // Ajouter automatiquement le responsable aux assignés s'il n'y est pas
            if (! in_array($validated['responsable_id'], $validated['assignee_ids'])) {
                $validated['assignee_ids'][] = $validated['responsable_id'];

                Log::info('✅ Responsable automatiquement ajouté aux assignés', [
                    'responsable_id' => $validated['responsable_id'],
                    'assignee_ids' => $validated['assignee_ids'],
                ]);
            }
        }
    }

    /**
     * Créer une tâche - VERSION ULTRA CORRIGÉE
     */
    public function store(Request $request): JsonResponse
    {

        // Vérifier chaque fichier individuellement
        if ($request->hasFile('uploaded_files')) {
            foreach ($request->file('uploaded_files') as $index => $file) {
                Log::info("File {$index}", [
                    'name' => $file->getClientOriginalName(),
                    'mime' => $file->getMimeType(),
                    'size' => $file->getSize(),
                    'is_valid' => $file->isValid(),
                    'error' => $file->getError(),
                ]);
            }
        }

        if ($request->hasFile('cover_image')) {
            $cover = $request->file('cover_image');
        }

        // ✅ Validation stricte avec messages personnalisés
        $validated = $request->validate([
            'activite_id' => 'required|exists:activites,id',
            'responsable_id' => 'nullable|exists:users,id',
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'indicateurs_resultats' => 'nullable|string',
            'statut' => 'required|in:a_faire,en_cours,termine',
            'priorite' => 'required|in:faible,moyenne,elevee,critique',

            // ✅ Dates avec format flexible
            'echeance' => 'nullable|date_format:Y-m-d',
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin_reelle' => 'nullable|date_format:Y-m-d',

            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'actual_hours' => 'nullable|numeric|min:0|max:999.99',
            'taux_realisation' => 'nullable|integer|min:0|max:100',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'commentaire' => 'nullable|string',
            'visibility' => 'nullable|in:public,private,members_only',

            // ✅ Booléens
            'validation_n1_required' => 'nullable|boolean',
            'validation_n2_required' => 'nullable|boolean',

            // ✅ CORRECTION 1: Validation des fichiers uploadés
            'uploaded_files' => 'nullable|array',
            'uploaded_files.*' => [
                'file',
                'max:10240', // 10MB
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip',
            ],

            // ✅ CORRECTION 2: Validation de l'image de couverture
            'cover_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif',
                'max:2048', // 2MB
            ],

            // ✅ CORRECTION 3: Links en JSON ou array
            'external_links' => 'nullable|json',

            // ✅ Relations
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:labels,id',
        ], [
            'uploaded_files.*.file' => 'Chaque fichier doit être un fichier valide',
            'uploaded_files.*.max' => 'Les fichiers ne doivent pas dépasser 10 Mo',
            'uploaded_files.*.mimes' => 'Les fichiers doivent être : PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP',
            'cover_image.image' => 'L\'image de couverture doit être une image valide',
            'cover_image.mimes' => 'L\'image doit être au format JPEG, PNG, JPG ou GIF',
            'cover_image.max' => 'L\'image ne doit pas dépasser 2 Mo',
            'responsable_id.required' => 'Le responsable de la tâche est obligatoire',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas',
        ]);

        try {
            $activite = Activite::with(['projet.workspace', 'membres'])->findOrFail($validated['activite_id']);
            $user = $request->user();

            // ✅ Vérification des permissions
            $canCreate = $user->isSuperAdmin() || $activite->responsable_id === $user->id ||
                ($activite->projet && $activite->projet->responsable_id === $user->id) ||
                $activite->membres()
                    ->where('user_id', $user->id)
                    ->where(function ($query) {
                        $query->where('role', 'responsable')
                            ->orWhere('can_create_tasks', true);
                    })
                    ->exists();

            // Vérifier permissions workspace
            if (! $canCreate && $activite->projet && $activite->projet->workspace) {
                $workspace = $activite->projet->workspace;
                $workspaceMember = $workspace->membres()->where('user_id', $user->id)->first();
                if ($workspaceMember && in_array($workspaceMember->pivot->role, ['owner', 'admin'])) {
                    $canCreate = true;
                }
            }

            if (! $canCreate) {
                return response()->json([
                    'message' => 'Vous n\'avez pas la permission de créer des tâches pour cette activité.',
                ], 403);
            }

            // ✅ Valider et synchroniser responsable avec assignés
            $this->validateAndSyncResponsable($validated);

            // ✅ Préparer les données
            $data = $validated;

            // Convertir external_links de JSON string vers array
            if (isset($data['external_links']) && is_string($data['external_links'])) {
                $data['external_links'] = json_decode($data['external_links'], true) ?? [];
            } else {
                $data['external_links'] = [];
            }

            // ✅ Gérer les fichiers uploadés - ULTRA SÉCURISÉ
            $uploadedFiles = [];
            if ($request->hasFile('uploaded_files')) {
                $files = $request->file('uploaded_files');

                // S'assurer que c'est un tableau
                if (! is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    // ✅ Vérification stricte
                    if ($file && $file->isValid()) {
                        $uploadedFiles[] = $file;

                    }
                }
            }

            $data['uploaded_files'] = $uploadedFiles;

            // ✅ Gérer l'image de couverture
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image');
                if ($coverImage->isValid()) {
                    $coverImagePath = $coverImage->store('task-covers', 'uploads');
                    $data['cover_image'] = $coverImagePath;

                }
            }

            // ✅ Créer la tâche via service
            $tache = $this->tacheService->createTache($data, $user);

            return response()->json([
                'message' => 'Tâche créée avec succès.',
                'data' => new TacheResource($tache),
            ], 201);

        } catch (ValidationException $e) {

            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {

            return response()->json([
                'message' => 'Erreur lors de la création de la tâche',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ NOUVEAU : Endpoint pour vérifier les permissions d'une activité
     */
    public function checkPermissions(Request $request, int $activiteId): JsonResponse
    {
        try {
            $activite = Activite::with(['projet.workspace', 'membres'])->findOrFail($activiteId);
            $user = $request->user();

            $membre = $activite->membres()->where('user_id', $user->id)->first();

            // Vérifier les permissions workspace
            $workspaceRole = null;
            if ($activite->projet && $activite->projet->workspace) {
                $workspaceMember = $activite->projet->workspace->membres()->where('user_id', $user->id)->first();
                $workspaceRole = $workspaceMember ? $workspaceMember->pivot->role : null;
            }

            $permissions = [
                'can_view' => $this->canUserAccessActivite($user, $activite),
                'can_create_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($activite->projet && $activite->projet->responsable_id === $user->id) ||
                    ($membre && ($membre->pivot->role === 'responsable' || $membre->pivot->can_create_tasks)) ||
                    in_array($workspaceRole, ['owner', 'admin']),
                'can_edit_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_edit_tasks),
                'can_delete_tasks' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_delete_tasks),
                'can_validate_results' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($membre && $membre->pivot->can_validate_results),
                'can_manage_members' => $user->isSuperAdmin() ||
                    $activite->responsable_id === $user->id ||
                    ($activite->projet && $activite->projet->responsable_id === $user->id),
            ];

            return response()->json([
                'permissions' => $permissions,
                'user_role' => $membre ? $membre->pivot->role : 'non-membre',
                'workspace_role' => $workspaceRole,
                'is_responsable' => $activite->responsable_id === $user->id,
                'is_projet_responsable' => $activite->projet && $activite->projet->responsable_id === $user->id,
                'is_super_admin' => $user->isSuperAdmin(),
            ]);
        } catch (\Exception $e) {
            Log::error('Erreur vérification permissions', [
                'activite_id' => $activiteId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la vérification des permissions',
            ], 500);
        }
    }

    /**
     * Afficher les détails d'une tâche
     */
    public function show(Tache $tache)
    {
        try {
            $this->authorize('view', $tache);

            // Charger toutes les relations nécessaires
            $tache->load([
                'activite:id,nom,code,projet_id,responsable_id',
                'activite.projet:id,nom,workspace_id',
                'activite.projet.workspace:id,nom',
                'assignees:id,nom,email,avatar',
                'labels:id,nom,couleur',
                'attachments' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'attachments.uploadedBy:id,nom,avatar',
                'externalLinks' => function ($query) {
                    $query->orderBy('created_at', 'desc');
                },
                'externalLinks.createdBy:id,nom,avatar',
                'resultats' => function ($query) {
                    $query->with(['user:id,nom,avatar', 'validateurN1:id,nom', 'validateurN2:id,nom'])
                        ->orderBy('created_at', 'desc');
                },
                // 'comments' => function ($query) {
                //     $query->with('user:id,nom,avatar')
                //         ->orderBy('created_at', 'desc');
                // },
                // 'validateurN1:id,nom',
                // 'validateurN2:id,nom',
                'createdBy:id,nom,avatar',
            ]);

            // Ajouter des informations supplémentaires
            $additionalInfo = [
                // Statistiques de la tâche
                'stats' => [
                    'assignees_count' => $tache->assignees->count(),
                    'attachments_count' => $tache->attachments->count(),
                    'links_count' => $tache->externalLinks->count(),
                    // 'comments_count' => $tache->comments->count(),
                    'resultats_count' => $tache->resultats->count(),
                    'is_overdue' => $tache->is_overdue,
                    'days_until_due' => $tache->echeance ? now()->diffInDays($tache->echeance, false) : null,
                ],

                // Permissions de l'utilisateur actuel
                'permissions' => [
                    'can_update' => auth()->user()->can('update', $tache),
                    'can_delete' => auth()->user()->can('delete', $tache),
                    'can_validate_n1' => auth()->user()->can('validateN1', $tache),
                    'can_validate_n2' => auth()->user()->can('validateN2', $tache),
                    'can_add_attachments' => auth()->user()->can('addAttachments', $tache),
                    'can_add_links' => auth()->user()->can('addLinks', $tache),
                    'can_comment' => auth()->user()->can('comment', $tache),
                ],

                // Informations de navigation
                'breadcrumb' => [
                    'workspace' => [
                        'id' => $tache->activite->projet->workspace->id,
                        'nom' => $tache->activite->projet->workspace->nom,
                    ],
                    'projet' => [
                        'id' => $tache->activite->projet->id,
                        'nom' => $tache->activite->projet->nom,
                    ],
                    'activite' => [
                        'id' => $tache->activite->id,
                        'nom' => $tache->activite->nom,
                    ],
                ],
            ];

            return response()->json([
                'success' => true,
                'data' => $tache,
                'additional_info' => $additionalInfo,
            ]);

        } catch (AuthorizationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Vous n\'avez pas la permission de voir cette tâche.',
            ], 403);
        } catch (\Exception $e) {
            Log::error('Erreur lors de la récupération de la tâche', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Erreur lors de la récupération de la tâche.',
            ], 500);
        }
    }

    /**
     * Mettre à jour une tâche - VERSION COMPLÈTEMENT CORRIGÉE
     */
    public function update(Request $request, Tache $tache): JsonResponse
    {
        // ✅ Vérification des permissions
        $this->authorize('update', $tache);

        // ✅ Validation COMPLÈTE similaire à store
        $validated = $request->validate([
            'titre' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'objectif' => 'nullable|string',
            'indicateurs_resultats' => 'nullable|string',
            'statut' => 'sometimes|required|in:a_faire,en_cours,termine',
            'priorite' => 'sometimes|required|in:faible,moyenne,elevee,critique',
            // Dates
            'echeance' => 'nullable|date_format:Y-m-d',
            'date_debut' => 'nullable|date_format:Y-m-d',
            'date_fin_reelle' => 'nullable|date_format:Y-m-d',

            'taux_realisation' => 'sometimes|integer|min:0|max:100',
            'estimated_hours' => 'nullable|numeric|min:0|max:999.99',
            'actual_hours' => 'nullable|numeric|min:0|max:999.99',
            'couleur' => 'nullable|string|regex:/^#[0-9A-Fa-f]{6}$/',
            'commentaire' => 'nullable|string',
            'visibility' => 'nullable|in:public,private,members_only',

            // Booléens
            'validation_n1_required' => 'nullable|boolean',
            'validation_n2_required' => 'nullable|boolean',

            // ✅ CORRECTION: Même validation des fichiers que pour store
            'uploaded_files' => 'nullable|array',
            'uploaded_files.*' => [
                'file',
                'max:10240',
                'mimes:pdf,doc,docx,xls,xlsx,ppt,pptx,jpg,jpeg,png,gif,zip',
            ],

            // ✅ CORRECTION: Même validation de l'image
            'cover_image' => [
                'nullable',
                'file',
                'image',
                'mimes:jpeg,jpg,png,gif',
                'max:2048',
            ],

            // Links
            'external_links' => 'nullable|json',

            // Relations
            'responsable_id' => 'sometimes|required|exists:users,id',
            'assignee_ids' => 'nullable|array',
            'assignee_ids.*' => 'exists:users,id',
            'label_ids' => 'nullable|array',
            'label_ids.*' => 'exists:labels,id',
        ], [
            // ✅ CORRECTION: Messages d'erreur cohérents
            'uploaded_files.*.file' => 'Chaque fichier doit être un fichier valide',
            'uploaded_files.*.max' => 'Les fichiers ne doivent pas dépasser 10 Mo',
            'uploaded_files.*.mimes' => 'Les fichiers doivent être : PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, JPG, PNG, GIF, ZIP',
            'cover_image.image' => 'L\'image de couverture doit être une image valide',
            'cover_image.mimes' => 'L\'image doit être au format JPEG, PNG, JPG ou GIF',
            'cover_image.max' => 'L\'image ne doit pas dépasser 2 Mo',
        ]);

        try {
            Log::info('Début mise à jour tâche', [
                'tache_id' => $tache->id,
                'user_id' => $request->user()->id,
                'has_files' => $request->hasFile('uploaded_files'),
                'files_count' => $request->hasFile('uploaded_files') ? count($request->file('uploaded_files')) : 0,
                'has_cover' => $request->hasFile('cover_image'),
            ]);

            // ✅ Valider et synchroniser responsable avec assignés
            $this->validateAndSyncResponsable($validated);

            // ✅ Préparer les données de la même manière que store
            $data = $validated;

            // Convertir external_links
            if (isset($data['external_links']) && is_string($data['external_links'])) {
                $data['external_links'] = json_decode($data['external_links'], true) ?? [];
            } else {
                $data['external_links'] = [];
            }

            // ✅ Gérer les fichiers uploadés - MÊME LOGIQUE QUE STORE
            $uploadedFiles = [];
            if ($request->hasFile('uploaded_files')) {
                $files = $request->file('uploaded_files');

                // S'assurer que c'est un tableau
                if (! is_array($files)) {
                    $files = [$files];
                }

                foreach ($files as $file) {
                    if ($file && $file->isValid()) {
                        $uploadedFiles[] = $file;
                        Log::info('Fichier valide détecté pour mise à jour', [
                            'name' => $file->getClientOriginalName(),
                            'mime' => $file->getMimeType(),
                            'size' => $file->getSize(),
                        ]);
                    }
                }
            }

            $data['uploaded_files'] = $uploadedFiles;

            // ✅ Gérer l'image de couverture - MÊME LOGIQUE QUE STORE
            if ($request->hasFile('cover_image')) {
                $coverImage = $request->file('cover_image');
                if ($coverImage->isValid()) {
                    // Supprimer l'ancienne image si elle existe
                    if ($tache->cover_image && Storage::disk('uploads')->exists($tache->cover_image)) {
                        Storage::disk('uploads')->delete($tache->cover_image);
                        Log::info('Ancienne image de couverture supprimée', [
                            'old_path' => $tache->cover_image,
                        ]);
                    }

                    $coverImagePath = $coverImage->store('task-covers', 'uploads');
                    $data['cover_image'] = $coverImagePath;
                    Log::info('Nouvelle image de couverture uploadée', [
                        'path' => $coverImagePath,
                    ]);
                }
            }

            Log::info('Avant mise à jour tâche via service', [
                'tache_id' => $tache->id,
                'files_count' => count($uploadedFiles),
                'has_cover' => isset($data['cover_image']),
            ]);

            // ✅ Mettre à jour via service
            $tache = $this->tacheService->updateTache($tache, $data);

            Log::info('Tâche mise à jour avec succès', [
                'tache_id' => $tache->id,
                'files_processed' => count($uploadedFiles),
            ]);

            return response()->json([
                'message' => 'Tâche mise à jour avec succès.',
                'data' => new TacheResource($tache),
            ]);

        } catch (ValidationException $e) {
            Log::warning('Erreur validation mise à jour tâche', [
                'tache_id' => $tache->id,
                'errors' => $e->errors(),
            ]);

            return response()->json([
                'message' => 'Erreur de validation',
                'errors' => $e->errors(),
            ], 422);
        } catch (\InvalidArgumentException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('Erreur mise à jour tâche', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de la mise à jour de la tâche',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ NOUVEAU : Endpoint pour changer le responsable avec auto-sync
     */
    public function changeResponsable(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'responsable_id' => 'required|exists:users,id',
        ]);

        try {
            $newResponsableId = $validated['responsable_id'];
            $user = User::findOrFail($newResponsableId);

            // Vérifier que l'utilisateur a accès à l'activité
            if (! $tache->activite) {
                return response()->json([
                    'message' => 'La tâche doit appartenir à une activité',
                ], 422);
            }

            $isMember = $tache->activite->membres()->where('user_id', $user->id)->exists();
            $isProjetMember = $tache->activite->projet &&
                $tache->activite->projet->membres()->where('user_id', $user->id)->exists();

            if (! $isMember && ! $isProjetMember && ! $user->isSuperAdmin()) {
                return response()->json([
                    'message' => 'L\'utilisateur doit être membre de l\'activité ou du projet',
                ], 422);
            }

            // ✅ Mettre à jour le responsable
            $tache->update([
                'responsable_id' => $newResponsableId,
            ]);

            // ✅ S'assurer que le nouveau responsable est assigné
            if (! $tache->isAssignedTo($user)) {
                $tache->assignees()->attach($newResponsableId, [
                    'role' => 'responsable',
                    'can_edit' => true,
                    'can_complete' => true,
                    'can_validate' => true,
                ]);

                Log::info('✅ Nouveau responsable ajouté aux assignés', [
                    'tache_id' => $tache->id,
                    'responsable_id' => $newResponsableId,
                ]);
            }

            activity()
                ->causedBy($request->user())
                ->performedOn($tache)
                ->withProperties([
                    'responsable_id' => $newResponsableId,
                    'responsable_nom' => $user->nom,
                ])
                ->log('Responsable modifié');

            return response()->json([
                'message' => 'Responsable modifié avec succès',
                'data' => new TacheResource($tache->fresh(['responsable', 'activite', 'assignees', 'labels'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur changement responsable', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du changement de responsable',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ Supprimer un fichier attaché
     */
    public function deleteAttachment(Tache $tache, TacheAttachment $attachment): JsonResponse
    {
        $this->authorize('update', $tache);

        if ($attachment->tache_id !== $tache->id) {
            return response()->json([
                'message' => 'Fichier non trouvé pour cette tâche',
            ], 404);
        }

        try {
            $this->tacheService->deleteAttachment($attachment, auth()->user());

            return response()->json([
                'message' => 'Fichier supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du fichier',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ Supprimer un lien externe
     */
    public function deleteExternalLink(Tache $tache, TacheExternalLink $link): JsonResponse
    {
        $this->authorize('update', $tache);

        if ($link->tache_id !== $tache->id) {
            return response()->json([
                'message' => 'Lien non trouvé pour cette tâche',
            ], 404);
        }

        try {
            $this->tacheService->deleteExternalLink($link);

            return response()->json([
                'message' => 'Lien supprimé avec succès',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erreur lors de la suppression du lien',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * Supprimer une tâche
     */
    public function destroy(Tache $tache): JsonResponse
    {
        $this->authorize('delete', $tache);

        $this->tacheService->deleteTache($tache);

        return response()->json([
            'message' => 'Tâche supprimée avec succès.',
        ]);
    }

    /**
     * ✅ Marquer comme terminé
     */
    public function complete(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        try {
            $tache->markAsCompleted($request->user());

            return response()->json([
                'message' => 'Tâche marquée comme terminée. En attente de validation.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ NOUVEAU : Assigner un responsable à la tâche
     */
    public function assignResponsable(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'responsable_id' => 'required|exists:users,id',
        ]);

        try {
            // Vérifier que le responsable a accès à l'activité
            $user = User::findOrFail($validated['responsable_id']);

            if (! $tache->activite) {
                return response()->json([
                    'message' => 'La tâche doit appartenir à une activité',
                ], 422);
            }

            // Vérifier que l'utilisateur est membre de l'activité ou du projet
            $isMember = $tache->activite->membres()->where('user_id', $user->id)->exists();
            $isProjetMember = $tache->activite->projet &&
                $tache->activite->projet->membres()->where('user_id', $user->id)->exists();

            if (! $isMember && ! $isProjetMember && ! $user->isSuperAdmin()) {
                return response()->json([
                    'message' => 'L\'utilisateur doit être membre de l\'activité ou du projet',
                ], 422);
            }

            $tache->update([
                'responsable_id' => $validated['responsable_id'],
            ]);

            activity()
                ->causedBy($request->user())
                ->performedOn($tache)
                ->withProperties([
                    'responsable_id' => $validated['responsable_id'],
                    'responsable_nom' => $user->nom,
                ])
                ->log('Responsable assigné');

            return response()->json([
                'message' => 'Responsable assigné avec succès',
                'data' => new TacheResource($tache->fresh(['responsable', 'activite', 'assignees', 'labels'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur assignation responsable', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors de l\'assignation du responsable',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ NOUVEAU : Retirer le responsable d'une tâche
     */
    public function removeResponsable(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        try {
            $oldResponsable = $tache->responsable;

            $tache->update([
                'responsable_id' => null,
            ]);

            activity()
                ->causedBy($request->user())
                ->performedOn($tache)
                ->withProperties([
                    'old_responsable_id' => $oldResponsable?->id,
                    'old_responsable_nom' => $oldResponsable?->nom,
                ])
                ->log('Responsable retiré');

            return response()->json([
                'message' => 'Responsable retiré avec succès',
                'data' => new TacheResource($tache->fresh(['activite', 'assignees', 'labels'])),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur retrait responsable', [
                'tache_id' => $tache->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => 'Erreur lors du retrait du responsable',
                'error' => config('app.debug') ? $e->getMessage() : 'Une erreur est survenue',
            ], 500);
        }
    }

    /**
     * ✅ NOUVEAU : Mes tâches en tant que responsable
     */
    // public function myTasksAsResponsable(Request $request): JsonResponse
    // {
    //     $user = $request->user();

    //     $taches = Tache::with([
    //         'activite.projet',
    //         'assignees',
    //         'labels',
    //         'resultatsIndividuels',
    //         'responsable'
    //     ])
    //         ->responsableBy($user->id)
    //         ->active()
    //         ->ordered()
    //         ->get();

    //     return response()->json([
    //         'message' => 'Tâches dont vous êtes responsable',
    //         'data' => TacheResource::collection($taches),
    //         'count' => $taches->count(),
    //     ]);
    // }

    /**
     * ✅ Valider N1 (Responsable activité)
     */
    public function validateN1(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('validateN1', $tache);

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $tache->validateN1($request->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Tâche validée (N1) avec succès.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ Valider N2 (Responsable projet)
     */
    public function validateN2(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('validateN2', $tache);

        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        try {
            $tache->validateN2($request->user(), $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Tâche validée (N2) avec succès. Validation complète.',
                'data' => new TacheResource($tache->fresh()),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ Déplacer tâche (Kanban)
     */
    /**
     * ✅ Déplacer tâche (Kanban)
     */
    public function move(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'statut' => 'required|in:a_faire,en_cours,termine',
            'position' => 'required|integer|min:0',
        ]);

        $tache = $this->tacheService->moveTache(
            $tache,
            TacheStatut::from($validated['statut']),
            $validated['position']
        );

        return response()->json([
            'message' => 'Tâche déplacée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Archiver tâche
     */
    public function archive(Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $tache->archive();

        return response()->json([
            'message' => 'Tâche archivée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Désarchiver tâche
     */
    public function unarchive(Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $tache->unarchive();

        return response()->json([
            'message' => 'Tâche désarchivée avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Assigner utilisateur
     */
    public function assignUser(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('update', $tache);

        $validated = $request->validate([
            'user_id' => 'required|exists:users,id',
            'role' => 'nullable|in:assignee,validator,observer',
            'can_edit' => 'boolean',
            'can_complete' => 'boolean',
            'can_validate' => 'boolean',
        ]);

        $tache = $this->tacheService->assignUser($tache, $validated);

        return response()->json([
            'message' => 'Utilisateur assigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    /**
     * ✅ Désassigner utilisateur
     */
    public function unassignUser(Tache $tache, int $userId): JsonResponse
    {
        $this->authorize('update', $tache);

        $tache = $this->tacheService->unassignUser($tache, $userId);

        return response()->json([
            'message' => 'Utilisateur désassigné avec succès.',
            'data' => new TacheResource($tache),
        ]);
    }

    public function subTasks(Tache $tache): JsonResponse
    {
        $this->authorize('view', $tache);

        $sousTaches = $tache->sousTaches()
            ->with(['responsable'])
            ->ordered()
            ->get();

        return response()->json([
            'data' => SousTacheResource::collection($sousTaches),
        ]);
    }

    public function createSubTask(Request $request, Tache $tache): JsonResponse
    {
        $this->authorize('createSubtask', $tache);

        $validated = $request->validate([
            'titre' => 'required|string|max:255',
            'description' => 'nullable|string',
            'responsable_id' => 'nullable|exists:users,id',
            'date_echeance' => 'nullable|date|before_or_equal:'.optional($tache->echeance)->format('Y-m-d'),
            'poids' => 'nullable|integer|min:0|max:100',
            'ordre' => 'nullable|integer|min:0',
            'validation_n0_required' => 'boolean',
            'validation_n1_required' => 'boolean',
            'validation_n2_required' => 'boolean',
        ]);

        $poids = $validated['poids'] ?? 0;

        // R1: SousTache cannot be created on a task that is itself a SousTache
        // (enforced at data layer — Tache has no parent concept anymore)

        // R2: weight validation
        SousTache::enforceWeights($tache->id, $poids, $request->user()->id);

        $sousTache = SousTache::create(array_merge($validated, [
            'tache_id' => $tache->id,
            'poids' => $poids,
        ]));

        $sousTache->load('responsable');

        return response()->json([
            'message' => __('sous_taches.success.created'),
            'data' => new SousTacheResource($sousTache),
        ], 201);
    }

    /**
     * ✅ NOUVELLE VERSION CORRIGÉE : Rapport hebdomadaire avec statuts individuels
     *
     * Affiche les tâches où l'utilisateur :
     * - Est assigné
     * - N'a PAS terminé OU a terminé mais résultat pas complètement validé
     * - Avec son statut INDIVIDUEL (statut_individuel dans tache_user)
     */
    public function myWeeklyReport(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:1990',
        ]);

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;

        $user = $request->user();

        // ✅ Calculer les dates de début et fin de la semaine
        $weekStart = $this->getWeekStartDate($year, $weekNumber);
        $weekEnd = $this->getWeekEndDate($year, $weekNumber);

        Log::info('📋 Chargement fiche évaluation', [
            'user_id' => $user->id,
            'week' => $weekNumber,
            'year' => $year,
            'week_start' => $weekStart,
            'week_end' => $weekEnd,
        ]);

        // ✅ NOUVELLE LOGIQUE : Récupérer les tâches selon le statut individuel
        $taches = Tache::with([
            'activite.projet',
            'assignees' => function ($query) use ($user) {
                $query->where('user_id', $user->id)
                    ->withPivot([
                        'statut_individuel',
                        'progression_individuelle',
                        'started_at',
                        'completed_at',
                        'notes_personnelles',
                    ]);
            },
            'labels',
            'validatedN1By',
            'validatedN2By',
            'resultatsIndividuels' => function ($query) use ($user) {
                $query->where('user_id', $user->id);
            },
        ])
            ->whereHas('assignees', function ($query) use ($user) {
                $query->where('user_id', $user->id);
            })
            // ✅ Filtrer par semaine (tâches dont l'échéance tombe dans cette semaine ou avant)
            ->where(function ($q) use ($weekEnd) {
                $q->where('echeance', '<=', $weekEnd)
                    ->orWhereNull('echeance');
            })
            ->active()
            ->ordered()
            ->get();

        // ✅ FILTRAGE SELON LE PROCESSUS :
        // Afficher seulement si :
        // 1. Statut individuel != terminé
        // 2. OU statut individuel = terminé MAIS résultat pas complètement validé
        $tasksToDisplay = $taches->filter(function ($tache) use ($user) {
            $assignee = $tache->assignees->first();

            if (! $assignee) {
                return false;
            }

            $statutIndividuel = $assignee->pivot->statut_individuel;

            // 1️⃣ Si pas terminé individuellement → TOUJOURS afficher
            if ($statutIndividuel !== 'termine') {
                return true;
            }

            // 2️⃣ Si terminé individuellement → vérifier la validation du résultat
            $monResultat = $tache->monResultat($user);

            // Pas de résultat soumis → afficher
            if (! $monResultat || ! $monResultat->soumis_le) {
                return true;
            }

            // Vérifier si validation complète
            $validationComplete = false;

            if ($tache->validation_n2_required) {
                // N1 ET N2 requis → masquer seulement si les 2 sont validés
                $validationComplete = $monResultat->valide_par_n1 && $monResultat->valide_par_n2;
            } else {
                // Seulement N1 requis → masquer si N1 validé
                $validationComplete = $monResultat->valide_par_n1;
            }

            // ✅ Afficher si validation PAS complète
            return ! $validationComplete;
        });

        // ✅ Calculer les statistiques avec STATUTS INDIVIDUELS
        $stats = [
            'total' => $tasksToDisplay->count(),
            'a_faire' => $tasksToDisplay->filter(function ($t) {
                $assignee = $t->assignees->first();

                return $assignee && $assignee->pivot->statut_individuel === 'a_faire';
            })->count(),
            'en_cours' => $tasksToDisplay->filter(function ($t) {
                $assignee = $t->assignees->first();

                return $assignee && $assignee->pivot->statut_individuel === 'en_cours';
            })->count(),
            'termine' => $tasksToDisplay->filter(function ($t) {
                $assignee = $t->assignees->first();

                return $assignee && $assignee->pivot->statut_individuel === 'termine';
            })->count(),
            'avec_resultat' => $tasksToDisplay->filter(function ($t) {
                return $t->resultatsIndividuels->isNotEmpty() && $t->resultatsIndividuels->first()->soumis_le;
            })->count(),
            'valide_n1' => $tasksToDisplay->filter(function ($t) {
                $r = $t->resultatsIndividuels->first();

                return $r && $r->valide_par_n1;
            })->count(),
            'valide_n2' => $tasksToDisplay->filter(function ($t) {
                $r = $t->resultatsIndividuels->first();

                return $r && $r->valide_par_n2;
            })->count(),
            'en_retard' => $tasksToDisplay->filter(function ($t) {
                $assignee = $t->assignees->first();

                return $t->is_overdue &&
                    $assignee &&
                    $assignee->pivot->statut_individuel !== 'termine';
            })->count(),
            'estimated_hours' => $tasksToDisplay->sum(fn ($t) => (float) $t->estimated_hours),
            'actual_hours' => $tasksToDisplay->sum(fn ($t) => (float) $t->actual_hours),
        ];

        // ✅ Grouper par activité
        $byActivite = $tasksToDisplay->groupBy('activite_id')->map(function ($tasks, $activiteId) {
            $activite = $tasks->first()->activite;

            return [
                'activite' => [
                    'id' => $activite->id,
                    'nom' => $activite->nom,
                    'code' => $activite->code,
                    'projet_nom' => $activite->projet?->nom,
                ],
                'taches' => TacheResource::collection($tasks),
                'stats' => [
                    'total' => $tasks->count(),
                    'a_faire' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'a_faire';
                    })->count(),
                    'en_cours' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'en_cours';
                    })->count(),
                    'termine' => $tasks->filter(function ($t) {
                        $assignee = $t->assignees->first();

                        return $assignee && $assignee->pivot->statut_individuel === 'termine';
                    })->count(),
                ],
            ];
        })->values();

        Log::info('✅ Fiche évaluation chargée', [
            'user_id' => $user->id,
            'total_tasks' => $stats['total'],
            'activites' => $byActivite->count(),
        ]);

        return response()->json([
            'week_info' => [
                'week_number' => $weekNumber,
                'year' => $year,
                'start_date' => $weekStart,
                'end_date' => $weekEnd,
            ],
            'all_tasks' => TacheResource::collection($tasksToDisplay),
            'by_activite' => $byActivite,
            'stats' => $stats,
        ]);
    }

    /**
     * ✅ Helper pour obtenir la date de début de semaine (Lundi)
     */
    private function getWeekStartDate(int $year, int $week): string
    {
        $dto = new \DateTime;
        $dto->setISODate($year, $week);

        return $dto->format('Y-m-d');
    }

    /**
     * ✅ Helper pour obtenir la date de fin de semaine (Dimanche)
     */
    private function getWeekEndDate(int $year, int $week): string
    {
        $dto = new \DateTime;
        $dto->setISODate($year, $week, 7);

        return $dto->format('Y-m-d');
    }

    /**
     * ✅ Rapport hebdomadaire d'un utilisateur (managers)
     */
    public function userWeeklyReport(Request $request, int $userId): JsonResponse
    {
        $user = User::findOrFail($userId);

        // Vérifier permissions: doit être manager de l'utilisateur
        if (! $request->user()->isSuperAdmin()) {
            $hasAccess = Activite::where('responsable_id', $request->user()->id)
                ->whereHas('membres', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->exists();

            if (! $hasAccess) {
                return response()->json([
                    'message' => 'Vous n\'avez pas accès aux rapports de cet utilisateur',
                ], 403);
            }
        }

        $weekNumber = $request->input('week_number');
        $year = $request->input('year');

        $report = $this->tacheService->getWeeklyReport($user, $weekNumber, $year);

        return response()->json($report);
    }

    /**
     * ✅ NOUVEAU : Déplacer MA carte (statut individuel)
     */
    public function moveMyCard(Request $request, Tache $tache): JsonResponse
    {
        $validated = $request->validate([
            'statut' => 'required|in:a_faire,en_cours,termine',
            'progression' => 'nullable|integer|min:0|max:100',
            'notes_personnelles' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        try {
            // Vérifier assignation
            if (! $tache->isAssignedTo($user)) {
                return response()->json([
                    'message' => 'Vous n\'êtes pas assigné à cette tâche',
                ], 403);
            }

            // Mettre à jour le statut individuel
            $tache->updateStatutForUser(
                $user,
                $validated['statut'],
                $validated['progression'] ?? null
            );

            // Mettre à jour les notes si fournies
            if (isset($validated['notes_personnelles'])) {
                $tache->assignees()->updateExistingPivot($user->id, [
                    'notes_personnelles' => $validated['notes_personnelles'],
                ]);
            }

            Log::info('Statut individuel mis à jour', [
                'tache_id' => $tache->id,
                'user_id' => $user->id,
                'nouveau_statut' => $validated['statut'],
                'statut_global' => $tache->fresh()->statut->value,
            ]);

            return response()->json([
                'message' => 'Votre statut a été mis à jour avec succès',
                'data' => new TacheResource($tache->fresh([
                    'activite',
                    'assignees',
                    'labels',
                    'resultatsIndividuels',
                ])),
            ]);

        } catch (\Exception $e) {
            Log::error('Erreur mise à jour statut individuel', [
                'tache_id' => $tache->id,
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ NOUVEAU : Mes tâches en attente de collègues
     */
    public function waitingForColleagues(Request $request): JsonResponse
    {
        $user = $request->user();

        $taches = Tache::enAttenteCollegues($user)
            ->with(['activite.projet', 'assignees', 'labels'])
            ->active()
            ->ordered()
            ->get();

        return response()->json([
            'message' => 'Tâches où vous avez terminé mais vos collègues travaillent encore',
            'data' => TacheResource::collection($taches),
            'count' => $taches->count(),
        ]);
    }

    /**
     * ✅ NOUVEAU : Vue Tâches Assignées par utilisateur (pour coordination)
     */
    public function assignedByUser(Request $request, int $activiteId): JsonResponse
    {
        $activite = Activite::with(['projet', 'membres'])->findOrFail($activiteId);
        $user = $request->user();

        // Vérifier permissions (responsable activité/projet ou super admin)
        if (
            ! $user->isSuperAdmin() &&
            $activite->responsable_id !== $user->id &&
            (! $activite->projet || $activite->projet->responsable_id !== $user->id)
        ) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        // Récupérer toutes les tâches de l'activité avec leurs assignés
        $taches = Tache::forActivite($activiteId)
            ->with(['assignees', 'labels', 'resultatsIndividuels'])
            ->active()
            ->get();

        // Grouper par utilisateur
        $byUser = [];

        foreach ($taches as $tache) {
            foreach ($tache->assignees as $assignee) {
                if (! isset($byUser[$assignee->id])) {
                    $byUser[$assignee->id] = [
                        'user' => [
                            'id' => $assignee->id,
                            'nom' => $assignee->nom,
                            'email' => $assignee->email,
                            'avatar' => $assignee->avatar,
                        ],
                        'taches' => [],
                        'stats' => [
                            'total' => 0,
                            'a_faire' => 0,
                            'en_cours' => 0,
                            'termine' => 0,
                            'en_retard' => 0,
                            'progression_moyenne' => 0,
                        ],
                    ];
                }

                $statutIndividuel = $assignee->pivot->statut_individuel;
                $progression = $assignee->pivot->progression_individuelle;

                $byUser[$assignee->id]['taches'][] = [
                    'tache_id' => $tache->id,
                    'titre' => $tache->titre,
                    'code' => $tache->code,
                    'statut_global' => $tache->statut->value,
                    'statut_individuel' => $statutIndividuel,
                    'progression_individuelle' => $progression,
                    'echeance' => $tache->echeance?->format('Y-m-d'),
                    'is_overdue' => $tache->is_overdue,
                    'priorite' => $tache->priorite->value,
                    'has_result' => $tache->monResultat($assignee) !== null,
                ];

                // Statistiques
                $byUser[$assignee->id]['stats']['total']++;
                $byUser[$assignee->id]['stats'][$statutIndividuel]++;

                if ($tache->is_overdue && $statutIndividuel !== 'termine') {
                    $byUser[$assignee->id]['stats']['en_retard']++;
                }
            }
        }

        // Calculer progression moyenne par utilisateur
        foreach ($byUser as $userId => &$userData) {
            $progressions = array_column($userData['taches'], 'progression_individuelle');
            $userData['stats']['progression_moyenne'] = count($progressions) > 0
                ? round(array_sum($progressions) / count($progressions))
                : 0;
        }

        return response()->json([
            'activite' => [
                'id' => $activite->id,
                'nom' => $activite->nom,
                'projet' => $activite->projet->only(['id', 'nom']),
            ],
            'data' => array_values($byUser),
            'total_users' => count($byUser),
            'total_tasks' => $taches->count(),
        ]);
    }

    /**
     * ✅ MODIFIÉ : Valider un résultat individuel (N1)
     */
    public function validateIndividualResultN1(Request $request, TacheResultat $resultat): JsonResponse
    {
        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        try {
            $tache = $resultat->tache;

            if (! $resultat->canBeValidatedByN1($user)) {
                return response()->json([
                    'message' => 'Vous n\'avez pas la permission de valider ce résultat',
                ], 403);
            }

            $resultat->validateByN1($user, $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Résultat validé N1 avec succès',
                'data' => [
                    'resultat' => new TacheResultatResource($resultat->fresh()),
                    'tache' => new TacheResource($tache->fresh()),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ MODIFIÉ : Valider un résultat individuel (N2)
     */
    public function validateIndividualResultN2(Request $request, TacheResultat $resultat): JsonResponse
    {
        $validated = $request->validate([
            'commentaire' => 'nullable|string|max:1000',
        ]);

        $user = $request->user();

        try {
            $tache = $resultat->tache;

            if (! $resultat->canBeValidatedByN2($user)) {
                return response()->json([
                    'message' => 'Vous n\'avez pas la permission de valider ce résultat',
                ], 403);
            }

            $resultat->validateByN2($user, $validated['commentaire'] ?? null);

            return response()->json([
                'message' => 'Résultat validé N2 avec succès',
                'data' => [
                    'resultat' => new TacheResultatResource($resultat->fresh()),
                    'tache' => new TacheResource($tache->fresh()),
                ],
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * ✅ NOUVEAU : Obtenir mes résultats de la semaine
     */
    public function myWeekResults(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:2020',
        ]);

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;

        $user = $request->user();

        $resultats = TacheResultat::forUser($user->id)
            ->individual()
            ->with(['tache.activite.projet', 'validateurN1', 'validateurN2', 'documents'])
            ->whereHas('tache', function ($q) use ($weekNumber, $year) {
                $q->where('week_number', $weekNumber)
                    ->where('year', $year);
            })
            ->get();

        return response()->json([
            'week_number' => $weekNumber,
            'year' => $year,
            'data' => TacheResultatResource::collection($resultats),
            'stats' => [
                'total' => $resultats->count(),
                'soumis' => $resultats->whereNotNull('soumis_le')->count(),
                'valides_n1' => $resultats->where('valide_par_n1', true)->count(),
                'valides_n2' => $resultats->where('valide_par_n2', true)->count(),
            ],
        ]);
    }

    /**
     * Helper pour uploader un document
     */
    private function uploadDocument(TacheResultat $resultat, $file): Document
    {
        $originalName = $file->getClientOriginalName();
        $extension = $file->getClientOriginalExtension();
        $filename = Str::uuid().'.'.$extension;
        $path = $file->storeAs('resultats', $filename, 'public');

        return $resultat->documents()->create([
            'nom' => $originalName,
            'nom_fichier' => $originalName,
            'chemin_fichier' => $path,
            'taille_fichier' => $file->getSize(),
            'type_fichier' => $file->getMimeType(),
            'extension' => $extension,
            'uploaded_by' => auth()->id(),
        ]);
    }

    /**
     * Helper pour notifier les responsables
     */
    protected function notifyResponsablesOfResult(Tache $tache, User $assignee, TacheResultat $resultat): void
    {
        $responsables = collect();

        // Responsable activité
        if ($tache->activite->responsable) {
            $responsables->push($tache->activite->responsable);
        }

        // Responsable projet
        if ($tache->activite->projet && $tache->activite->projet->responsable) {
            $responsables->push($tache->activite->projet->responsable);
        }

        // Notifier (sans doublon)
        $responsables->unique('id')
            ->reject(fn ($r) => $r->id === $assignee->id)
            ->each(fn ($r) => $r->notify(new ResultatIndividuelSoumisNotification($tache, $assignee, $resultat)));
    }

    /**
     * ✅ Performance d'équipe
     */
    public function teamPerformance(Request $request, int $activiteId): JsonResponse
    {
        $activite = Activite::findOrFail($activiteId);

        // Vérifier permissions
        if (
            ! $activite->canUserEdit($request->user()) &&
            $activite->responsable_id !== $request->user()->id
        ) {
            return response()->json([
                'message' => 'Accès non autorisé',
            ], 403);
        }

        $weekNumber = $request->input('week_number');
        $year = $request->input('year');

        $performance = $this->tacheService->getTeamPerformance($activiteId, $weekNumber, $year);

        return response()->json($performance);
    }

    /**
     * ✅ Dashboard d'évaluation
     */
    public function evaluationDashboard(Request $request): JsonResponse
    {
        $user = $request->user();
        $weekInfo = TacheService::getWeekInfo();

        // Mes tâches de la semaine
        $myTasks = Tache::assignedTo($user->id)
            ->forWeek($weekInfo['week_number'], $weekInfo['year'])
            ->with(['activite', 'labels'])
            ->get();

        // Validations en attente
        $pendingN1 = Tache::pendingValidationN1()
            ->whereHas('activite', function ($q) use ($user) {
                $q->where('responsable_id', $user->id)
                    ->orWhereHas('membres', function ($mq) use ($user) {
                        $mq->where('user_id', $user->id)
                            ->where('can_validate_results', true);
                    });
            })
            ->count();

        $pendingN2 = Tache::pendingValidationN2()
            ->whereHas('activite.projet', function ($q) use ($user) {
                $q->where('responsable_id', $user->id);
            })
            ->count();

        return response()->json([
            'week_info' => $weekInfo,
            'my_tasks' => [
                'total' => $myTasks->count(),
                'completed' => $myTasks->where('statut', TacheStatut::TERMINE)->count(),
                'in_progress' => $myTasks->where('statut', TacheStatut::EN_COURS)->count(),
                'pending' => $myTasks->where('statut', TacheStatut::A_FAIRE)->count(),
                'overdue' => $myTasks->filter->is_overdue->count(),
            ],
            'pending_validations' => [
                'n1' => $pendingN1,
                'n2' => $pendingN2,
                'total' => $pendingN1 + $pendingN2,
            ],
        ]);
    }

    /**
     * ✅ Export PDF du rapport hebdomadaire
     */
    public function exportWeeklyReportPdf(Request $request): Response
    {
        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'week_number' => 'nullable|integer|min:1|max:53',
            'year' => 'nullable|integer|min:2020',
        ]);

        $userId = $validated['user_id'] ?? $request->user()->id;
        $user = User::findOrFail($userId);

        // Vérifier permissions
        if ($userId !== $request->user()->id && ! $request->user()->isSuperAdmin()) {
            $hasAccess = Activite::where('responsable_id', $request->user()->id)
                ->whereHas('membres', function ($q) use ($userId) {
                    $q->where('user_id', $userId);
                })
                ->exists();

            if (! $hasAccess) {
                abort(403, 'Accès non autorisé');
            }
        }

        $weekNumber = $validated['week_number'] ?? now()->weekOfYear;
        $year = $validated['year'] ?? now()->year;

        $report = $this->tacheService->getWeeklyReport($user, $weekNumber, $year);

        // Générer PDF avec DomPDF ou Laravel Snappy
        $pdf = PDF::loadView('reports.weekly-tasks', [
            'report' => $report,
            'user' => $user,
        ]);

        return $pdf->download("rapport-hebdomadaire-{$user->nom}-S{$weekNumber}-{$year}.pdf");
    }
}
