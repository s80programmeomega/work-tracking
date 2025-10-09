<?php

namespace App\Http\Requests\Tache;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use Illuminate\Foundation\Http\FormRequest;

class StoreTacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All authenticated users can create tasks
        return true;
    }

    public function rules(): array
    {
        return [
            'activite_id' => ['required', 'exists:activites,id'],
            'titre' => [
                'required',
                'string',
                'max:255',
                'unique:taches,titre,NULL,id,activite_id,' . $this->activite_id
            ],
            'description' => ['nullable', 'string'],
            'objectif' => ['nullable', 'string'],
            'indicateurs_resultats' => ['nullable', 'string'],
            'statut' => ['nullable', 'in:' . implode(',', TacheStatut::values())],
            'priorite' => ['nullable', 'in:' . implode(',', TachePriorite::values())],
            'echeance' => ['nullable', 'date', 'after_or_equal:today'],
            'taux_realisation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'validation_superieur' => ['nullable', 'boolean'],
            'verrou_reevaluation' => ['nullable', 'boolean'],
            'commentaire' => ['nullable', 'string'],
            'validateur_id' => ['nullable', 'exists:users,id'],
            'ordre' => ['nullable', 'integer', 'min:0'],
            'couleur' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'image_couverture' => ['nullable', 'string'],
            'metadata' => ['nullable', 'array'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['exists:users,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'activite_id.required' => 'L\'activité est requise.',
            'activite_id.exists' => 'L\'activité sélectionnée n\'existe pas.',
            'titre.required' => 'Le titre de la tâche est requis.',
            'titre.unique' => 'Une tâche avec ce titre existe déjà dans cette activité.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'priorite.in' => 'La priorité sélectionnée est invalide.',
            'echeance.date' => 'La date d\'échéance doit être une date valide.',
            'echeance.after_or_equal' => 'La date d\'échéance ne peut pas être dans le passé.',
            'taux_realisation.min' => 'Le taux de réalisation ne peut pas être négatif.',
            'taux_realisation.max' => 'Le taux de réalisation ne peut pas dépasser 100%.',
            'validateur_id.exists' => 'Le validateur sélectionné n\'existe pas.',
            'couleur.regex' => 'Le format de la couleur est invalide. Utilisez un code hexadécimal (ex: #FF5733).',
            'assignee_ids.array' => 'Les assignations doivent être un tableau.',
            'assignee_ids.*.exists' => 'Un des utilisateurs assignés n\'existe pas.',
        ];
    }
}
