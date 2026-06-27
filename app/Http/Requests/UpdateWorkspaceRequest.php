<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Prépare les données avant la validation
     */
    protected function prepareForValidation()
    {
        // ✅ Gestion flexible des settings (objet ou string)
        if ($this->has('settings')) {
            $settings = $this->input('settings');

            // Si c'est déjà un array (depuis JSON body ou form-data object)
            if (is_array($settings)) {
                $this->merge([
                    'settings_array' => $settings,
                    'settings' => json_encode($settings), // Convertir en string pour validation
                ]);
            }
            // Si c'est une string JSON
            elseif (is_string($settings)) {
                $decoded = json_decode($settings, true);

                if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $this->merge([
                        'settings_array' => $decoded,
                    ]);
                }
            }
        }

        // Convertir is_active en boolean si c'est une string
        if ($this->has('is_active')) {
            $isActive = $this->input('is_active');

            if (is_string($isActive)) {
                $this->merge([
                    'is_active' => in_array($isActive, ['1', 'true', 'on'], true),
                ]);
            } elseif (is_numeric($isActive)) {
                $this->merge([
                    'is_active' => (bool) $isActive,
                ]);
            }
        }

        // Convertir remove_logo en boolean
        if ($this->has('remove_logo')) {
            $removeLogo = $this->input('remove_logo');

            if (is_string($removeLogo)) {
                $this->merge([
                    'remove_logo' => in_array($removeLogo, ['1', 'true', 'on'], true),
                ]);
            }
        }

        // Debug: Log des données reçues
        \Log::info('Données de mise à jour workspace', [
            'has_logo' => $this->hasFile('logo'),
            'has_settings' => $this->has('settings'),
            'settings_type' => $this->has('settings') ? gettype($this->input('settings')) : 'null',
            'is_active' => $this->input('is_active'),
            'remove_logo' => $this->input('remove_logo'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'nom' => 'sometimes|required|string|min:3|max:255',
            'description' => 'nullable|string|max:500',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'is_active' => 'sometimes|boolean',
            'remove_logo' => 'nullable|boolean',
            'settings' => 'nullable|string|json', // ✅ Accepter string JSON valide

            // Validation des settings décodées
            'settings_array' => 'nullable|array',
            'settings_array.language' => 'nullable|string|in:fr,en',
            'settings_array.timezone' => 'nullable|string',
            'settings_array.visibility' => 'nullable|string|in:public,private,internal',
            'settings_array.members_can_create_projects' => 'nullable|boolean',
            'settings_array.members_can_invite' => 'nullable|boolean',
            'settings_array.members_can_delete_projects' => 'nullable|boolean',
            'settings_array.require_task_validation' => 'nullable|boolean',
            'settings_array.require_approval_for_time_off' => 'nullable|boolean',
            'settings_array.notify_on_new_member' => 'nullable|boolean',
            'settings_array.notify_on_new_project' => 'nullable|boolean',
            'settings_array.notify_on_task_assigned' => 'nullable|boolean',
            'settings_array.notify_on_deadline_approaching' => 'nullable|boolean',
            'settings_array.weekly_digest' => 'nullable|boolean',
        ];
    }

    /**
     * Messages de validation personnalisés
     */
    public function messages(): array
    {
        return [
            'nom.required' => 'Le nom du workspace est obligatoire',
            'nom.min' => 'Le nom doit contenir au moins 3 caractères',
            'nom.max' => 'Le nom ne peut pas dépasser 255 caractères',

            'description.max' => 'La description ne peut pas dépasser 500 caractères',

            'logo.image' => 'Le fichier doit être une image',
            'logo.mimes' => 'L\'image doit être au format: jpeg, png, jpg, gif ou webp',
            'logo.max' => 'L\'image ne peut pas dépasser 2 MB',

            'is_active.boolean' => 'Le statut doit être actif ou inactif',

            'settings.string' => 'Le format des paramètres est invalide',
            'settings.json' => 'Les paramètres doivent être au format JSON valide',
        ];
    }

    /**
     * Attributs personnalisés pour les messages d'erreur
     */
    public function attributes(): array
    {
        return [
            'nom' => 'nom du workspace',
            'description' => 'description',
            'logo' => 'logo',
            'is_active' => 'statut',
        ];
    }
}
