<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('user.create');
    }

    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', Password::defaults()],
            'role' => ['required', 'in:super_admin,manager,responsable_n1,responsable_n2,cadre,stagiaire'],
            'fonction' => ['nullable', 'string', 'max:255'],
            'numero_telephone' => ['nullable', 'string', 'max:20'],
            'team_id' => ['nullable', 'exists:teams,id'],
            'bio' => ['nullable', 'string', 'max:1000'],
            'adresse' => ['nullable', 'string', 'max:500'],
            'language' => ['nullable', 'in:fr,en'],
            'timezone' => ['nullable', 'string', 'max:50'],
        ];
    }

    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom est requis',
            'email.required' => 'L\'email est requis',
            'email.unique' => 'Cet email est déjà utilisé',
            'password.required' => 'Le mot de passe est requis',
            'role.required' => 'Le rôle est requis',
        ];
    }
}
