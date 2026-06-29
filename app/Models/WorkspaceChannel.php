<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class WorkspaceChannel extends Model
{
    use HasFactory;

    /** @var array<int,string> */
    protected $fillable = [
        'workspace_id',
        'type',
        'name',
    ];

    /** Types de canaux système */
    public const TYPE_RESPONSIBLES = 'responsibles';

    public const TYPE_GLOBAL = 'global';

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(WorkspaceMessage::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(WorkspaceChannelRead::class);
    }
}
