<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // Authorization handled in controller via PermissionService
    }

    public function rules(): array
    {
        /** @var User $user */
        $user = $this->route('user');
        $userId = $user->id;

        return [
            'nom' => ['sometimes', 'string', 'max:255'],
            'email' => ['sometimes', 'email', Rule::unique('users')->ignore($userId)],
            'password' => ['sometimes', 'nullable', 'min:8'],
            'role' => ['sometimes', 'in:super_admin,manager,responsable_n1,responsable_n2,cadre,stagiaire'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'numero_telephone' => ['nullable', 'string', 'max:20'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'language' => ['nullable', 'in:fr,en'],
            'timezone' => ['nullable', 'string', 'max:50'],
            'is_active' => ['sometimes', 'boolean'],
        ];
    }
}
