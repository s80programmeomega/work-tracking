<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProfileRequest extends FormRequest
{
    public function authorize()
    {
        return true; // User can always update their own profile
    }

    public function rules()
    {
        $userId = auth()->id();

        return [
            'nom' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'string', 'email', 'max:255', 'unique:users,email,' . $userId],
            'numero_telephone' => ['nullable', 'string', 'max:20'],
            'fonction' => ['nullable', 'string', 'max:100'],
            'bio' => ['nullable', 'string', 'max:500'],
            'adresse' => ['nullable', 'string', 'max:255'],
            'language' => ['nullable', 'string', 'in:fr,en,es'],
            'timezone' => ['nullable', 'string', 'timezone'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,gif', 'max:5120'], // 5MB max
            'social_links' => ['nullable', 'array'],
            'social_links.linkedin' => ['nullable', 'url'],
            'social_links.twitter' => ['nullable', 'url'],
            'social_links.github' => ['nullable', 'url'],
        ];
    }

    public function messages()
    {
        return [
            'nom.required' => 'Le nom est obligatoire',
            'email.required' => 'L\'email est obligatoire',
            'email.unique' => 'Cet email est déjà utilisé',
            'avatar.image' => 'Le fichier doit être une image',
            'avatar.max' => 'L\'image ne doit pas dépasser 5MB',
        ];
    }
}