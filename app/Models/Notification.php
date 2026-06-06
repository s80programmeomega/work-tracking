<?php

namespace App\Models;

use Illuminate\Notifications\DatabaseNotification;
use Laravel\Scout\Searchable;

/**
 * Modèle de notification indexable par Scout.
 *
 * Étend le DatabaseNotification standard de Laravel (même table `notifications`)
 * en ajoutant la recherche plein-texte. User::notifications() pointe vers ce
 * modèle, ce qui permet l'indexation automatique des nouvelles notifications.
 *
 * Confidentialité : la recherche de notifications est toujours limitée aux
 * notifications de l'utilisateur courant (voir SearchController::searchNotifications).
 */
class Notification extends DatabaseNotification
{
    use Searchable;

    public function toSearchableArray(): array
    {
        $data = is_array($this->data) ? $this->data : (json_decode((string) $this->data, true) ?? []);

        // Concaténer toutes les valeurs scalaires du payload pour la recherche plein-texte
        $content = collect($data)
            ->filter(fn ($v) => is_scalar($v))
            ->map(fn ($v) => (string) $v)
            ->implode(' ');

        return [
            'id' => (string) $this->id,
            'type' => class_basename($this->type),
            'event' => isset($data['type']) ? (string) $data['type'] : '',
            'content' => $content,
            'notifiable_id' => (int) $this->notifiable_id,
            'notifiable_type' => (string) $this->notifiable_type,
            'is_read' => $this->read_at !== null,
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function searchableAs(): string
    {
        return 'notifications';
    }
}
