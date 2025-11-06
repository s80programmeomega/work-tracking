<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TacheValidation extends Model
{
    protected $fillable = [
        'tache_id',
        'created_by',
        'responsable_n1_id',
        'responsable_n2_id',
        'status',
        'validated_by_n1_at',
        'validated_by_n2_at',
        'rejection_reason',
    ];

    protected $casts = [
        'validated_by_n1_at' => 'datetime',
        'validated_by_n2_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED_N1 = 'approved_n1';
    const STATUS_FULLY_APPROVED = 'fully_approved';
    const STATUS_REJECTED = 'rejected';

    public function tache(): BelongsTo
    {
        return $this->belongsTo(Tache::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function responsableN1(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_n1_id');
    }

    public function responsableN2(): BelongsTo
    {
        return $this->belongsTo(User::class, 'responsable_n2_id');
    }

    public function approveByN1(): void
    {
        $this->update([
            'status' => self::STATUS_APPROVED_N1,
            'validated_by_n1_at' => now(),
        ]);
    }

    public function approveByN2(): void
    {
        $this->update([
            'status' => self::STATUS_FULLY_APPROVED,
            'validated_by_n2_at' => now(),
        ]);
    }

    public function reject(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_REJECTED,
            'rejection_reason' => $reason,
        ]);
    }
}