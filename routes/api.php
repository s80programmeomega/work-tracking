<?php

use App\Http\Controllers\ActivityController;
use App\Http\Controllers\Api\ActiviteController;
use App\Http\Controllers\Api\AdminController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\DocumentController;
use App\Http\Controllers\Api\EvaluationController;
use App\Http\Controllers\Api\ProjetController;
use App\Http\Controllers\Api\ProjetInvitationController;
use App\Http\Controllers\Api\PushSubscriptionController;
use App\Http\Controllers\Api\SearchController;
use App\Http\Controllers\Api\SocialAuthController;
use App\Http\Controllers\Api\SousTacheController;
use App\Http\Controllers\Api\SupportTicketController;
use App\Http\Controllers\Api\TacheController;
use App\Http\Controllers\Api\TacheResultatController;
use App\Http\Controllers\Api\TwoFactorManagementController;
use App\Http\Controllers\Api\WorkspaceController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\LabelController;
use App\Http\Controllers\LabelTemplateController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\NotificationPreferenceController;
use App\Http\Controllers\TacheLabelController;
use App\Http\Controllers\TeamAnnouncementController;
use App\Http\Controllers\TeamController;
use App\Http\Controllers\TeamEventController;
use App\Http\Controllers\TeamMemberController;
use App\Http\Controllers\TeamMessageController;
use App\Http\Controllers\TeamResourceController;
use App\Http\Controllers\UserController;
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
    Route::post('/refresh', [AuthController::class, 'refresh']);

    // Google OAuth — le redirect renvoie vers Google, le callback revient ici
    Route::get('/google/redirect', [SocialAuthController::class, 'redirectToGoogle'])->name('auth.google.redirect');
    Route::get('/google/callback', [SocialAuthController::class, 'handleGoogleCallback'])->name('auth.google.callback');

    // Challenge MFA post-login (pas encore authentifié — jeton de challenge provisoire)
    Route::post('/two-factor-challenge', [AuthController::class, 'twoFactorChallenge'])
        ->middleware('throttle:10,1');
    Route::post('/two-factor-email-send', [AuthController::class, 'twoFactorEmailSend'])
        ->middleware('throttle:3,1');
});

// Routes publiques pour les invitations (pas besoin d'authentification)
Route::prefix('workspace-invitations')->group(function () {
    // Routes statiques authentifiées — doivent précéder les routes wildcard /{token}
    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/all', [WorkspaceController::class, 'allInvitations']);
        Route::get('/statistics', [WorkspaceController::class, 'invitationStatistics']);
        Route::delete('/workspace-invitations/{invitation}', [WorkspaceController::class, 'declineInvitation']);
    });

    // Routes dynamiques publiques
    Route::get('/{token}', [WorkspaceController::class, 'checkInvitation']);
    Route::post('/{token}/accept', [WorkspaceController::class, 'acceptInvitation']);
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
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
        Route::put('/language', [AuthController::class, 'updateLanguage']);
        // Activation/désactivation de l'OTP email comme facteur de secours
        Route::post('/email-otp-toggle', [AuthController::class, 'toggleEmailOtp']);
    });

    // =====================================  2FA MANAGEMENT  =====================================
    // Ces routes remplacent les endpoints Fortify qui utilisent le middleware 'web' (session),
    // incompatible avec l'auth par token Sanctum de ce SPA.
    Route::prefix('user')->group(function () {
        Route::post('/two-factor-authentication', [TwoFactorManagementController::class, 'enable']);
        Route::post('/confirmed-two-factor-authentication', [TwoFactorManagementController::class, 'confirm']);
        Route::delete('/two-factor-authentication', [TwoFactorManagementController::class, 'disable']);
        Route::get('/two-factor-qr-code', [TwoFactorManagementController::class, 'qrCode']);
        Route::get('/two-factor-secret-key', [TwoFactorManagementController::class, 'secretKey']);
        Route::get('/two-factor-recovery-codes', [TwoFactorManagementController::class, 'recoveryCodes']);
        Route::post('/two-factor-recovery-codes', [TwoFactorManagementController::class, 'regenerateRecoveryCodes']);
    });

    // ========================================  SUPPORT  ==========================================
    Route::prefix('support')->group(function () {
        Route::post('/', [SupportTicketController::class, 'store'])->name('support.store');
        Route::get('/', [SupportTicketController::class, 'index'])->name('support.index');
        Route::post('/{ticket}/attachments', [SupportTicketController::class, 'addAttachments'])->name('support.attachments.add');
        Route::get('/attachments/{attachment}/download', [SupportTicketController::class, 'downloadAttachment'])->name('support.attachments.download');
    });

    // ========================================  PLATFORM ADMIN  ========================================
    Route::prefix('admin')->middleware('super_admin')->group(function () {
        Route::get('/stats', [AdminController::class, 'stats'])->name('admin.stats');
        Route::get('/workspaces', [AdminController::class, 'workspaces'])->name('admin.workspaces');
        Route::get('/users', [AdminController::class, 'users'])->name('admin.users');
        Route::patch('/users/{user}/role', [AdminController::class, 'updateUserRole'])->name('admin.users.update-role');
        Route::post('/workspaces/{workspace}/extend-trial', [AdminController::class, 'extendTrial'])->name('admin.workspaces.extend-trial');
        Route::post('/workspaces/{workspace}/suspend', [AdminController::class, 'suspendWorkspace'])->name('admin.workspaces.suspend');
        Route::post('/workspaces/{workspace}/reactivate', [AdminController::class, 'reactivateWorkspace'])->name('admin.workspaces.reactivate');
        Route::get('/roles', [AdminController::class, 'roles'])->name('admin.roles');
        Route::patch('/roles/{role}/permissions', [AdminController::class, 'syncRolePermissions'])->name('admin.roles.sync-permissions');
        // Gestion des tickets de support
        Route::get('/support', [SupportTicketController::class, 'adminIndex'])->name('admin.support.index');
        // Réponse obligatoire + changement de statut en une seule action
        Route::post('/support/{ticket}/reply', [SupportTicketController::class, 'reply'])->name('admin.support.reply');
        // Journal d'activité cross-app (Spatie activity_log) — super-admin seulement
        Route::get('/activity-log', [ActivityController::class, 'adminFeed'])->name('admin.activity-log');
        // Journal d'audit de validation (N0/N1/bypass) — super-admin seulement
        Route::get('/validation-audit-log', [AdminController::class, 'validationAuditLog'])->name('admin.validation-audit-log');
    });

    // Recherche globale (Phase 6) — manager et supérieur uniquement
    Route::get('/search', [SearchController::class, 'search'])->name('search.global');
    Route::get('/search/export', [SearchController::class, 'export'])->name('search.export');
    Route::post('/search/export', [SearchController::class, 'exportSelected'])->name('search.export.selected');

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

            // Static routes MUST come before /{user} to avoid being swallowed by the wildcard
            Route::post('/invite', [WorkspaceController::class, 'inviteMembers'])->middleware('subscription.limits:add_member');
            Route::get('/invitations', [WorkspaceController::class, 'invitations']);
            Route::post('/invitations/{invitation}/resend', [WorkspaceController::class, 'resendInvitation']);
            Route::delete('/invitations/{invitation}', [WorkspaceController::class, 'cancelInvitation']);

            // Dynamic /{user} routes after static ones
            Route::put('/{user}', [WorkspaceController::class, 'updateMember'])->name('workspace.members.update');
            Route::get('/{user}', [WorkspaceController::class, 'showMember'])->name('workspace.members.show');
            Route::delete('/{user}', [WorkspaceController::class, 'removeMember']);
        });

        // Workspace Projects
        Route::get('/{workspace}/projets', [WorkspaceController::class, 'projets']);

        // Workspace Statistics
        Route::get('/{workspace}/statistics', [WorkspaceController::class, 'statistics']);

        // Subscription summary (used by the trial banner)
        Route::get('/{workspace}/subscription', [WorkspaceController::class, 'subscriptionSummary'])->name('workspaces.subscription');

        // Super-admin: configure trial duration per workspace
        Route::patch('/{workspace}/subscription', [WorkspaceController::class, 'updateSubscription'])->name('workspaces.subscription.update');

        // ========================================  MEMBRE REMOVAL WITH TRANSFER  ========================================
        Route::prefix('/{workspace}')->group(function () {

            // Aperçu de l'impact du retrait d'un membre
            Route::get('members/{user}/removal-preview', [WorkspaceController::class, 'getRemovalPreview'])
                ->name('workspaces.members.removal-preview');

            // Obtenir les projets où l'utilisateur est responsable
            Route::get('members/{user}/projects', [WorkspaceController::class, 'getUserProjects'])
                ->name('workspaces.members.projects');

            // Obtenir les candidats pour le transfert
            Route::get('transfer-candidates', [WorkspaceController::class, 'getTransferCandidates'])
                ->name('workspaces.transfer-candidates');

            // Retirer un membre avec transfert de responsabilités
            Route::delete('members/{user}/remove', [WorkspaceController::class, 'removeMemberWithTransfer'])
                ->name('workspaces.members.remove-with-transfer');

            // Retirer un membre (simple, vérifie les responsabilités)
            Route::delete('members/{user}', [WorkspaceController::class, 'removeMember'])
                ->name('workspaces.members.remove');
        });
    });

    // Task 10: Vue globale des tâches du workspace (owner/directeur uniquement)
    Route::get('/workspace/taches', [TacheController::class, 'workspaceTaches'])->name('workspace.taches');
    // Task 16: Export Excel des tâches du workspace
    Route::get('/workspace/taches/export-excel', [TacheController::class, 'exportWorkspaceTachesExcel'])->name('workspace.taches.export-excel');

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

        Route::get('/{projet}/members/{user}/removal-impact', [ProjetController::class, 'getMemberRemovalImpact']);
        Route::delete('/{projet}/members/{user}/remove', [ProjetController::class, 'removeMemberWithTransfer']);

    });

    // Activity Management Routes
    Route::prefix('activites')->group(function () {

        // ✅ Routes statiques — doivent précéder les routes wildcard /{activite}
        Route::get('/mes-activites', [ActiviteController::class, 'myActivites']);
        Route::get('/my-activites', [ActiviteController::class, 'myActivites']);
        Route::get('/en-retard', [ActiviteController::class, 'enRetard']);
        Route::get('/projet/{projetId}', [ActiviteController::class, 'forProjet']);
        Route::get('/available-members/{projetId}', [ActiviteController::class, 'availableMembers']);
        Route::get('/all/activity', [ActiviteController::class, 'index']);
        Route::post('/reorder', [ActiviteController::class, 'reorder']);

        // CRUD de base
        Route::post('/', [ActiviteController::class, 'store']);
        Route::get('/{activite}', [ActiviteController::class, 'show']);
        Route::put('/{activite}', [ActiviteController::class, 'update']);
        Route::delete('/{activite}', [ActiviteController::class, 'destroy']);
        Route::put('/{activite}/change-responsable', [ActiviteController::class, 'changeResponsable']);

        // Actions sur une activité spécifique
        Route::post('/{activite}/archive', [ActiviteController::class, 'archive']);
        Route::post('/{activite}/unarchive', [ActiviteController::class, 'unarchive']);
        Route::post('/{activite}/toggle-archive', [ActiviteController::class, 'toggleArchive']);
        Route::post('/{activite}/duplicate', [ActiviteController::class, 'duplicate']);

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

        Route::get('/my-tasks-as-responsable', [TacheController::class, 'myTasksAsResponsable']);
        Route::post('/{tache}/assign-responsable', [TacheController::class, 'assignResponsable']);
        Route::delete('/{tache}/remove-responsable', [TacheController::class, 'removeResponsable']);

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
        Route::get('/{tache}', [TacheController::class, 'show'])->name('taches.show');
        Route::put('/{tache}', [TacheController::class, 'update']);
        Route::patch('/{tache}', [TacheController::class, 'update']);
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
        Route::get('/{tache}/sous-taches', [SousTacheController::class, 'index']);
        Route::post('/{tache}/sous-taches', [SousTacheController::class, 'store']);

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

    // ======================================== SOUS-TÂCHES ========================================
    Route::prefix('sous-taches')->group(function () {
        Route::put('/{sousTache}', [SousTacheController::class, 'update']);
        Route::delete('/{sousTache}', [SousTacheController::class, 'destroy']);
        Route::post('/{sousTache}/intervenants', [SousTacheController::class, 'assignIntervenant']);
        Route::delete('/{sousTache}/intervenants/{user}', [SousTacheController::class, 'removeIntervenant']);
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

        // N0 circuit actions
        Route::post('/{resultat}/approuver-n0', [TacheResultatController::class, 'approuverN0']);
        Route::post('/{resultat}/renvoyer-n0', [TacheResultatController::class, 'renvoyerN0']);

        // Anti-sabotage bypass (R3 + R5)
        Route::post('/{resultat}/activer-bypass', [TacheResultatController::class, 'activerBypass']);

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

        // 📋 Validator queue: results awaiting MY action as N1 or N2 (by responsable_id scope)
        Route::get('/resultats/en-attente', [EvaluationController::class, 'pendingValidations']);

        // G5: Assignee view — results I submitted that are waiting on someone else's action
        Route::get('/mes-resultats/en-attente', [EvaluationController::class, 'mesResultatsEnAttente']);

        // Task 7: Pending validations dashboard + score query
        Route::get('/validations/en-attente', [EvaluationController::class, 'pendingValidationsDashboard']);
        Route::get('/score', [EvaluationController::class, 'userScore']);

        // Task 10: Evaluation dashboard (scores équipe, top performers, alertes)
        Route::get('/tableau-de-bord', [EvaluationController::class, 'evaluationDashboard']);

        // Task 9: full agent evaluation sheet (8 criteria + indicators).
        // Réponse JSON consommée par la page /evaluations/personnel/:id/historique.
        Route::get('/personnel/{user}/score', [EvaluationController::class, 'agentSheet']);

        // Task 9: historique paginé en 4 sections (directed tasks, directed
        // subtasks, assignee tasks, assignee subtasks) pour la même page.
        Route::get('/personnel/{user}/historique', [EvaluationController::class, 'agentSheetSections']);

        // Task 16: Export PDF de la fiche d'évaluation
        Route::get('/personnel/{user}/export-pdf', [EvaluationController::class, 'exportAgentSheetPdf'])->name('evaluations.export-pdf');

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

    // CDC §6 — journal d'audit de validation par tâche
    Route::get('/audit-logs/validation/{tache}', [EvaluationController::class, 'auditLogs'])
        ->name('audit-logs.validation');

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
         * POST /api/documents
         * Upload un ou plusieurs documents
         * Body: files[], documentable_type, documentable_id, description, visibility, disk
         */
        Route::post('/', [DocumentController::class, 'store'])->name('documents.store')->middleware('subscription.limits:upload_file');

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

        /**
         * POST /api/documents/{document}/share-by-email
         * Partage par email à un destinataire externe (peut ne pas avoir de compte)
         * Body: email
         */
        Route::post('/{document}/share-by-email', [DocumentController::class, 'shareByEmail'])->name('documents.share-by-email');
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
        Route::delete('/profile', [UserController::class, 'deleteAccount']);
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
        Route::get('/', [LabelTemplateController::class, 'index']);
        Route::get('/default', [LabelTemplateController::class, 'getDefault']);
        Route::get('/predefined', [LabelTemplateController::class, 'predefined']);
        Route::post('/', [LabelTemplateController::class, 'store']);
        Route::get('/{labelTemplate}', [LabelTemplateController::class, 'show']);
        Route::put('/{labelTemplate}', [LabelTemplateController::class, 'update']);
        Route::delete('/{labelTemplate}', [LabelTemplateController::class, 'destroy']);
        Route::post('/{labelTemplate}/apply', [LabelTemplateController::class, 'apply']);
        Route::post('/{labelTemplate}/duplicate', [LabelTemplateController::class, 'duplicate']);
        Route::post('/{labelTemplate}/set-default', [LabelTemplateController::class, 'setDefault']);
    });

    // Task Labels routes (attach/detach labels to tasks)
    Route::prefix('taches/{tache}/labels')->group(function () {
        Route::get('/', [TacheLabelController::class, 'index']);
        Route::post('/sync', [TacheLabelController::class, 'sync']);
        Route::post('/attach', [TacheLabelController::class, 'attach']);
        Route::post('/detach', [TacheLabelController::class, 'detach']);
        Route::delete('/detach-all', [TacheLabelController::class, 'detachAll']);
    });

    // Comment Management Routes
    Route::prefix('comments')->group(function () {
        // Routes statiques — doivent précéder les routes wildcard /{comment}
        Route::get('/', [CommentController::class, 'index']);
        Route::get('/mentions/unread', [CommentController::class, 'unreadMentions']);
        Route::post('/mentions/mark-read', [CommentController::class, 'markMentionsAsRead']);

        // CRUD operations
        Route::post('/', [CommentController::class, 'store']);
        Route::get('/{comment}', [CommentController::class, 'show']);
        Route::put('/{comment}', [CommentController::class, 'update']);
        Route::delete('/{comment}', [CommentController::class, 'destroy']);

        // Reactions
        Route::post('/{comment}/reactions', [CommentController::class, 'toggleReaction']);

        // Attachments
        Route::post('/{comment}/attachments', [CommentController::class, 'addAttachment']);
        Route::delete('/{comment}/attachments/{attachment}', [CommentController::class, 'deleteAttachment']);
    });

    // Activity Log Routes
    Route::prefix('activities')->group(function () {
        // Get activity feed for dashboard
        Route::get('/feed', [ActivityController::class, 'feed']);

        // Get recent activities
        Route::get('/recent', [ActivityController::class, 'recent']);

        // Get activities for a specific subject
        Route::get('/subject', [ActivityController::class, 'forSubject']);

        // Get activities by user
        Route::get('/user', [ActivityController::class, 'byUser']);

        // Get activities by log name
        Route::get('/log-name', [ActivityController::class, 'byLogName']);

        // Get activities by date range
        Route::get('/date-range', [ActivityController::class, 'byDateRange']);

        // Get activity statistics
        Route::get('/stats', [ActivityController::class, 'stats']);
    });

    // Notification routes
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/all', [NotificationController::class, 'all']);
        Route::get('/grouped', [NotificationController::class, 'grouped']);
        Route::get('/statistics', [NotificationController::class, 'statistics']);
        Route::post('/mark-all-read', [NotificationController::class, 'markAllAsRead']);
        Route::post('/{id}/mark-read', [NotificationController::class, 'markAsRead']);
        Route::delete('/delete-all-read', [NotificationController::class, 'deleteAllRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // Notification preferences
    Route::prefix('notification-preferences')->group(function () {
        Route::get('/', [NotificationPreferenceController::class, 'show']);
        Route::put('/', [NotificationPreferenceController::class, 'update']);
    });

    // Task 8b — Web Push subscription management
    Route::prefix('webpush')->group(function () {
        Route::get('/vapid-key', [PushSubscriptionController::class, 'vapidKey']);
        Route::get('/subscriptions', [PushSubscriptionController::class, 'index']);
        Route::post('/subscribe', [PushSubscriptionController::class, 'subscribe']);
        Route::delete('/unsubscribe', [PushSubscriptionController::class, 'unsubscribe']);
    });

    // Team Management Routes
    Route::prefix('teams')->group(function () {
        // List and my teams
        Route::get('/', [TeamController::class, 'index']);
        Route::get('/my-teams', [TeamController::class, 'myTeams']);
        Route::get('/unread-total', [TeamController::class, 'totalUnread']);

        // CRUD
        Route::post('/', [TeamController::class, 'store']);
        Route::get('/{uuid}', [TeamController::class, 'show']);
        Route::put('/{uuid}', [TeamController::class, 'update']);
        Route::delete('/{uuid}', [TeamController::class, 'destroy']);

        // Actions
        Route::post('/{uuid}/archive', [TeamController::class, 'archive']);
        Route::post('/{uuid}/restore', [TeamController::class, 'restore']);
        Route::post('/{uuid}/avatar', [TeamController::class, 'uploadAvatar']);

        // Stats and activity
        Route::get('/{uuid}/stats', [TeamController::class, 'stats']);
        Route::get('/{uuid}/activities', [TeamController::class, 'activities']);
        Route::get('/{uuid}/online-members', [TeamController::class, 'onlineMembers']);
        Route::post('/{uuid}/presence', [TeamController::class, 'updatePresence']);

        // Members
        Route::post('/{uuid}/members', [TeamMemberController::class, 'store']);
        Route::put('/{uuid}/members/{userId}/role', [TeamMemberController::class, 'updateRole']);
        Route::put('/{uuid}/members/{userId}/permissions', [TeamMemberController::class, 'updatePermissions']);
        Route::delete('/{uuid}/members/{userId}', [TeamMemberController::class, 'destroy']);
        Route::post('/{uuid}/transfer-ownership', [TeamMemberController::class, 'transferOwnership']);

        // Messages
        Route::get('/{uuid}/messages', [TeamMessageController::class, 'index']);
        Route::get('/{uuid}/messages/pinned', [TeamMessageController::class, 'pinned']);
        Route::post('/{uuid}/messages', [TeamMessageController::class, 'store']);
        Route::post('/{uuid}/read', [TeamMessageController::class, 'markRead']);
        Route::patch('/messages/{uuid}', [TeamMessageController::class, 'update']);
        Route::delete('/messages/{uuid}', [TeamMessageController::class, 'destroy']);
        Route::post('/messages/{uuid}/pin', [TeamMessageController::class, 'togglePin']);
        Route::post('/messages/{uuid}/reactions', [TeamMessageController::class, 'addReaction']);
        Route::delete('/messages/{uuid}/reactions', [TeamMessageController::class, 'removeReaction']);

        // Announcements
        Route::get('/{uuid}/announcements', [TeamAnnouncementController::class, 'index']);
        Route::post('/{uuid}/announcements', [TeamAnnouncementController::class, 'store']);
        Route::put('/{uuid}/announcements/{announcement}', [TeamAnnouncementController::class, 'update']);
        Route::delete('/{uuid}/announcements/{announcement}', [TeamAnnouncementController::class, 'destroy']);

        // Resources
        Route::get('/{uuid}/resources', [TeamResourceController::class, 'index']);
        Route::post('/{uuid}/resources', [TeamResourceController::class, 'store']);
        Route::put('/{uuid}/resources/{resource}', [TeamResourceController::class, 'update']);
        Route::delete('/{uuid}/resources/{resource}', [TeamResourceController::class, 'destroy']);

        // Events
        Route::get('/{uuid}/events', [TeamEventController::class, 'index']);
        Route::post('/{uuid}/events', [TeamEventController::class, 'store']);
        Route::get('/{uuid}/events/{event}', [TeamEventController::class, 'show']);
        Route::put('/{uuid}/events/{event}', [TeamEventController::class, 'update']);
        Route::delete('/{uuid}/events/{event}', [TeamEventController::class, 'destroy']);
    });
});
