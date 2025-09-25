<?php

use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\ProjetController;
use App\Http\Controllers\TeamController;
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

// Authentication routes
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');

// Password reset routes
Route::post('/forgot-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'sendResetLink']);
Route::post('/reset-password', [App\Http\Controllers\Auth\ResetPasswordController::class, 'reset']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {
    // User profile routes
    Route::get('/user', [AuthController::class, 'user']);
    Route::get('/profile', [UserController::class, 'profile']);
    Route::put('/profile', [UserController::class, 'updateProfile']);
    Route::put('/profile/password', [UserController::class, 'changePassword']);

    // User management routes (admin)
    Route::apiResource('users', UserController::class)->except(['show']);

    // Team management routes
    Route::apiResource('teams', TeamController::class);
    Route::post('/teams/{team}/members', [TeamController::class, 'addMember']);
    Route::delete('/teams/{team}/members', [TeamController::class, 'removeMember']);
    Route::get('/teams/members/available', [TeamController::class, 'availableUsers']);

    // Projet management routes
    Route::apiResource('projets', ProjetController::class);
    Route::get('/projets-dashboard', [ProjetController::class, 'dashboard']);
    Route::get('/responsables', [ProjetController::class, 'responsables']);
});
