<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $article_id
 * @property string $path
 * @property string $mime
 * @property int $size_bytes
 * @property int $uploaded_by
 * @property-read string $url
 */
class HelpArticleImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'article_id',
        'path',
        'mime',
        'size_bytes',
        'uploaded_by',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    protected $appends = ['url'];

    public function article(): BelongsTo
    {
        return $this->belongsTo(HelpArticle::class, 'article_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    /** URL publique de l'image (disque public). */
    protected function url(): Attribute
    {
        return Attribute::get(fn (): string => Storage::disk('public')->url($this->path));
    }
}
