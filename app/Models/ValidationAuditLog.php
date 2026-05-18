<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ValidationAuditLog extends Model
{
    // Immutable — no updated_at (R6)
    public $timestamps = false;

    protected $fillable = [
        'tache_resultat_id',
        'actor_id',
        'action',
        'context',
        'created_at',
    ];

    protected $casts = [
        'context' => 'array',
        'created_at' => 'datetime',
    ];

    public static function boot(): void
    {
        parent::boot();
        static::creating(function (self $log) {
            $log->created_at = $log->created_at ?? now();
        });
    }

    public function resultat(): BelongsTo
    {
        return $this->belongsTo(TacheResultat::class, 'tache_resultat_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
