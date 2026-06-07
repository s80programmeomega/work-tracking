<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;
use Laravel\Scout\Searchable;

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
    use HasFactory, Searchable;

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

    /** Collection Scout dédiée. */
    public function searchableAs(): string
    {
        return 'help_images';
    }

    /**
     * N'indexe une image que si son article parent est publié — évite de
     * révéler des images rattachées à des brouillons via la recherche.
     */
    public function shouldBeSearchable(): bool
    {
        $article = $this->article;

        return $article !== null
            && $article->published_at !== null
            && $article->published_at->lte(now());
    }

    /**
     * Données indexées : nom de fichier (basename du chemin) + contexte article.
     * Les images n'ont pas de texte propre ; on indexe le nom de fichier et le
     * titre de l'article parent pour les rendre trouvables.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('article');

        return [
            'id' => (string) $this->id,
            'filename' => basename($this->path),
            'mime' => $this->mime,
            'article_id' => (int) $this->article_id,
            'article_slug' => $this->article?->slug ?? '',
            'article_titre_fr' => $this->article?->titre_fr ?? '',
            'article_titre_en' => $this->article?->titre_en ?? '',
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }
}
