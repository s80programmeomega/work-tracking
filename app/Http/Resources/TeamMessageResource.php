<?php

namespace App\Http\Resources;

use App\Models\TeamMessage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TeamMessage $resource
 *
 * @mixin TeamMessage
 */
class TeamMessageResource extends JsonResource
{
    /**
     * @responseField uuid string Identifiant unique du message.
     * @responseField team_id integer ID de l'équipe.
     * @responseField content string Contenu du message.
     * @responseField mentions array IDs des utilisateurs mentionnés.
     * @responseField attachments array Pièces jointes {name, url, mime_type, size}.
     * @responseField is_pinned boolean Message épinglé.
     * @responseField is_edited boolean Message modifié.
     * @responseField edited_at string|null Date ISO 8601 de la dernière modification.
     * @responseField created_at string Date ISO 8601 d'envoi.
     * @responseField user object Auteur {id, nom, email, avatar_url}.
     * @responseField reply_to object|null Message parent {uuid, content_snippet, user_nom}.
     * @responseField reactions array Réactions groupées par emoji {emoji, count, did_react}.
     */
    public function toArray(Request $request): array
    {
        $authUserId = $request->user()?->id;

        // Grouper les réactions par emoji avec comptage et indicateur personnel
        $reactions = $this->whenLoaded('reactions', function () use ($authUserId) {
            return $this->reactions
                ->groupBy('emoji')
                ->map(fn ($group, $emoji) => [
                    'emoji' => $emoji,
                    'count' => $group->count(),
                    'did_react' => $authUserId
                        ? $group->contains('user_id', $authUserId)
                        : false,
                ])
                ->values()
                ->toArray();
        }, []);

        return [
            'uuid' => $this->uuid,
            'team_id' => $this->team_id,
            'content' => $this->content,
            'mentions' => $this->mentions ?? [],
            'attachments' => $this->attachments ?? [],
            'is_pinned' => $this->is_pinned,
            'is_edited' => $this->is_edited,
            'edited_at' => $this->edited_at?->toISOString(),
            'created_at' => $this->created_at->toISOString(),

            'user' => $this->when($this->relationLoaded('user'), fn () => [
                'id' => $this->user->id,
                'nom' => $this->user->nom,
                'email' => $this->user->email,
                'avatar_url' => $this->user->avatar_url ?? null,
            ]),

            'reply_to' => $this->when(
                $this->relationLoaded('replyTo') && $this->replyTo !== null,
                fn () => [
                    'uuid' => $this->replyTo->uuid,
                    'content_snippet' => mb_substr($this->replyTo->content, 0, 100),
                    'user_nom' => $this->replyTo->user?->nom,
                ]
            ),

            'reactions' => $reactions,
        ];
    }
}
