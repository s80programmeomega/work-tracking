<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Document;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory as SpreadsheetFactory;
use PhpOffice\PhpWord\IOFactory as WordFactory;
use Smalot\PdfParser\Parser as PdfParser;
use Throwable;

/**
 * Extrait le contenu textuel d'un document uploadé selon son type MIME.
 * Retourne null en cas d'échec ou de type non pris en charge (gracieux).
 *
 * Limite : 50 000 caractères stockés en base.
 * Typesense n'indexe que les 5 000 premiers chars de content_text.
 */
class DocumentTextExtractorService
{
    // Limite de stockage en base (chars)
    private const MAX_DB_CHARS = 50_000;

    /**
     * Extrait le texte du fichier associé au document.
     * Retourne null si le type n'est pas pris en charge ou si l'extraction échoue.
     */
    public function extract(Document $document): ?string
    {
        $mime = strtolower($document->mime_type ?? '');
        $disk = $document->disk ?? 'public';
        $path = $document->chemin;

        if (! $path || ! Storage::disk($disk)->exists($path)) {
            return null;
        }

        try {
            $text = match (true) {
                $mime === 'application/pdf' => $this->extractPdf($disk, $path),
                in_array($mime, [
                    'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
                    'application/msword',
                    'application/vnd.ms-word',
                ]) => $this->extractDocx($disk, $path),
                in_array($mime, [
                    'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
                    'application/vnd.ms-excel',
                ]) => $this->extractXlsx($disk, $path),
                in_array($mime, [
                    'text/plain',
                    'text/markdown',
                    'text/csv',
                    'text/html',
                ]) => $this->extractPlainText($disk, $path),
                default => null,
            };

            if ($text === null) {
                return null;
            }

            // Normaliser les espaces et tronquer au seuil de stockage
            $text = preg_replace('/\s+/', ' ', trim($text));

            return mb_substr($text, 0, self::MAX_DB_CHARS);

        } catch (Throwable $e) {
            // Échec d'extraction — on continue sans bloquer l'upload
            Log::warning('Échec de l\'extraction de texte pour le document', [
                'document_id' => $document->id,
                'mime_type' => $mime,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ── Parseurs privés ───────────────────────────────────────────────────────

    private function extractPdf(string $disk, string $path): ?string
    {
        $localPath = $this->toLocalPath($disk, $path);
        if (! $localPath) {
            return null;
        }

        $parser = new PdfParser;
        $pdf = $parser->parseFile($localPath);

        return $pdf->getText();
    }

    private function extractDocx(string $disk, string $path): ?string
    {
        $localPath = $this->toLocalPath($disk, $path);
        if (! $localPath) {
            return null;
        }

        $phpWord = WordFactory::load($localPath);
        $sections = $phpWord->getSections();
        $texts = [];

        foreach ($sections as $section) {
            foreach ($section->getElements() as $element) {
                if (method_exists($element, 'getText')) {
                    $texts[] = $element->getText();
                } elseif (method_exists($element, 'getElements')) {
                    foreach ($element->getElements() as $child) {
                        if (method_exists($child, 'getText')) {
                            $texts[] = $child->getText();
                        }
                    }
                }
            }
        }

        return implode(' ', array_filter($texts));
    }

    private function extractXlsx(string $disk, string $path): ?string
    {
        $localPath = $this->toLocalPath($disk, $path);
        if (! $localPath) {
            return null;
        }

        $spreadsheet = SpreadsheetFactory::load($localPath);
        $texts = [];

        foreach ($spreadsheet->getAllSheets() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                foreach ($row->getCellIterator() as $cell) {
                    $value = $cell->getFormattedValue();
                    if ($value !== '' && $value !== null) {
                        $texts[] = (string) $value;
                    }
                }
            }
        }

        return implode(' ', $texts);
    }

    private function extractPlainText(string $disk, string $path): ?string
    {
        // Lecture directe depuis Storage — pas besoin de fichier temporaire
        return Storage::disk($disk)->get($path) ?: null;
    }

    /**
     * Renvoie le chemin absolu local du fichier (copie temporaire si nécessaire).
     * Pour les disques non-locaux (S3, etc.), crée un fichier tmp et retourne son chemin.
     */
    private function toLocalPath(string $disk, string $path): ?string
    {
        $driver = config("filesystems.disks.{$disk}.driver", 'local');

        if ($driver === 'local') {
            return Storage::disk($disk)->path($path);
        }

        // Disque distant : copie temporaire locale
        $contents = Storage::disk($disk)->get($path);
        if (! $contents) {
            return null;
        }

        $tmp = tempnam(sys_get_temp_dir(), 'doc_extract_');
        file_put_contents($tmp, $contents);

        // Le fichier tmp sera nettoyé par le système à la fin du processus
        return $tmp;
    }
}
