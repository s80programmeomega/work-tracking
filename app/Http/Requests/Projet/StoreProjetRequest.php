<?php

namespace App\Http\Requests\Projet;

use Illuminate\Foundation\Http\FormRequest;

class StoreProjetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // L'autorisation est gérée par la Policy
    }

    public function rules(): array
    {
        return [
            // Workspace requis
            'workspace_id' => 'required|exists:workspaces,id',
            
            'nom' => 'required|string|max:255',
            'code' => 'nullable|string|max:50|unique:projets,code',
            'description' => 'nullable|string',
            'date_debut' => 'required|date',
            'date_fin' => 'required|date|after:date_debut',
            'responsable_id' => 'required|exists:users,id',
            'status' => 'nullable|in:active,pending,completed,archived',
            'visibility' => 'nullable|in:public,team,private',
            'couleur' => 'nullable|string|max:7', // Format HEX
            'budget' => 'nullable|numeric|min:0',
            'objectifs' => 'nullable|string',
            'is_template' => 'nullable|boolean',
            'is_favorite' => 'nullable|boolean',
            'metadata' => 'nullable|array',
            
            // Relations
            'members' => 'nullable|array',
            'members.*.user_id' => 'required_with:members|exists:users,id',
            'members.*.role' => 'nullable|in:admin,member,viewer',
            'members.*.can_edit' => 'nullable|boolean',
            'members.*.can_delete' => 'nullable|boolean',
            'members.*.can_invite' => 'nullable|boolean',
            
            'tags' => 'nullable|array',
            'tags.*' => 'exists:projet_tags,id',
        ];
    }

    public function messages(): array
    {
        return [
            'workspace_id.required' => 'Le workspace est obligatoire.',
            'workspace_id.exists' => 'Le workspace sélectionné n\'existe pas.',
            
            'nom.required' => 'Le nom du projet est obligatoire.',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères.',
            'code.unique' => 'Ce code de projet existe déjà.',
            'date_debut.required' => 'La date de début est obligatoire.',
            'date_debut.date' => 'La date de début doit être une date valide.',
            'date_fin.required' => 'La date de fin est obligatoire.',
            'date_fin.after' => 'La date de fin doit être après la date de début.',
            'responsable_id.required' => 'Le responsable du projet est obligatoire.',
            'responsable_id.exists' => 'Le responsable sélectionné n\'existe pas.',
            'status.in' => 'Le statut doit être: active, pending, completed ou archived.',
            'visibility.in' => 'La visibilité doit être: public, team ou private.',
            'couleur.max' => 'La couleur doit être au format HEX (#RRGGBB).',
            'budget.numeric' => 'Le budget doit être un nombre.',
            'budget.min' => 'Le budget ne peut pas être négatif.',
            'members.*.user_id.exists' => 'Un des utilisateurs sélectionnés n\'existe pas.',
            'tags.*.exists' => 'Un des tags sélectionnés n\'existe pas.',
        ];
    }

    /**
     * Prepare data for validation
     */
    protected function prepareForValidation(): void
    {
        // Si workspace_id n'est pas fourni, utiliser le workspace actuel de l'utilisateur
        if (!$this->has('workspace_id') && auth()->user()->current_workspace_id) {
            $this->merge([
                'workspace_id' => auth()->user()->current_workspace_id
            ]);
        }
    }
}