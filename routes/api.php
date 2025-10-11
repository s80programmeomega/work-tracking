<?php

use App\Http\Controllers\Api\AuthController;
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

// Protected routes
Route::middleware(['auth:sanctum'])->group(function () {
    Route::prefix('auth')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
        Route::post('/verify-email', [AuthController::class, 'verifyEmail']);
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

    // Project Management Routes
    Route::prefix('projets')->group(function () {
        // List and stats
        Route::get('/', [\App\Http\Controllers\ProjetController::class, 'index']);
        Route::get('/my-projets', [\App\Http\Controllers\ProjetController::class, 'myProjets']);
        Route::get('/dashboard-stats', [\App\Http\Controllers\ProjetController::class, 'dashboardStats']);

        // CRUD
        Route::post('/', [\App\Http\Controllers\ProjetController::class, 'store']);
        Route::get('/{projet}', [\App\Http\Controllers\ProjetController::class, 'show']);
        Route::put('/{projet}', [\App\Http\Controllers\ProjetController::class, 'update']);
        Route::delete('/{projet}', [\App\Http\Controllers\ProjetController::class, 'destroy']);

        // Actions
        Route::post('/{projet}/archive', [\App\Http\Controllers\ProjetController::class, 'archive']);
        Route::post('/{projet}/unarchive', [\App\Http\Controllers\ProjetController::class, 'unarchive']);
        Route::post('/{projet}/complete', [\App\Http\Controllers\ProjetController::class, 'complete']);
        Route::post('/{projet}/clone', [\App\Http\Controllers\ProjetController::class, 'clone']);
        Route::post('/{projet}/toggle-favorite', [\App\Http\Controllers\ProjetController::class, 'toggleFavorite']);

        // Members management
        Route::post('/{projet}/members', [\App\Http\Controllers\ProjetController::class, 'addMember']);
        Route::put('/{projet}/members/{userId}', [\App\Http\Controllers\ProjetController::class, 'updateMember']);
        Route::delete('/{projet}/members/{userId}', [\App\Http\Controllers\ProjetController::class, 'removeMember']);
    });

    // Activity Management Routes
    Route::prefix('activites')->group(function () {
        // List and filter
        Route::get('/', [\App\Http\Controllers\ActiviteController::class, 'index']);
        Route::get('/my-activites', [\App\Http\Controllers\ActiviteController::class, 'myActivites']);
        Route::get('/projet/{projetId}', [\App\Http\Controllers\ActiviteController::class, 'forProjet']);

        // CRUD
        Route::post('/', [\App\Http\Controllers\ActiviteController::class, 'store']);
        Route::get('/{activite}', [\App\Http\Controllers\ActiviteController::class, 'show']);
        Route::put('/{activite}', [\App\Http\Controllers\ActiviteController::class, 'update']);
        Route::delete('/{activite}', [\App\Http\Controllers\ActiviteController::class, 'destroy']);

        // Actions
        Route::post('/{activite}/archive', [\App\Http\Controllers\ActiviteController::class, 'archive']);
        Route::post('/{activite}/unarchive', [\App\Http\Controllers\ActiviteController::class, 'unarchive']);
        Route::post('/{activite}/duplicate', [\App\Http\Controllers\ActiviteController::class, 'duplicate']);
        Route::post('/reorder', [\App\Http\Controllers\ActiviteController::class, 'reorder']);
    });

    // Task Management Routes
    Route::prefix('taches')->group(function () {
        // List and filter
        Route::get('/', [\App\Http\Controllers\TacheController::class, 'index']);
        Route::get('/my-taches', [\App\Http\Controllers\TacheController::class, 'myTaches']);
        Route::get('/activite/{activiteId}', [\App\Http\Controllers\TacheController::class, 'forActivite']);

        // CRUD
        Route::post('/', [\App\Http\Controllers\TacheController::class, 'store']);
        Route::get('/{tache}', [\App\Http\Controllers\TacheController::class, 'show']);
        Route::put('/{tache}', [\App\Http\Controllers\TacheController::class, 'update']);
        Route::delete('/{tache}', [\App\Http\Controllers\TacheController::class, 'destroy']);

        // Actions
        Route::post('/{tache}/move', [\App\Http\Controllers\TacheController::class, 'move']);
        Route::post('/{tache}/reorder', [\App\Http\Controllers\TacheController::class, 'reorder']);
        Route::post('/{tache}/duplicate', [\App\Http\Controllers\TacheController::class, 'duplicate']);
        Route::post('/{tache}/archive', [\App\Http\Controllers\TacheController::class, 'archive']);
        Route::post('/{tache}/unarchive', [\App\Http\Controllers\TacheController::class, 'unarchive']);
        Route::post('/{tache}/validate', [\App\Http\Controllers\TacheController::class, 'validateTask']);
        Route::post('/{tache}/assign', [\App\Http\Controllers\TacheController::class, 'assignUser']);
        Route::post('/{tache}/unassign', [\App\Http\Controllers\TacheController::class, 'unassignUser']);
        Route::post('/{tache}/progress', [\App\Http\Controllers\TacheController::class, 'updateProgress']);
    });

    // Labels routes
    Route::prefix('labels')->group(function () {
        Route::get('/', [LabelController::class, 'index']);
        Route::post('/', [LabelController::class, 'store']);
        Route::get('/{label}', [LabelController::class, 'show']);
        Route::put('/{label}', [LabelController::class, 'update']);
        Route::delete('/{label}', [LabelController::class, 'destroy']);
        Route::post('/reorder', [LabelController::class, 'reorder']);
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

    // Document Management Routes
    Route::prefix('documents')->group(function () {
        // List and search
        Route::get('/', [\App\Http\Controllers\DocumentController::class, 'index']);
        Route::get('/search', [\App\Http\Controllers\DocumentController::class, 'search']);

        // Upload documents
        Route::post('/', [\App\Http\Controllers\DocumentController::class, 'store']);

        // Document operations
        Route::get('/{document}', [\App\Http\Controllers\DocumentController::class, 'show']);
        Route::put('/{document}', [\App\Http\Controllers\DocumentController::class, 'update']);
        Route::delete('/{document}', [\App\Http\Controllers\DocumentController::class, 'destroy']);

        // Download document
        Route::get('/{document}/download', [\App\Http\Controllers\DocumentController::class, 'download']);

        // Versioning
        Route::post('/{document}/versions', [\App\Http\Controllers\DocumentController::class, 'createVersion']);

        // Statistics
        Route::get('/{document}/stats', [\App\Http\Controllers\DocumentController::class, 'stats']);

        // Permissions
        Route::post('/{document}/permissions/grant', [\App\Http\Controllers\DocumentController::class, 'grantPermission']);
        Route::post('/{document}/permissions/revoke', [\App\Http\Controllers\DocumentController::class, 'revokePermission']);
    });
});
