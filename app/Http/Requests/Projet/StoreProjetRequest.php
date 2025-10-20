<?php

namespace App\Http\Requests\Projet;

use App\Enums\ProjetVisibility;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProjetRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'date_debut' => ['required', 'date'],
            'date_fin' => ['nullable', 'date', 'after:date_debut'],
            'responsable_id' => ['required', 'exists:users,id'],
            'visibility' => ['nullable', 'string', Rule::in(ProjetVisibility::values())],
            'couleur' => ['nullable', 'string', 'regex:/^#([A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$/'],
            'budget' => ['nullable', 'numeric', 'min:0'],
            'objectifs' => ['nullable', 'string'],
            'is_template' => ['nullable', 'boolean'],
            'is_favorite' => ['nullable', 'boolean'],
            'metadata' => ['nullable', 'array'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['exists:projet_tags,id'],
            'members' => ['nullable', 'array'],
            'members.*.user_id' => ['required', 'exists:users,id'],
            'members.*.role' => ['required', 'string', Rule::in(['owner', 'admin', 'member', 'viewer'])],
            'members.*.can_edit' => ['nullable', 'boolean'],
            'members.*.can_delete' => ['nullable', 'boolean'],
            'members.*.can_invite' => ['nullable', 'boolean'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du projet est requis.',
            'nom.max' => 'Le nom du projet ne doit pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est requise.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',
            'responsable_id.required' => 'Le responsable est requis.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'visibility.in' => 'La visibilité doit être public, private ou team.',
            'couleur.regex' => 'La couleur doit être un code hexadécimal valide.',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'budget.min' => 'Le budget ne peut pas être négatif.',
            'tags.*.exists' => 'Un ou plusieurs tags sélectionnés n\'existent pas.',
            'members.*.user_id.exists' => 'Un ou plusieurs utilisateurs sélectionnés n\'existent pas.',
            'members.*.role.in' => 'Le rôle doit être owner, admin, member ou viewer.',
        ];
    }
}
