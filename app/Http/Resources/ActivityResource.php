<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'log_name' => $this->log_name,
            'description' => $this->description,
            'subject_type' => $this->subject_type,
            'subject_id' => $this->subject_id,
            'event' => $this->event,
            'causer_type' => $this->causer_type,
            'causer_id' => $this->causer_id,
            'properties' => $this->properties,
            'batch_uuid' => $this->batch_uuid,
            'created_at' => $this->created_at->toISOString(),

            // Relationships
            'causer' => $this->when($this->causer, function () {
                return [
                    'id' => $this->causer->id,
                    'name' => $this->causer->name,
                    'email' => $this->causer->email,
                ];
            }),

            'subject' => $this->when($this->subject, function () {
                return $this->formatSubject();
            }),

            // Computed
            'human_readable' => $this->getHumanReadableDescription(),
            'icon' => $this->getIcon(),
            'color' => $this->getColor(),
        ];
    }

    /**
     * Format subject based on its type
     */
    protected function formatSubject(): array
    {
        if (!$this->subject) {
            return [];
        }

        $type = class_basename($this->subject_type);

        switch ($type) {
            case 'Projet':
                return [
                    'type' => 'projet',
                    'id' => $this->subject->id,
                    'nom' => $this->subject->nom,
                ];
            case 'Activite':
                return [
                    'type' => 'activite',
                    'id' => $this->subject->id,
                    'nom' => $this->subject->nom,
                ];
            case 'Tache':
                return [
                    'type' => 'tache',
                    'id' => $this->subject->id,
                    'titre' => $this->subject->titre,
                    'statut' => $this->subject->statut,
                ];
            case 'Comment':
                return [
                    'type' => 'comment',
                    'id' => $this->subject->id,
                    'content' => $this->subject->content,
                ];
            default:
                return [
                    'type' => strtolower($type),
                    'id' => $this->subject->id,
                ];
        }
    }

    /**
     * Get human-readable description
     */
    protected function getHumanReadableDescription(): string
    {
        $causer = $this->causer ? $this->causer->name : 'Système';
        $subject = $this->getSubjectName();

        switch ($this->description) {
            case 'created':
                return "{$causer} a créé {$subject}";
            case 'updated':
                return "{$causer} a modifié {$subject}";
            case 'deleted':
                return "{$causer} a supprimé {$subject}";
            case 'assigned':
                return "{$causer} a assigné {$subject}";
            case 'completed':
                return "{$causer} a terminé {$subject}";
            case 'status_changed':
                return "{$causer} a changé le statut de {$subject}";
            case 'comment_added':
                return "{$causer} a commenté sur {$subject}";
            default:
                return "{$causer} a effectué une action sur {$subject}";
        }
    }

    /**
     * Get subject name for display
     */
    protected function getSubjectName(): string
    {
        if (!$this->subject) {
            return 'un élément supprimé';
        }

        $type = class_basename($this->subject_type);

        switch ($type) {
            case 'Projet':
                return "le projet \"{$this->subject->nom}\"";
            case 'Activite':
                return "l'activité \"{$this->subject->nom}\"";
            case 'Tache':
                return "la tâche \"{$this->subject->titre}\"";
            case 'Comment':
                return "un commentaire";
            default:
                return "un {$type}";
        }
    }

    /**
     * Get icon for activity type
     */
    protected function getIcon(): string
    {
        return match ($this->description) {
            'created' => 'plus-circle',
            'updated' => 'edit',
            'deleted' => 'trash',
            'assigned' => 'user-plus',
            'completed' => 'check-circle',
            'status_changed' => 'refresh',
            'comment_added' => 'message-circle',
            default => 'activity',
        };
    }

    /**
     * Get color for activity type
     */
    protected function getColor(): string
    {
        return match ($this->description) {
            'created' => 'green',
            'updated' => 'blue',
            'deleted' => 'red',
            'assigned' => 'purple',
            'completed' => 'green',
            'status_changed' => 'orange',
            'comment_added' => 'blue',
            default => 'gray',
        };
    }
}
