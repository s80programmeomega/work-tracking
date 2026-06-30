<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceChannelRead extends Model
{
    use HasFactory;

    /** @var array<int,string> */
    protected $fillable = [
        'user_id',
        'workspace_channel_id',
        'last_read_at',
    ];

    /** @var array<string,string> */
    protected $casts = [
        'last_read_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function channel(): BelongsTo
    {
        return $this->belongsTo(WorkspaceChannel::class, 'workspace_channel_id');
    }
}
