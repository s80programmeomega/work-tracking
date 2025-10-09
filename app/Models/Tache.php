<?php

namespace App\Models;

use App\Enums\TachePriorite;
use App\Enums\TacheStatut;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Tache extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'activite_id',
        'titre',
        'description',
        'objectif',
        'indicateurs_resultats',
        'statut',
        'priorite',
        'echeance',
        'taux_realisation',
        'validation_superieur',
        'verrou_reevaluation',
        'commentaire',
        'validateur_id',
        'ordre',
        'couleur',
        'image_couverture',
        'metadata',
    ];

    protected $casts = [
        'statut' => TacheStatut::class,
        'priorite' => TachePriorite::class,
        'echeance' => 'date',
        'validation_superieur' => 'boolean',
        'verrou_reevaluation' => 'boolean',
        'metadata' => 'array',
        'taux_realisation' => 'integer',
    ];

    protected $with = ['activite', 'assignees', 'validateur', 'labels'];

    /**
     * Get the activity that owns the task
     */
    public function activite(): BelongsTo
    {
        return $this->belongsTo(Activite::class);
    }

    /**
     * Get the users assigned to this task
     */
    public function assignees(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'tache_user')
            ->withTimestamps();
    }

    /**
     * Get the validator for this task
     */
    public function validateur(): BelongsTo
    {
        return $this->belongsTo(User::class, 'validateur_id');
    }

    /**
     * Get tasks that this task depends on
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(
            Tache::class,
            'tache_dependencies',
            'tache_id',
            'depends_on_tache_id'
        )->withTimestamps();
    }

    /**
     * Get tasks that depend on this task
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(
            Tache::class,
            'tache_dependencies',
            'depends_on_tache_id',
            'tache_id'
        )->withTimestamps();
    }

    /**
     * Get labels associated with this task
     */
    public function labels(): BelongsToMany
    {
        return $this->belongsToMany(Label::class, 'label_tache')
            ->withTimestamps();
    }

    /**
     * Check if the task is overdue
     */
    public function isOverdue(): bool
    {
        return $this->echeance &&
               $this->echeance->isPast() &&
               $this->statut !== TacheStatut::TERMINE;
    }

    /**
     * Check if task can be started (all dependencies completed)
     */
    public function canBeStarted(): bool
    {
        return $this->dependencies()
            ->where('statut', '!=', TacheStatut::TERMINE->value)
            ->count() === 0;
    }

    /**
     * Update task progress
     */
    public function updateProgress(int $taux): void
    {
        $this->taux_realisation = max(0, min(100, $taux));

        // Auto-update status based on progress
        if ($taux === 0) {
            $this->statut = TacheStatut::A_FAIRE;
        } elseif ($taux === 100) {
            $this->statut = TacheStatut::TERMINE;
        } else {
            $this->statut = TacheStatut::EN_COURS;
        }

        $this->save();
    }

    /**
     * Validate task by superior
     */
    public function validate(User $validator): void
    {
        $this->validation_superieur = true;
        $this->validateur_id = $validator->id;
        $this->save();
    }

    /**
     * Scope to filter by activite
     */
    public function scopeForActivite($query, int $activiteId)
    {
        return $query->where('activite_id', $activiteId);
    }

    /**
     * Scope to filter by status
     */
    public function scopeByStatut($query, TacheStatut $statut)
    {
        return $query->where('statut', $statut);
    }

    /**
     * Scope to get tasks assigned to a user
     */
    public function scopeAssignedTo($query, int $userId)
    {
        return $query->whereHas('assignees', function ($q) use ($userId) {
            $q->where('user_id', $userId);
        });
    }

    /**
     * Scope to get overdue tasks
     */
    public function scopeOverdue($query)
    {
        return $query->where('echeance', '<', now())
            ->where('statut', '!=', TacheStatut::TERMINE->value);
    }

    /**
     * Scope to order by custom order
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('ordre')->orderBy('created_at');
    }
}
