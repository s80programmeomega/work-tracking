<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;
use Spatie\Activitylog\Models\Activity;

/**
 * @property Activity $resource
 *
 * @mixin Activity
 */
class ActivityResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Activity log entry unique identifier.
     * @responseField log_name string Log channel name.
     * @responseField description string Event key (e.g. created, updated, deleted).
     * @responseField subject_type string Fully-qualified class name of the subject model.
     * @responseField subject_id integer|null ID of the subject model instance.
     * @responseField event string|null Event name (mirrors description for Spatie v3+).
     * @responseField causer_type string|null Fully-qualified class name of the causer.
     * @responseField causer_id integer|null ID of the causer.
     * @responseField properties object Key-value bag of changed attributes or custom data.
     * @responseField batch_uuid string|null UUID grouping related log entries.
     * @responseField created_at string ISO 8601 datetime when the event was logged.
     * @responseField causer object|null Causer summary (id, name, email) — when present.
     * @responseField subject object|null Subject summary (shape depends on subject type).
     * @responseField human_readable string Localised human-readable description of the event.
     * @responseField icon string Icon identifier for UI display.
     * @responseField color string Color name for UI display (e.g. green, red, blue).
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
        if (! $this->subject) {
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
        if (! $this->subject) {
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
                return 'un commentaire';
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
