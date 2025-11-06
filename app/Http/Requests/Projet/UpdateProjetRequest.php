<?php

namespace App\Http\Requests\Projet;

use Illuminate\Foundation\Http\FormRequest;

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
            'nom' => 'sometimes|required|string|max:255',
            'code' => "nullable|string|max:50|unique:projets,code,{$projetId}",
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
            'nom.required' => 'Le nom du projet est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'code.unique' => 'Ce code de projet existe déjà.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'status.in' => 'Le statut doit être: active, pending, completed ou archived.',
            'visibility.in' => 'La visibilité doit être: public, team ou private.',
            'progression.min' => 'La progression ne peut pas être négative.',
            'progression.max' => 'La progression ne peut pas dépasser 100%.',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'budget.min' => 'Le budget ne peut pas être négatif.',
            'tags.*.exists' => 'Un des tags sélectionnés n\'existe pas.',
        ];
    }
}