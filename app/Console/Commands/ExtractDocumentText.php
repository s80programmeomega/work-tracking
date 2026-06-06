<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Jobs\ExtractDocumentTextJob;
use App\Models\Document;
use Illuminate\Console\Command;

/**
 * Remplit rétroactivement le champ content_text pour les documents existants.
 * Lance ExtractDocumentTextJob en queue (ou synchrone avec --sync).
 *
 * Usage :
 *   php artisan documents:extract-text           — documents sans content_text uniquement
 *   php artisan documents:extract-text --all     — tous les documents (re-extraction)
 *   php artisan documents:extract-text --sync    — exécution synchrone (sans queue)
 */
class ExtractDocumentText extends Command
{
    protected $signature = 'documents:extract-text
                            {--all : Ré-extraire même les documents déjà traités}
                            {--sync : Exécuter de façon synchrone sans passer par la queue}';

    protected $description = 'Extrait le contenu textuel des documents uploadés pour l\'indexation Typesense';

    public function handle(): int
    {
        $query = Document::query()->whereNull('deleted_at');

        if (! $this->option('all')) {
            $query->whereNull('content_text');
        }

        $total = $query->count();

        if ($total === 0) {
            $this->info('Aucun document à traiter.');

            return self::SUCCESS;
        }

        $this->info("Traitement de {$total} document(s)…");
        $bar = $this->output->createProgressBar($total);
        $bar->start();

        $query->chunk(50, function ($documents) use ($bar) {
            foreach ($documents as $document) {
                if ($this->option('sync')) {
                    ExtractDocumentTextJob::dispatchSync($document);
                } else {
                    ExtractDocumentTextJob::dispatch($document);
                }
                $bar->advance();
            }
        });

        $bar->finish();
        $this->newLine();

        $mode = $this->option('sync') ? 'synchrone' : 'en file d\'attente';
        $this->info("✅ {$total} job(s) lancés ({$mode}).");

        return self::SUCCESS;
    }
}
