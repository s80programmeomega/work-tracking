<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

class Projet extends Model
{
    use HasFactory;

    protected $fillable = [
        'nom',
        'description',
        'date_debut',
        'date_fin',
        'responsable_id',
        'budget',
        'status',
        'priorite',
    ];

    protected $casts = [
        'date_debut' => 'date',
        'date_fin' => 'date',
        'budget' => 'decimal:2',
    ];

    protected $appends = [
        'duree_jours',
        'jours_restants',
        'progression_temporelle',
    ];

    public function responsable(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_id');
    }

    public function activites(): HasMany
    {
        return $this->hasMany(Activite::class);
    }

    public function getDureeJoursAttribute(): int
    {
        return $this->date_debut->diffInDays($this->date_fin);
    }

    public function getJoursRestantsAttribute(): int
    {
        $now = Carbon::now();
        if ($now > $this->date_fin) {
            return 0;
        }
        return $now->diffInDays($this->date_fin);
    }

    public function getProgressionTemporelleAttribute(): float
    {
        $now = Carbon::now();
        $total = $this->date_debut->diffInDays($this->date_fin);
        $ecoule = $this->date_debut->diffInDays($now);

        if ($total <= 0) return 100;

        return min(100, max(0, ($ecoule / $total) * 100));
    }

    public function getProgressionGlobaleAttribute(): float
    {
        $activites = $this->activites()->get();
        if ($activites->isEmpty()) {
            return 0;
        }

        $totalProgression = $activites->sum('taux_realisation');
        return $totalProgression / $activites->count();
    }

    public function scopeActifs($query)
    {
        return $query->whereIn('status', ['planifie', 'en_cours']);
    }

    public function scopeParResponsable($query, $userId)
    {
        return $query->where('responsable_id', $userId);
    }

    public function scopeParPriorite($query, $priorite)
    {
        return $query->where('priorite', $priorite);
    }

    public function scopeParStatus($query, $status)
    {
        return $query->where('status', $status);
    }
}
