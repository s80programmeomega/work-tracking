<?php

namespace App\Http\Requests\Tache;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTacheRequest extends FormRequest
{
    public function authorize(): bool
    {
        // All authenticated users can attempt to update
        return true;
    }

    public function rules(): array
    {
        $tacheId = $this->route('tache')->id;
        $activiteId = $this->activite_id ?? $this->route('tache')->activite_id;

        return [
            'titre' => [
                'sometimes',
                'string',
                'max:255',
                'unique:taches,titre,' . $tacheId . ',id,activite_id,' . $activiteId
            ],
            'description' => ['nullable', 'string'],
            'objectif' => ['nullable', 'string'],
            'indicateurs_resultats' => ['nullable', 'string'],
            'statut' => ['sometimes', 'in:' . implode(',', TacheStatut::values())],
            'priorite' => ['sometimes', 'in:' . implode(',', TachePriorite::values())],
            'echeance' => ['nullable', 'date'],
            'date_debut' => ['nullable', 'date'],
            'date_fin_reelle' => ['nullable', 'date'],
            'taux_realisation' => ['nullable', 'integer', 'min:0', 'max:100'],
            'validation_superieur' => ['nullable', 'boolean'],
            'verrou_reevaluation' => ['nullable', 'boolean'],
            'commentaire' => ['nullable', 'string'],
            'validateur_id' => ['nullable', 'exists:users,id'],
            'position' => ['nullable', 'integer', 'min:0'],
            'couleur' => ['nullable', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'cover_image' => ['nullable', 'image', 'max:2048'],
            'metadata' => ['nullable', 'array'],
            'estimated_hours' => ['nullable', 'integer', 'min:0'],
            'actual_hours' => ['nullable', 'integer', 'min:0'],
            'assignee_ids' => ['nullable', 'array'],
            'assignee_ids.*' => ['exists:users,id'],
            'label_ids' => ['nullable', 'array'],
            'label_ids.*' => ['exists:labels,id'],
        ];
    }

    public function messages(): array
    {
        return [
            'titre.unique' => 'Une tâche avec ce titre existe déjà dans cette activité.',
            'titre.max' => 'Le titre ne doit pas dépasser 255 caractères.',
            'statut.in' => 'Le statut sélectionné est invalide.',
            'priorite.in' => 'La priorité sélectionnée est invalide.',
            'echeance.date' => 'La date d\'échéance doit être une date valide.',
            'taux_realisation.min' => 'Le taux de réalisation ne peut pas être négatif.',
            'taux_realisation.max' => 'Le taux de réalisation ne peut pas dépasser 100%.',
            'validateur_id.exists' => 'Le validateur sélectionné n\'existe pas.',
            'couleur.regex' => 'Le format de la couleur est invalide. Utilisez un code hexadécimal (ex: #FF5733).',
            'assignee_ids.array' => 'Les assignations doivent être un tableau.',
            'assignee_ids.*.exists' => 'Un des utilisateurs assignés n\'existe pas.',
        ];
    }
}
