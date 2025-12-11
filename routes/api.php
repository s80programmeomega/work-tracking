<?php

use App\Http\Controllers\Api\ActiviteController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\Api\ProjetController;
use App\Http\Controllers\Api\ProjetInvitationController;
use App\Http\Controllers\Api\TacheController;
use App\Http\Controllers\Api\TacheResultatController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\UserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Public routes
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login', [AuthController::class, 'login']);
});

// Routes publiques pour les invitations (pas besoin d'authentification)
Route::prefix('workspace-invitations')->group(function () {
    // Vérifier une invitation
    Route::get('/{token}', [WorkspaceController::class, 'checkInvitation']);

    // Accepter une invitation
    Route::post('/{token}/accept', [WorkspaceController::class, 'acceptInvitation']);

    // Routes admin (nécessitent une authentification)
    Route::middleware('auth:sanctum')->group(function () {
        // Récupérer toutes les invitations (pour les admins)
        Route::get('/all', [WorkspaceController::class, 'allInvitations']);

        // ✅ Refuser une invitation (authentifié)
        Route::delete('/workspace-invitations/{invitation}', [WorkspaceController::class, 'declineInvitation']);

        // Statistiques des invitations
        Route::get('/statistics', [WorkspaceController::class, 'invitationStatistics']);
    });

});

// Public routes (pas besoin d'authentification)
Route::prefix('invitations/projet')->group(function () {
    Route::get('/{token}/check', [ProjetInvitationController::class, 'check']);
    Route::post('/{token}/accept', [ProjetInvitationController::class, 'accept']);
});

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::put('/language', [AuthController::class, 'updateLanguage']);
    });

    // Dashboard routes
    Route::get('/dashboard', [DashboardController::class, 'index']);
    Route::get('/dashboard/personal', [DashboardController::class, 'personalStats']);
    Route::get('/dashboard/workspace/{workspace}', [DashboardController::class, 'tasksByWorkspace']);

    // ========================================  WORKSPACES  ========================================
    Route::prefix('workspaces')->group(function () {

        Route::get('/', [WorkspaceController::class, 'index']);
        Route::post('/', [WorkspaceController::class, 'store']);
        Route::get('/user-workspaces', [WorkspaceController::class, 'getUserWorkspaces']);
        Route::get('/{workspace}', [WorkspaceController::class, 'show']);
        Route::put('/{workspace}', [WorkspaceController::class, 'update']);
        Route::delete('/{workspace}', [WorkspaceController::class, 'destroy']);
        Route::post('/switch/{workspace}', [WorkspaceController::class, 'switch'])->name('workspaces.switch');

        // Workspace Members
        Route::prefix('{workspace}/members')->group(function () {
            Route::get('/', [WorkspaceController::class, 'members']);
            Route::post('/', [WorkspaceController::class, 'addMember']);
            Route::put('/{user}', [WorkspaceController::class, 'updateMember']);
            Route::delete('/{user}', [WorkspaceController::class, 'removeMember']);

            // Invitations
            Route::post('/invite', [WorkspaceController::class, 'inviteMembers']);
            Route::get('/invitations', [WorkspaceController::class, 'invitations']);
            Route::post('/invitations/{invitation}/resend', [WorkspaceController::class, 'resendInvitation']);
            Route::delete('/invitations/{invitation}', [WorkspaceController::class, 'cancelInvitation']);
        });

        // Workspace Projects
        Route::get('/{workspace}/projets', [WorkspaceController::class, 'projets']);

        // Workspace Statistics
        Route::get('/{workspace}/statistics', [WorkspaceController::class, 'statistics']);

        // ========================================  MEMBRE REMOVAL WITH TRANSFER  ========================================
        Route::prefix('{workspace}')->group(function () {
            // Obtenir les projets où l'user est responsable (pour UI de transfert)
            Route::get('/members/{user}/projects', [WorkspaceController::class, 'getUserProjects']);

            // Obtenir les candidats pour le transfert
            Route::get('/transfer-candidates', [WorkspaceController::class, 'getTransferCandidates']);

            // Retirer membre avec transfert optionnel
            Route::delete('/members/{user}/remove', [WorkspaceController::class, 'removeMemberWithTransfer']);
        });
    });



    // ======================================== PROJETS ========================================
    Route::prefix('projets')->group(function () {

        // Dashboard & Statistics (routes spécifiques)
        Route::get('/dashboard-stats', [ProjetController::class, 'dashboardStats']);
        Route::get('/mes-projets', [ProjetController::class, 'myProjets']);
        Route::get('/archives', [ProjetController::class, 'archived']);
        Route::get('/projets-accessibles', [ProjetController::class, 'accessible']);

        Route::middleware(['super_admin'])->group(function () {
            Route::get('/list/all', [ProjetController::class, 'index']); // Tous les projets
            Route::get('/activites', [ActiviteController::class, 'index']); // Toutes les activités
        });

        // CRUD de base 
        Route::post('/', [ProjetController::class, 'store']);
        Route::get('/{projet}', [ProjetController::class, 'show']);
        Route::put('/{projet}', [ProjetController::class, 'update']);
        Route::delete('/{projet}', [ProjetController::class, 'destroy']);

        // Project Actions
        Route::post('/{projet}/archive', [ProjetController::class, 'archive']);
        Route::post('/{projet}/unarchive', [ProjetController::class, 'unarchive']);
        Route::post('/{projet}/complete', [ProjetController::class, 'complete']);
        Route::post('/{projet}/clone', [ProjetController::class, 'clone']); // Changé de duplicate à clone
        Route::post('/{projet}/toggle-favorite', [ProjetController::class, 'toggleFavorite']);


        // Project Members Management
        Route::prefix('{projet}/members')->group(function () {
            Route::get('/', [ProjetController::class, 'getMembers']);
            Route::post('/', [ProjetController::class, 'addMember']);
            Route::put('/{user}', [ProjetController::class, 'updateMember']);
            Route::delete('/{user}', [ProjetController::class, 'removeMember']);
        });

        Route::prefix('{projet}/invitations')->group(function () {
            Route::post('/', [ProjetInvitationController::class, 'invite']);
            Route::get('/', [ProjetInvitationController::class, 'index']);
            Route::post('/{invitation}/resend', [ProjetInvitationController::class, 'resend']);
            Route::delete('/{invitation}', [ProjetInvitationController::class, 'cancel']);
        });

        // Project Relations
        Route::get('/{projet}/activites', [ProjetController::class, 'getActivites']);
        Route::get('/{projet}/taches', [ProjetController::class, 'getTaches']);

        // Project Statistics & Reports
        Route::get('/{projet}/statistics', [ProjetController::class, 'getStatistics']);
        Route::get('/{projet}/performance-report', [ProjetController::class, 'performanceReport']);
        Route::get('/{projet}/accessible-tasks', [ProjetController::class, 'accessibleTasks']);
        Route::delete('/{projet}/members/{user}/remove', [ProjetController::class, 'removeMemberWithTransfer']);


    });


    // Activity Management Routes
    Route::prefix('activites')->group(function () {

        // ✅ Routes PUBLIQUES (accessibles à tous les utilisateurs authentifiés)
        Route::get('/mes-activites', [ActiviteController::class, 'myActivites']); // Mes activités
        Route::get('/my-activites', [ActiviteController::class, 'myActivites']); // Alias
        Route::get('/en-retard', [ActiviteController::class, 'enRetard']); // Activités en retard
        Route::get('/projet/{projetId}', [ActiviteController::class, 'forProjet']); // Par projet

        // CRUD de base
        Route::post('/', [ActiviteController::class, 'store']);
        Route::get('/{activite}', [ActiviteController::class, 'show']);
        Route::put('/{activite}', [ActiviteController::class, 'update']);
        Route::delete('/{activite}', [ActiviteController::class, 'destroy']);
        Route::put('/{activite}/change-responsable', [ActiviteController::class, 'changeResponsable']);

        // Actions
        Route::post('/{activite}/archive', [ActiviteController::class, 'archive']);
        Route::post('/{activite}/unarchive', [ActiviteController::class, 'unarchive']);
        Route::post('/{activite}/toggle-archive', [ActiviteController::class, 'toggleArchive']);
        Route::post('/{activite}/duplicate', [ActiviteController::class, 'duplicate']);
        Route::post('/reorder', [ActiviteController::class, 'reorder']);

        // Available members for projet
        Route::get('/available-members/{projetId}', [ActiviteController::class, 'availableMembers']);

        // Membres d'une activité spécifique
        Route::get('/{activite}/membres', [ActiviteController::class, 'membres']);

        // ✅ Gestion des membres d'activité
        Route::prefix('{activite}/members')->group(function () {
            Route::get('/', [ActiviteController::class, 'getMembers']);
            Route::post('/', [ActiviteController::class, 'addMember']);
            Route::put('/{user}', [ActiviteController::class, 'updateMember']);
            Route::delete('/{user}', [ActiviteController::class, 'removeMember']);
        });

        // Tâches de l'activité
        Route::get('/{activite}/taches', [ActiviteController::class, 'getTaches']);

        // List and filter
        // Route::middleware(['super_admin'])->group(function () {
        Route::get('/all/activity', [ActiviteController::class, 'index']); // Toutes les activités (Super Admin)
        // });

        // ✅ NOUVEAU : Vue coordination (tâches par utilisateur)
        Route::get('/{activiteId}/taches-by-user', [TacheController::class, 'assignedByUser']);
    });

    // ======================================== TÂCHES  ========================================
    Route::prefix('taches')->group(function () {
        // Liste et création
        Route::get('/', [TacheController::class, 'index']);
        Route::post('/', [TacheController::class, 'store']);

        // Vues spécifiques
        Route::get('/mes-taches', [TacheController::class, 'myTasks']);
        Route::get('/assignees', [TacheController::class, 'assignedToMe']);
        Route::get('/en-attente', [TacheController::class, 'pending']);
        Route::get('/en-retard', [TacheController::class, 'overdue']);
        Route::post('/reorder', [TacheController::class, 'reorder']);

        // ✅ NOUVEAU: Mon kanban personnel
        Route::get('/my-kanban', [TacheController::class, 'myKanban']);

        // ✅ NOUVEAU : Gestion statut individuel
        Route::post('/{tache}/move-my-card', [TacheController::class, 'moveMyCard']);

        // Soumettre mon résultat individuel
        Route::post('/{tache}/submit-my-result', [TacheResultatController::class, 'submitMyResult']);

        // ✅ Kanban pour une activité
        Route::get('/activite/{activiteId}/kanban', [TacheController::class, 'forActivite']);

        // ✅ NOUVEAU : Tâches en attente de collègues
        Route::get('/waiting-for-colleagues', [TacheController::class, 'waitingForColleagues']);

        // Vérifier les permissions
        Route::get('/activite/{activiteId}/check-permissions', [TacheController::class, 'checkPermissions']);

        // ✅ NOUVEAU : Validation résultats individuels
        // Route::post('/resultats-individuels/{resultat}/validate-n1', [TacheController::class, 'validateIndividualResultN1']);
        // Route::post('/resultats-individuels/{resultat}/validate-n2', [TacheController::class, 'validateIndividualResultN2']);


        // CRUD basique
        Route::get('/{tache}', [TacheController::class, 'show']);
        Route::put('/{tache}', [TacheController::class, 'update']);
        Route::delete('/{tache}', [TacheController::class, 'destroy']);

        // ✅ Actions principales
        Route::post('/{tache}/complete', [TacheController::class, 'complete']); // Marquer terminé
        // Route::post('/{tache}/validate-n1', [TacheController::class, 'validateN1']); // Validation N1
        // Route::post('/{tache}/validate-n2', [TacheController::class, 'validateN2']); // Validation N2
        Route::post('/{tache}/move', [TacheController::class, 'move']); // Déplacer (Kanban)
        Route::post('/{tache}/archive', [TacheController::class, 'archive']);
        Route::post('/{tache}/unarchive', [TacheController::class, 'unarchive']);
        Route::post('/{tache}/duplicate', [TacheController::class, 'duplicate']);

        // Task Assignees
        Route::post('/{tache}/assignees', [TacheController::class, 'assignUser']);
        Route::delete('/{tache}/assignees/{user}', [TacheController::class, 'unassignUser']);

        // Sub-tasks
        Route::get('/{tache}/sous-taches', [TacheController::class, 'subTasks']);
        Route::post('/{tache}/sous-taches', [TacheController::class, 'createSubTask']);

        // Task Dependencies
        Route::get('/{tache}/dependencies', [TacheController::class, 'dependencies']);
        Route::post('/{tache}/dependencies', [TacheController::class, 'addDependency']);
        Route::delete('/{tache}/dependencies/{dependencyId}', [TacheController::class, 'removeDependency']);

        // Task Labels
        Route::post('/{tache}/labels', [TacheController::class, 'attachLabel']);
        Route::delete('/{tache}/labels/{label}', [TacheController::class, 'detachLabel']);

        // Routes pour les fichiers attachés
        Route::post('{tache}/attachments', [TacheController::class, 'addAttachments']);
        Route::get('{tache}/attachments', [TacheController::class, 'getAttachments']);
        Route::get('{tache}/attachments/{attachment}/download', [TacheController::class, 'downloadAttachment']);
        Route::delete('{tache}/attachments/{attachment}', [TacheController::class, 'deleteAttachment']);

        // Routes pour les liens externes
        Route::post('{tache}/external-links', [TacheController::class, 'addExternalLink']);
        Route::delete('{tache}/external-links/{link}', [TacheController::class, 'deleteExternalLink']);

    });

    // ======================================== RÉSULTATS DE TÂCHES  ========================================
    Route::prefix('taches/{tache}/resultats')->group(function () {
        Route::get('/', [TacheResultatController::class, 'index']);
        Route::post('/', [TacheResultatController::class, 'store']);
        // Route::get('/{resultat}', [TacheResultatController::class, 'show']);
        Route::put('/{resultat}', [TacheResultatController::class, 'update']);
        Route::delete('/{resultat}', [TacheResultatController::class, 'destroy']);

        // Soumettre un résultat
        Route::post('/{resultat}/submit', [TacheResultatController::class, 'submit']);

        Route::get('/documents', [TacheResultatController::class, 'getDocuments']);
        Route::delete('/documents/{document}', [TacheResultatController::class, 'deleteDocument']);
        // Nouvelles routes pour les documents
        Route::get('/{resultat}/documents/{document}/view', [TacheResultatController::class, 'viewDocument']);
        Route::get('/{resultat}/documents/{document}/download', [TacheResultatController::class, 'downloadDocument']);
        Route::get('/{resultat}/document-stats', [TacheResultatController::class, 'documentStats']);
    });

    // 📋 Récupérer un résultat spécifique
    Route::get('/tache-resultats/{resultat}', [TacheResultatController::class, 'show'])
    ->name('tache-resultats.show');

    // ========================================  ÉVALUATIONS  ========================================
    Route::prefix('evaluations')->group(function () {
        // ✅ Rapport hebdomadaire personnel
        // Route::get('/mon-rapport-hebdomadaire', [TacheController::class, 'myWeeklyReport']);
        Route::get('/mon-rapport-hebdomadaire', [EvaluationController::class, 'myWeeklyReportImproved']);

        // ✅ Rapport hebdomadaire d'un utilisateur (managers)
        // Route::get('/rapport-hebdomadaire/{userId}', [TacheController::class, 'userWeeklyReport']);
        Route::get('/rapport-hebdomadaire/{userId}', [EvaluationController::class, 'userWeeklyReport']);

        // 📊 PERFORMANCE WORKSPACE (NOUVEAU)
        // Vue d'ensemble de la performance d'un workspace
        Route::get('/workspace/{workspaceId}/performance', [EvaluationController::class, 'workspacePerformanceReport']);

        // ✅ Performance d'équipe
        Route::get('/performance-equipe/{activiteId}', [TacheController::class, 'teamPerformance']);

        // Détail de performance d'un membre spécifique
        Route::get('/membre/{userId}/performance', [EvaluationController::class, 'memberDetailedPerformance']);

        // ✅ Export PDF
        Route::post('/export-pdf', [TacheController::class, 'exportWeeklyReportPdf']);

        // Export PDF du rapport de performance workspace
        Route::post('/workspace/{workspaceId}/export-pdf', [EvaluationController::class, 'exportWorkspacePerformancePdf']);


        // Dashboard général
        Route::get('/dashboard', [TacheController::class, 'evaluationDashboard']);

        // Statistiques de validation utilisateur
        Route::get('/stats/user/{userId}', [EvaluationController::class, 'userValidationStats']);

        // Statistiques globales de validation
        Route::get('/stats/global', [EvaluationController::class, 'globalValidationStats']);

        // 📋 Liste des résultats en attente de validation (personnalisée par rôle)
        Route::get('/resultats/en-attente', [EvaluationController::class, 'pendingValidations']);

        // 📊 Mes responsabilités (tous les résultats que je peux consulter)
        Route::get('/mes-responsabilites', [EvaluationController::class, 'myResponsibilities']);

        // ✅ Validation N1 (responsable activité uniquement)
        Route::post('/resultats-individuels/{resultat}/validate-n1', [EvaluationController::class, 'validateN1']);

        // ✅ Validation N2 (responsable projet uniquement)
        Route::post('/resultats-individuels/{resultat}/validate-n2', [EvaluationController::class, 'validateN2']);

        // ❌ Rejeter un résultat
        Route::post('/resultats/{resultat}/reject', [EvaluationController::class, 'reject']);

        // 🔍 Vérifier mes permissions sur un résultat
        Route::get('/resultats/{resultat}/permissions', [EvaluationController::class, 'checkPermissions']);

        // 📜 Historique des validations
        Route::get('/history', [EvaluationController::class, 'validationHistory']);
    });

    // Routes pour les rapports
    Route::prefix('reports')->group(function () {
        Route::get('/activite/{activiteId}/performance', [TacheController::class, 'activityPerformanceReport']);
        Route::get('/activite/{activiteId}/user-tasks', [TacheController::class, 'userTasksReport']);
    });


    // Document Management Routes
    Route::prefix('documents')->group(function () {
        /**
         * GET /api/documents
         * Récupère les documents d'une entité (Projet, Activité, Tâche, etc.)
         * Query params: documentable_type, documentable_id, with_versions
         */
        Route::get('/', [DocumentController::class, 'index'])->name('documents.index');

        /**
         * GET /api/documents/search
         * Recherche de documents
         * Query params: query, type, user_id, documentable_type, documentable_id, mime_type, per_page
         */
        Route::get('/search', [DocumentController::class, 'search'])->name('documents.search');

        /**
         * GET /api/documents/workspace/{workspace}
         * Récupère tous les documents d'un workspace (projets, activités, tâches)
         * Query params: type, search, per_page
         */
        Route::get('/workspace/{workspace}', [DocumentController::class, 'workspaceDocuments'])->name('documents.workspace');

        /**
         * GET /api/documents/workspace/{workspace}/stats
         * Statistiques des documents d'un workspace
         */
        Route::get('/workspace/{workspace}/stats', [DocumentController::class, 'workspaceStats'])->name('documents.workspace.stats');

        /**
         * POST /api/documents
         * Upload un ou plusieurs documents
         * Body: files[], documentable_type, documentable_id, description, visibility, disk
         */
        Route::post('/', [DocumentController::class, 'store'])->name('documents.store');

        /**
         * GET /api/documents/{document}
         * Affiche les détails d'un document
         */
        Route::get('/{document}', [DocumentController::class, 'show'])->name('documents.show');

        /**
         * PUT/PATCH /api/documents/{document}
         * Met à jour les métadonnées d'un document
         * Body: nom, description, visibility
         */
        Route::put('/{document}', [DocumentController::class, 'update'])->name('documents.update');
        Route::patch('/{document}', [DocumentController::class, 'update'])->name('documents.patch');

        /**
         * DELETE /api/documents/{document}
         * Supprime un document (soft delete)
         */
        Route::delete('/{document}', [DocumentController::class, 'destroy'])->name('documents.destroy');

        /**
         * GET /api/documents/{document}/download
         * Télécharge un document
         */
        Route::get('/{document}/download', [DocumentController::class, 'download'])->name('documents.download');

        /**
         * GET /api/documents/stats
         * Statistiques globales des documents
         */
        Route::get('/stats', [DocumentController::class, 'globalStats'])->name('documents.global-stats');

        /**
         * GET /api/documents/hierarchy
         * Récupère la hiérarchie d'une entité
         */
        Route::get('/hierarchy', [DocumentController::class, 'hierarchy'])->name('documents.hierarchy');

        /**
         * GET /api/documents/recent
         * Documents récents de l'utilisateur
         */
        Route::get('/recent', [DocumentController::class, 'recent'])->name('documents.recent');

        /**
         * GET /api/documents/shared-with-me
         * Documents partagés avec l'utilisateur
         */
        Route::get('/shared-with-me', [DocumentController::class, 'sharedWithMe'])->name('documents.shared-with-me');

        /**
         * GET /api/documents/my-documents
         * Documents créés par l'utilisateur
         */
        Route::get('/my-documents', [DocumentController::class, 'myDocuments'])->name('documents.my-documents');


        /**
         * POST /api/documents/{document}/versions
         * Crée une nouvelle version d'un document
         * Body: file
         */
        Route::post('/{document}/versions', [DocumentController::class, 'createVersion'])->name('documents.versions.create');

        /**
         * GET /api/documents/{document}/versions
         * Récupère toutes les versions d'un document
         */
        Route::get('/{document}/versions', [DocumentController::class, 'versions'])->name('documents.versions');

        /**
         * GET /api/documents/{document}/stats
         * Récupère les statistiques de téléchargement
         */
        Route::get('/{document}/stats', [DocumentController::class, 'stats'])->name('documents.stats');

        /**
         * GET /api/documents/{document}/permissions
         * Liste les permissions d'un document
         */
        Route::get('/{document}/permissions', [DocumentController::class, 'listPermissions'])->name('documents.permissions.list');

        /**
         * POST /api/documents/{document}/permissions/grant
         * Accorde une permission à un utilisateur
         * Body: user_id, can_view, can_download, can_edit, can_delete, can_share, expires_at
         */
        Route::post('/{document}/permissions/grant', [DocumentController::class, 'grantPermission'])->name('documents.permissions.grant');

        /**
         * POST /api/documents/{document}/permissions/revoke
         * Révoque une permission
         * Body: user_id
         */
        Route::post('/{document}/permissions/revoke', [DocumentController::class, 'revokePermission'])->name('documents.permissions.revoke');

        /**
         * POST /api/documents/{document}/share
         * Partage avec plusieurs utilisateurs
         * Body: user_ids[], permissions{}, expires_at
         */
        Route::post('/{document}/share', [DocumentController::class, 'shareWithUsers'])->name('documents.share');
    });
















































    // User Management Routes
    Route::prefix('users')->group(function () {


        // List and search
        Route::get('/', [UserController::class, 'index']);
        Route::get('/search', [UserController::class, 'search']);

        // Current user profile
        Route::get('/profile', [UserController::class, 'profile']);
        Route::put('/profile', [UserController::class, 'updateProfile']);
        Route::post('/profile', [UserController::class, 'updateProfile']); // For file uploads
        Route::post('/change-password', [UserController::class, 'changePassword']);

        // User CRUD
        Route::post('/', [UserController::class, 'store']);
        Route::get('/{user}', [UserController::class, 'show']);
        Route::put('/{user}', [UserController::class, 'update']);
        Route::delete('/{user}', [UserController::class, 'destroy']);

        // User actions
        Route::post('/{user}/toggle-active', [UserController::class, 'toggleActive']);
        Route::get('/{user}/activity', [UserController::class, 'activity']);
    });





    // Task Management Routes
    // Route::prefix('taches')->group(function () {
    //     // List and filter
    //     Route::get('/', [\App\Http\Controllers\TacheController::class, 'index']);
    //     Route::get('/my-taches', [\App\Http\Controllers\TacheController::class, 'myTaches']);
    //     Route::get('/activite/{activiteId}', [\App\Http\Controllers\TacheController::class, 'forActivite']);

    //     // CRUD
    //     Route::post('/', [\App\Http\Controllers\TacheController::class, 'store']);
    //     Route::get('/{tache}', [\App\Http\Controllers\TacheController::class, 'show']);
    //     Route::put('/{tache}', [\App\Http\Controllers\TacheController::class, 'update']);
    //     Route::delete('/{tache}', [\App\Http\Controllers\TacheController::class, 'destroy']);

    //     // Actions
    //     Route::post('/{tache}/move', [\App\Http\Controllers\TacheController::class, 'move']);
    //     Route::post('/{tache}/reorder', [\App\Http\Controllers\TacheController::class, 'reorder']);
    //     Route::post('/{tache}/duplicate', [\App\Http\Controllers\TacheController::class, 'duplicate']);
    //     Route::post('/{tache}/archive', [\App\Http\Controllers\TacheController::class, 'archive']);
    //     Route::post('/{tache}/unarchive', [\App\Http\Controllers\TacheController::class, 'unarchive']);
    //     Route::post('/{tache}/validate', [\App\Http\Controllers\TacheController::class, 'validateTask']);
    //     Route::post('/{tache}/assign', [\App\Http\Controllers\TacheController::class, 'assignUser']);
    //     Route::post('/{tache}/unassign', [\App\Http\Controllers\TacheController::class, 'unassignUser']);
    //     Route::post('/{tache}/progress', [\App\Http\Controllers\TacheController::class, 'updateProgress']);
    // });

    // Labels routes
    Route::prefix('labels')->group(function () {
        Route::get('/', [LabelController::class, 'index']);
        Route::get('/stats', [LabelController::class, 'stats']);
        Route::post('/', [LabelController::class, 'store']);
        Route::get('/{label}', [LabelController::class, 'show']);
        Route::put('/{label}', [LabelController::class, 'update']);
        Route::delete('/{label}', [LabelController::class, 'destroy']);
        Route::post('/reorder', [LabelController::class, 'reorder']);
        Route::post('/{label}/duplicate', [LabelController::class, 'duplicate']);
    });

    // Label Templates routes
    Route::prefix('label-templates')->group(function () {
        Route::get('/', [\App\Http\Controllers\LabelTemplateController::class, 'index']);
        Route::get('/default', [\App\Http\Controllers\LabelTemplateController::class, 'getDefault']);
        Route::get('/predefined', [\App\Http\Controllers\LabelTemplateController::class, 'predefined']);
        Route::post('/', [\App\Http\Controllers\LabelTemplateController::class, 'store']);
        Route::get('/{labelTemplate}', [\App\Http\Controllers\LabelTemplateController::class, 'show']);
        Route::put('/{labelTemplate}', [\App\Http\Controllers\LabelTemplateController::class, 'update']);
        Route::delete('/{labelTemplate}', [\App\Http\Controllers\LabelTemplateController::class, 'destroy']);
        Route::post('/{labelTemplate}/apply', [\App\Http\Controllers\LabelTemplateController::class, 'apply']);
        Route::post('/{labelTemplate}/duplicate', [\App\Http\Controllers\LabelTemplateController::class, 'duplicate']);
        Route::post('/{labelTemplate}/set-default', [\App\Http\Controllers\LabelTemplateController::class, 'setDefault']);
    });

    // Task Labels routes (attach/detach labels to tasks)
    Route::prefix('taches/{tache}/labels')->group(function () {
        Route::get('/', [\App\Http\Controllers\TacheLabelController::class, 'index']);
        Route::post('/sync', [\App\Http\Controllers\TacheLabelController::class, 'sync']);
        Route::post('/attach', [\App\Http\Controllers\TacheLabelController::class, 'attach']);
        Route::post('/detach', [\App\Http\Controllers\TacheLabelController::class, 'detach']);
        Route::delete('/detach-all', [\App\Http\Controllers\TacheLabelController::class, 'detachAll']);
    });

    // Comment Management Routes
    Route::prefix('comments')->group(function () {
        // List comments for an entity
        Route::get('/', [\App\Http\Controllers\CommentController::class, 'index']);

        // CRUD operations
        Route::post('/', [\App\Http\Controllers\CommentController::class, 'store']);
        Route::get('/{comment}', [\App\Http\Controllers\CommentController::class, 'show']);
        Route::put('/{comment}', [\App\Http\Controllers\CommentController::class, 'update']);
        Route::delete('/{comment}', [\App\Http\Controllers\CommentController::class, 'destroy']);

        // Reactions
        Route::post('/{comment}/reactions', [\App\Http\Controllers\CommentController::class, 'toggleReaction']);

        // Attachments
        Route::post('/{comment}/attachments', [\App\Http\Controllers\CommentController::class, 'addAttachment']);
        Route::delete('/{comment}/attachments/{attachment}', [\App\Http\Controllers\CommentController::class, 'deleteAttachment']);

        // Mentions
        Route::get('/mentions/unread', [\App\Http\Controllers\CommentController::class, 'unreadMentions']);
        Route::post('/mentions/mark-read', [\App\Http\Controllers\CommentController::class, 'markMentionsAsRead']);
    });

    // Activity Log Routes
    Route::prefix('activities')->group(function () {
        // Get activity feed for dashboard
        Route::get('/feed', [\App\Http\Controllers\ActivityController::class, 'feed']);

        // Get recent activities
        Route::get('/recent', [\App\Http\Controllers\ActivityController::class, 'recent']);

        // Get activities for a specific subject
        Route::get('/subject', [\App\Http\Controllers\ActivityController::class, 'forSubject']);

        // Get activities by user
        Route::get('/user', [\App\Http\Controllers\ActivityController::class, 'byUser']);

        // Get activities by log name
        Route::get('/log-name', [\App\Http\Controllers\ActivityController::class, 'byLogName']);

        // Get activities by date range
        Route::get('/date-range', [\App\Http\Controllers\ActivityController::class, 'byDateRange']);

        // Get activity statistics
        Route::get('/stats', [\App\Http\Controllers\ActivityController::class, 'stats']);
    });



    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationController::class, 'index']);
        Route::get('/all', [\App\Http\Controllers\NotificationController::class, 'all']);
        Route::get('/grouped', [\App\Http\Controllers\NotificationController::class, 'grouped']);
        Route::get('/statistics', [\App\Http\Controllers\NotificationController::class, 'statistics']);
        Route::post('/mark-all-read', [\App\Http\Controllers\NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/mark-read', [\App\Http\Controllers\NotificationController::class, 'markAsRead']);
        Route::delete('/delete-all-read', [\App\Http\Controllers\NotificationController::class, 'deleteAllRead']);
        Route::delete('/{id}', [\App\Http\Controllers\NotificationController::class, 'destroy']);
    });

    // Notification preferences
    Route::prefix('notification-preferences')->group(function () {
        Route::get('/', [\App\Http\Controllers\NotificationPreferenceController::class, 'show']);
        Route::put('/', [\App\Http\Controllers\NotificationPreferenceController::class, 'update']);
    });

    // Team Management Routes
    Route::prefix('teams')->group(function () {
        // List and my teams
        Route::get('/', [\App\Http\Controllers\TeamController::class, 'index']);
        Route::get('/my-teams', [\App\Http\Controllers\TeamController::class, 'myTeams']);

        // CRUD
        Route::post('/', [\App\Http\Controllers\TeamController::class, 'store']);
        Route::get('/{uuid}', [\App\Http\Controllers\TeamController::class, 'show']);
        Route::put('/{uuid}', [\App\Http\Controllers\TeamController::class, 'update']);
        Route::delete('/{uuid}', [\App\Http\Controllers\TeamController::class, 'destroy']);

        // Actions
        Route::post('/{uuid}/archive', [\App\Http\Controllers\TeamController::class, 'archive']);
        Route::post('/{uuid}/restore', [\App\Http\Controllers\TeamController::class, 'restore']);
        Route::post('/{uuid}/avatar', [\App\Http\Controllers\TeamController::class, 'uploadAvatar']);

        // Stats and activity
        Route::get('/{uuid}/stats', [\App\Http\Controllers\TeamController::class, 'stats']);
        Route::get('/{uuid}/activities', [\App\Http\Controllers\TeamController::class, 'activities']);
        Route::get('/{uuid}/online-members', [\App\Http\Controllers\TeamController::class, 'onlineMembers']);
        Route::post('/{uuid}/presence', [\App\Http\Controllers\TeamController::class, 'updatePresence']);

        // Members
        Route::post('/{uuid}/members', [\App\Http\Controllers\TeamMemberController::class, 'store']);
        Route::put('/{uuid}/members/{userId}/role', [\App\Http\Controllers\TeamMemberController::class, 'updateRole']);
        Route::put('/{uuid}/members/{userId}/permissions', [\App\Http\Controllers\TeamMemberController::class, 'updatePermissions']);
        Route::delete('/{uuid}/members/{userId}', [\App\Http\Controllers\TeamMemberController::class, 'destroy']);
        Route::post('/{uuid}/transfer-ownership', [\App\Http\Controllers\TeamMemberController::class, 'transferOwnership']);

        // Messages
        Route::get('/{uuid}/messages', [\App\Http\Controllers\TeamMessageController::class, 'index']);
        Route::get('/{uuid}/messages/pinned', [\App\Http\Controllers\TeamMessageController::class, 'pinned']);
        Route::post('/{uuid}/messages', [\App\Http\Controllers\TeamMessageController::class, 'store']);
        Route::post('/messages/{uuid}/reactions', [\App\Http\Controllers\TeamMessageController::class, 'addReaction']);

        // Announcements
        Route::get('/{uuid}/announcements', [\App\Http\Controllers\TeamAnnouncementController::class, 'index']);
        Route::post('/{uuid}/announcements', [\App\Http\Controllers\TeamAnnouncementController::class, 'store']);
        Route::put('/{uuid}/announcements/{announcement}', [\App\Http\Controllers\TeamAnnouncementController::class, 'update']);
        Route::delete('/{uuid}/announcements/{announcement}', [\App\Http\Controllers\TeamAnnouncementController::class, 'destroy']);

        // Resources
        Route::get('/{uuid}/resources', [\App\Http\Controllers\TeamResourceController::class, 'index']);
        Route::post('/{uuid}/resources', [\App\Http\Controllers\TeamResourceController::class, 'store']);
        Route::put('/{uuid}/resources/{resource}', [\App\Http\Controllers\TeamResourceController::class, 'update']);
        Route::delete('/{uuid}/resources/{resource}', [\App\Http\Controllers\TeamResourceController::class, 'destroy']);

        // Events
        Route::get('/{uuid}/events', [\App\Http\Controllers\TeamEventController::class, 'index']);
        Route::post('/{uuid}/events', [\App\Http\Controllers\TeamEventController::class, 'store']);
        Route::get('/{uuid}/events/{event}', [\App\Http\Controllers\TeamEventController::class, 'show']);
        Route::put('/{uuid}/events/{event}', [\App\Http\Controllers\TeamEventController::class, 'update']);
        Route::delete('/{uuid}/events/{event}', [\App\Http\Controllers\TeamEventController::class, 'destroy']);
    });
});
