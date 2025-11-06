<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Préparation avant validation : s'assurer que les clés existent
     */
    protected function prepareForValidation()
    {
        $this->merge([
            'prenom' => $this->prenom ?? null,
            'nom' => $this->nom ?? null,
            'email' => $this->email ?? null,
        ]);
    }

    /**
     * Règles de validation
     */
    public function rules(): array
    {
        return [
            'prenom' => ['nullable', 'string', 'max:255'],
            'nom' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => [
                'required',
                'confirmed',
                Password::min(5)
                    // ->mixedCase()
                    // ->numbers()
                    // ->symbols()
            ],
            'role' => ['sometimes', 'string', 'in:super_admin,manager,responsable_n1,responsable_n2,cadre,stagiaire'],
        ];
    }

    /**
     * Validation complémentaire : exiger au moins un prénom ou un nom
     */
    public function withValidator($validator)
    {
        // $validator->after(function ($validator) {
        //     if (empty($this->prenom) && empty($this->nom)) {
        //         // ✅ On ajoute une seule erreur pour éviter les doublons
        //         $validator->errors()->add('prenom', 'Please provide at least a first name or a last name');
        //     }
        // });
    }

    /**
     * Messages personnalisés
     */
    public function messages(): array
    {
        return [
            'prenom.string' => 'The first name must be a valid text.',
            'nom.string' => 'The last name must be a valid text.',
            'email.required' => 'Email is required.',
            'email.email' => 'Please provide a valid email address.',
            'email.unique' => 'An account with this email already exists.',
            'password.required' => 'Password is required.',
            'password.confirmed' => 'Passwords do not match.',
            'password.min' => 'Password must be at least 8 characters.',
        ];
    }

    /**
     * Données à sauvegarder dans la base
     */
    public function validatedData(): array
    {
        return [
            // La colonne dans ta BD est "nom", on fusionne prénom + nom
            'nom'      => $this->nom,
            'prenom'   => $this->prenom ,
            'email'    => $this->email,
            'password' => bcrypt($this->password),
            'role'     => $this->role ?? 'stagiaire',
        ];
    }
}
