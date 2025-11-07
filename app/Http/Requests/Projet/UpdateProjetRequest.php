<?php

namespace App\Http\Requests\Projet;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProjetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par la Policy
    }

    public function rules(): array
    {
        $projetId = $this->route('projet')->id;

        return [
            'workspace_id' => ['sometimes', 'required', 'exists:workspaces,id'],
            'nom' => 'sometimes|required|string|max:255',
             'code' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('projets', 'code')
                    ->ignore($projetId)
                    ->whereNull('deleted_at')
            ],
            'description' => 'nullable|string',
            'date_debut' => 'sometimes|required|date',
            'date_fin' => 'sometimes|required|date|after:date_debut',
            'responsable_id' => 'sometimes|required|exists:users,id',
            'status' => 'nullable|in:active,pending,completed,archived',
            'visibility' => 'nullable|in:public,team,private',
            'couleur' => 'nullable|string|max:7',
            'budget' => 'nullable|numeric|min:0',
            'progression' => 'nullable|integer|min:0|max:100',
            'objectifs' => 'nullable|string',
            'is_template' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'metadata' => 'nullable|array',
            
            // Relations
            'tags' => 'nullable|array',
            'tags.*' => 'exists:projet_tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'workspace_id.required' => 'Le workspace est requis.',
            'workspace_id.exists' => 'Le workspace sélectionné n\'existe pas.',
            'nom.required' => 'Le nom du projet est requis.',
            'nom.max' => 'Le nom du projet ne peut pas dépasser 255 caractères.',
            'code.unique' => 'Ce code projet existe déjà.',
            'code.max' => 'Le code ne peut pas dépasser 255 caractères.',
            'date_debut.required' => 'La date de début est requise.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est requise.',
            'date_fin.date' => 'La date de fin doit être une date valide.',
            'date_fin.after_or_equal' => 'La date de fin doit être après ou égale à la date de début.',
            'responsable_id.required' => 'Le responsable du projet est requis.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'status.in' => 'Le statut doit être : active, pending, completed ou archived.',
            'visibility.in' => 'La visibilité doit être : public, private ou team.',
            'couleur.max' => 'La couleur ne peut pas dépasser 7 caractères.',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'budget.min' => 'Le budget ne peut pas être négatif.',
            'progression.integer' => 'La progression doit être un nombre entier.',
            'progression.min' => 'La progression doit être au minimum 0%.',
            'progression.max' => 'La progression ne peut pas dépasser 100%.',
        ];
    }
}