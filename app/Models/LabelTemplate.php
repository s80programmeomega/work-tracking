<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LabelTemplate extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'type_workflow',
        'is_default',
        'created_by',
    ];

    protected $casts = [
        'is_default' => 'boolean',
    ];

    /**
     * Get the items for this template
     */
    public function items(): HasMany
    {
        return $this->hasMany(LabelTemplateItem::class)->orderBy('ordre');
    }

    /**
     * Get the user who created this template
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Apply this template to a project by creating labels
     */
    public function applyToProject(int $projetId): array
    {
        $createdLabels = [];

        foreach ($this->items as $item) {
            $label = Label::create([
                'projet_id' => $projetId,
                'nom' => $item->nom,
                'couleur' => $item->couleur,
                'description' => $item->description,
                'ordre' => $item->ordre,
                'is_global' => false,
                'created_by' => auth()->id(),
            ]);

            $createdLabels[] = $label;
        }

        return $createdLabels;
    }
}
