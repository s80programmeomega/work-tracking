<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AuthService;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class AuthController extends Controller
{
    public function __construct(
        private AuthService $authService
    ) {
    }

    public function register_(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return response()->json([
                'message' => 'Registration successful',
                'user' => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        try {
            $user = $this->authService->register($request->validated());

            return response()->json([
                'message' => __('auth.registration_success'),
                'user' => new UserResource($user),
            ], 201);

        } catch (\Illuminate\Database\QueryException $e) {
            Log::error('Registration database error: ' . $e->getMessage());
            return response()->json([
                'message' => __('auth.registration_failed'),
            ], 500);

        } catch (\Exception $e) {
            Log::error('Registration error: ' . $e->getMessage());
            return response()->json([
                'message' => __('auth.registration_failed'),
            ], 500);
        }
    }

    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                $request->only('email', 'password'),
                $request->boolean('remember')
            );

            return response()->json([
                'message' => 'Login successful',
                'data' => [
                    'user' => new UserResource($result['user']),
                    'token' => $result['token'],
                    'token_type' => $result['token_type'],
                    'expires_at' => $result['expires_at'],
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Login failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();

            return response()->json([
                'message' => 'Logout successful',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Logout failed',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'data' => new UserResource(auth()->user()->load('roles', 'permissions')),
        ]);
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refreshToken();

            return response()->json([
                'data' => $result,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Token refresh failed',
                'error' => $e->getMessage(),
            ], 401);
        }
    }

    public function verifyEmail(Request $request): JsonResponse
    {
        $user = $request->user();

        if ($user->hasVerifiedEmail()) {
            return response()->json([
                'message' => 'Email already verified',
            ], 400);
        }

        $this->authService->verifyEmail($user);

        return response()->json([
            'message' => 'Email verified successfully',
        ]);
    }


    /**
     * Met à jour la langue de l'utilisateur
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateLanguage(Request $request): JsonResponse
    {
        // Validation de la requête
        $request->validate([
            'language' => 'required|in:en,fr', // Seules les langues supportées
        ]);

        try {
            // Récupère l'utilisateur connecté
            $user = auth()->user();

            // Met à jour la langue
            $user->update(['language' => $request->language]);

            return response()->json([
                'message' => __('Language updated successfully'), // Utilise les traductions !
                'language' => $user->language,
            ]);

        } catch (\Exception $e) {
            // Log l'erreur pour le débogage
            Log::error('Language update error: ' . $e->getMessage());

            return response()->json([
                'message' => __('Failed to update language'),
            ], 500);
        }
    }
}
