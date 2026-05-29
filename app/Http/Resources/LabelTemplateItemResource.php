<?php

namespace App\Http\Resources;

use App\Models\LabelTemplateItem;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property LabelTemplateItem $resource
 *
 * @mixin LabelTemplateItem
 */
class LabelTemplateItemResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    /**
     * @responseField id integer Item unique identifier.
     * @responseField label_template_id integer Parent template ID.
     * @responseField nom string Item name.
     * @responseField couleur string Hex background color.
     * @responseField text_color string|null Computed hex text color for contrast.
     * @responseField description string|null Optional description.
     * @responseField ordre integer Sort order within the template.
     * @responseField created_at string|null ISO datetime of creation.
     * @responseField updated_at string|null ISO datetime of last update.
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'label_template_id' => $this->label_template_id,
            'nom' => $this->nom,
            'couleur' => $this->couleur,
            'text_color' => $this->text_color,
            'description' => $this->description,
            'ordre' => $this->ordre,
            'created_at' => $this->created_at?->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at?->format('Y-m-d H:i:s'),
        ];
    }
}
