<?php

namespace App\Http\Resources;

use App\Models\LabelTemplate;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LabelTemplate $resource
 *
 * @mixin LabelTemplate
 */
class LabelTemplateResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Template unique identifier.
     * @responseField nom string Template name.
     * @responseField description string|null Optional description.
     * @responseField type_workflow string Workflow type this template targets.
     * @responseField is_default boolean Whether this is the default template.
     * @responseField items LabelTemplateItemResource[] Template items (when loaded).
     * @responseField items_count integer Total number of items.
     * @responseField creator object|null Creator user summary (id, name) — when loaded.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nom' => $this->nom,
            'description' => $this->description,
            'type_workflow' => $this->type_workflow,
            'is_default' => $this->is_default,
            'items' => LabelTemplateItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->items->count() ?? 0,
            'creator' => $this->whenLoaded('creator', function () {
                return [
                    'id' => $this->creator->id,
                    'name' => $this->creator->nom,
                ];
            }),
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
