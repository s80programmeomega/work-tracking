<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Laravel\Scout\Searchable;
use Mews\Purifier\Facades\Purifier;

/**
 * @property int $id
 * @property int $category_id
 * @property string $slug
 * @property string $titre_fr
 * @property string $titre_en
 * @property string $body_fr
 * @property string $body_en
 * @property string $body_plain_fr
 * @property string $body_plain_en
 * @property string|null $cover_image
 * @property int $views_count
 * @property Carbon|null $published_at
 * @property int $created_by
 * @property int|null $updated_by
 */
class HelpArticle extends Model
{
    use HasFactory, Searchable, SoftDeletes;

    protected $fillable = [
        'category_id',
        'slug',
        'titre_fr',
        'titre_en',
        'body_fr',
        'body_en',
        'cover_image',
        'published_at',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'views_count' => 'integer',
        'published_at' => 'datetime',
    ];

    /**
     * Sanitise le HTML (défense en profondeur, en plus de la sanitisation
     * en contrôleur) et extrait le texte brut pour la recherche FULLTEXT.
     */
    protected static function booted(): void
    {
        static::saving(function (HelpArticle $article) {
            if ($article->isDirty('body_fr')) {
                $article->body_fr = Purifier::clean($article->body_fr, 'help-article');
                $article->body_plain_fr = self::toPlainText($article->body_fr);
            }
            if ($article->isDirty('body_en')) {
                $article->body_en = Purifier::clean($article->body_en, 'help-article');
                $article->body_plain_en = self::toPlainText($article->body_en);
            }
        });

        // Quand l'état de publication change, la visibilité des images rattachées
        // change aussi (elles ne sont indexées que si l'article est publié).
        // Scout ne resynchronise pas automatiquement les images sur un save de
        // l'article : on force leur réindexation ici.
        static::saved(function (HelpArticle $article) {
            if ($article->wasChanged('published_at')) {
                $article->images()->get()->each->searchable();
            }
        });
    }

    /**
     * Convertit du HTML en texte brut pour l'index FULLTEXT.
     * Insère un espace à la place des balises afin de ne pas coller
     * les mots de blocs adjacents (ex. "</p><h2>").
     */
    protected static function toPlainText(string $html): string
    {
        $withSpaces = preg_replace('/<[^>]+>/', ' ', $html) ?? '';

        return trim((string) preg_replace('/\s+/', ' ', html_entity_decode($withSpaces)));
    }

    /** Collection Scout dédiée. */
    public function searchableAs(): string
    {
        return 'help_articles';
    }

    /**
     * N'indexe que les articles publiés — les brouillons ne doivent jamais
     * apparaître dans la recherche globale.
     */
    public function shouldBeSearchable(): bool
    {
        return $this->published_at !== null && $this->published_at->lte(now());
    }

    /**
     * Données indexées : titres + texte brut bilingue, catégorie pour le contexte.
     * On n'indexe PAS le HTML brut (body_*) ni de données sensibles.
     *
     * @return array<string, mixed>
     */
    public function toSearchableArray(): array
    {
        $this->loadMissing('category');

        return [
            'id' => (string) $this->id,
            'slug' => $this->slug,
            'titre_fr' => $this->titre_fr,
            'titre_en' => $this->titre_en,
            'body_plain_fr' => $this->body_plain_fr,
            'body_plain_en' => $this->body_plain_en,
            'category_id' => (int) $this->category_id,
            'category_nom' => $this->category?->nom_fr ?? '',
            'created_at' => $this->created_at?->timestamp ?? 0,
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(HelpCategory::class, 'category_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function editor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function images(): HasMany
    {
        return $this->hasMany(HelpArticleImage::class, 'article_id');
    }

    /** Articles publiés uniquement. */
    public function scopePublished(Builder $query): Builder
    {
        return $query->whereNotNull('published_at')->where('published_at', '<=', now());
    }

    /**
     * Recherche FULLTEXT bilingue (titre + corps, FR + EN), mode booléen avec
     * préfixe pour matcher les débuts de mots. Repli LIKE pour SQLite.
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        if ($term === '') {
            return $query;
        }

        if (DB::connection($query->getModel()->getConnectionName())->getDriverName() === 'mysql') {
            // Réécrit "audit tache" → "+audit* +tache*" pour le matching par préfixe
            $boolean = collect(preg_split('/\s+/', $term))
                ->filter()
                ->map(fn ($w) => '+'.addcslashes($w, '+-<>()~*"@').'*')
                ->implode(' ');

            return $query->whereRaw(
                'MATCH(titre_fr, titre_en, body_plain_fr, body_plain_en) AGAINST (? IN BOOLEAN MODE)',
                [$boolean]
            );
        }

        // Repli (SQLite / tests sans FULLTEXT)
        return $query->where(function (Builder $q) use ($term) {
            foreach (['titre_fr', 'titre_en', 'body_plain_fr', 'body_plain_en'] as $col) {
                $q->orWhere($col, 'like', "%{$term}%");
            }
        });
    }
}
