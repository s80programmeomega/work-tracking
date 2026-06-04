<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Models\Document;
use App\Services\DocumentTextExtractorService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Extrait le contenu textuel d'un document et le stocke dans content_text,
 * puis re-indexe le document dans Typesense (Scout).
 *
 * Déclenché après chaque upload via DocumentController::store().
 * Peut aussi être lancé en masse via `php artisan documents:extract-text`.
 */
class ExtractDocumentTextJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 120;

    public function __construct(public readonly Document $document) {}

    public function handle(DocumentTextExtractorService $extractor): void
    {
        Log::info('Extraction de texte démarrée pour le document', [
            'document_id' => $this->document->id,
            'nom' => $this->document->nom,
            'mime_type' => $this->document->mime_type,
        ]);

        $text = $extractor->extract($this->document);

        // Mise à jour silencieuse — évite de déclencher les observers Spatie
        $this->document->updateQuietly(['content_text' => $text]);

        // Re-indexation dans Typesense avec le nouveau contenu textuel
        if (config('scout.driver') !== 'null') {
            $this->document->searchable();
        }

        Log::info('Extraction de texte terminée pour le document', [
            'document_id' => $this->document->id,
            'extracted_chars' => $text ? mb_strlen($text) : 0,
        ]);
    }
}
