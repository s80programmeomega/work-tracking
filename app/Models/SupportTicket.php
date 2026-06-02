<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $user_id
 * @property int|null $workspace_id
 * @property string $category
 * @property string $subject
 * @property string $message
 * @property string $status
 * @property string|null $reproducibility
 * @property string|null $steps_to_reproduce
 * @property Carbon|null $sla_deadline
 * @property Carbon|null $first_responded_at
 * @property Carbon|null $resolved_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * @property-read string $ticket_number  numéro formaté sur 6 chiffres ex: 000042
 * @property-read bool $sla_breached
 */
class SupportTicket extends Model
{
    use HasFactory;

    /** Délais SLA par catégorie (en heures) */
    public const SLA_HOURS = [
        'bug' => 4,
        'feature' => 48,
        'billing' => 8,
        'account' => 8,
        'other' => 24,
    ];

    protected $fillable = [
        'user_id',
        'workspace_id',
        'category',
        'subject',
        'message',
        'status',
        'reproducibility',
        'steps_to_reproduce',
        'sla_deadline',
        'first_responded_at',
        'resolved_at',
    ];

    protected $casts = [
        'sla_deadline' => 'datetime',
        'first_responded_at' => 'datetime',
        'resolved_at' => 'datetime',
    ];

    /** Numéro de ticket zéro-paddé sur 6 chiffres (ex: 000042). */
    public function getTicketNumberAttribute(): string
    {
        return str_pad((string) $this->id, 6, '0', STR_PAD_LEFT);
    }

    /** Vrai si le délai SLA est dépassé et le ticket n'est pas résolu. */
    public function getSlaBreachedAttribute(): bool
    {
        return $this->sla_deadline !== null
            && $this->sla_deadline->isPast()
            && $this->status !== 'resolved';
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function replies(): HasMany
    {
        return $this->hasMany(SupportTicketReply::class)->orderBy('created_at');
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(SupportTicketAttachment::class);
    }
}
