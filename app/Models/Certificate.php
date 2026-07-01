<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'titre',
        'organisme_emetteur',
        'date_obtention',
        'date_expiration',
        'credential_id',
        'credential_url',
        'description',
        'ordre',
    ];

    protected $casts = [
        'date_obtention' => 'date',
        'date_expiration' => 'date',
        'ordre' => 'integer',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
