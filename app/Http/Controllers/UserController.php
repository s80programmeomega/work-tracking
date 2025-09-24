<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('user.view')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $users = User::with(['team', 'roles', 'permissions'])
                    ->select('id', 'nom', 'email', 'role', 'fonction', 'avatar', 'numero_telephone', 'team_id', 'created_at')
                    ->get();

        return response()->json([
            'success' => true,
            'users' => $users
        ]);
    }

    /**
     * Get current user profile
     */
    public function profile(Request $request): JsonResponse
    {
        $user = $request->user()->load(['team', 'roles', 'permissions']);

        return response()->json([
            'success' => true,
            'user' => $user
        ]);
    }

    /**
     * Update user profile
     */
    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'fonction' => 'sometimes|string|max:255',
            'numero_telephone' => 'sometimes|string|max:20',
            'avatar' => 'sometimes|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $validator->validated();

        // Handle avatar upload
        if ($request->hasFile('avatar')) {
            // Delete old avatar if exists
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $avatarPath = $request->file('avatar')->store('avatars', 'public');
            $updateData['avatar'] = $avatarPath;
        }

        $user->update($updateData);

        return response()->json([
            'success' => true,
            'user' => $user->fresh(),
            'message' => 'Profil mis à jour avec succès'
        ]);
    }

    /**
     * Change user password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:8|confirmed',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Le mot de passe actuel est incorrect'
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Mot de passe changé avec succès'
        ]);
    }

    /**
     * Create a new user (admin only)
     */
    public function store(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('user.create')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:super_admin,manager,responsable_n1,responsable_n2,cadre,stagiaire',
            'fonction' => 'nullable|string|max:255',
            'numero_telephone' => 'nullable|string|max:20',
            'team_id' => 'nullable|exists:teams,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $userData = $validator->validated();
        $userData['password'] = Hash::make($userData['password']);

        $newUser = User::create($userData);

        // Assign role using Spatie Permission
        $newUser->assignRole($userData['role']);

        return response()->json([
            'success' => true,
            'user' => $newUser->fresh(['team', 'roles']),
            'message' => 'Utilisateur créé avec succès'
        ], 201);
    }

    /**
     * Update a user (admin only)
     */
    public function update(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('user.edit')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        $validator = Validator::make($request->all(), [
            'nom' => 'sometimes|string|max:255',
            'email' => 'sometimes|email|unique:users,email,' . $targetUser->id,
            'role' => 'sometimes|in:super_admin,manager,responsable_n1,responsable_n2,cadre,stagiaire',
            'fonction' => 'sometimes|string|max:255',
            'numero_telephone' => 'sometimes|string|max:20',
            'team_id' => 'sometimes|nullable|exists:teams,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $updateData = $validator->validated();

        // Handle role update
        if (isset($updateData['role']) && $user->can('user.assign_role')) {
            $targetUser->syncRoles([$updateData['role']]);
            $targetUser->update(['role' => $updateData['role']]);
        }

        $targetUser->update($updateData);

        return response()->json([
            'success' => true,
            'user' => $targetUser->fresh(['team', 'roles']),
            'message' => 'Utilisateur mis à jour avec succès'
        ]);
    }

    /**
     * Delete a user (admin only)
     */
    public function destroy(Request $request, User $targetUser): JsonResponse
    {
        $user = $request->user();

        if (!$user->can('user.delete')) {
            return response()->json(['message' => 'Non autorisé'], 403);
        }

        // Prevent self-deletion
        if ($user->id === $targetUser->id) {
            return response()->json([
                'success' => false,
                'message' => 'Vous ne pouvez pas supprimer votre propre compte'
            ], 400);
        }

        // Remove avatar if exists
        if ($targetUser->avatar && Storage::disk('public')->exists($targetUser->avatar)) {
            Storage::disk('public')->delete($targetUser->avatar);
        }

        $targetUser->delete();

        return response()->json([
            'success' => true,
            'message' => 'Utilisateur supprimé avec succès'
        ]);
    }
}
