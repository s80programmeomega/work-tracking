<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Label extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'couleur',
        'description',
        'ordre',
    ];

    protected $casts = [
        'ordre' => 'integer',
    ];

    /**
     * Get taches associated with this label
     */
    public function taches(): BelongsToMany
    {
        return $this->belongsToMany(Tache::class, 'label_tache')
            ->withTimestamps();
    }

    /**
     * Scope to order labels by ordre
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre');
    }
}
