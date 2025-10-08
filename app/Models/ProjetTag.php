<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class ProjetTag extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'couleur',
        'description',
    ];

    /**
     * Relationships
     */
    public function projets(): BelongsToMany
    {
        return $this->belongsToMany(Projet::class, 'projet_projet_tag')
            ->withTimestamps();
    }
}
