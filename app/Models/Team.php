<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Team extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'responsable_id',
    ];

    /**
     * Get the team manager/responsible
     */
    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    /**
     * Get all team members
     */
    public function membres(): HasMany
    {
        return $this->hasMany(User::class, 'team_id');
    }

    /**
     * Get the team members count
     */
    public function getMembresCountAttribute(): int
    {
        return $this->membres()->count();
    }
}
