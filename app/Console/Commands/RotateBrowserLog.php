<?php

namespace App\Console\Commands;

use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

/**
 * Rotation journalière du fichier browser.log écrit par le package laravel-boost.
 *
 * Ce package (opcodesio/log-viewer + laravel-boost) écrit directement dans
 * storage/logs/browser.log sans passer par les canaux Laravel configurables.
 * Cette commande simule une rotation de type `logrotate` :
 *   - Renomme browser.log → browser-YYYY-MM-DD.log
 *   - Supprime les fichiers browser-*.log de plus de 14 jours
 *
 * Planifiée quotidiennement dans app/Console/Kernel.php.
 */
class RotateBrowserLog extends Command
{
    protected $signature = 'logs:rotate-browser {--days=14 : Nombre de jours à conserver}';

    protected $description = 'Rotation journalière du fichier browser.log (généré par laravel-boost)';

    public function handle(): int
    {
        $logPath = storage_path('logs/browser.log');
        $retention = (int) $this->option('days');

        if (! File::exists($logPath)) {
            $this->info('Aucun fichier browser.log à archiver.');

            return self::SUCCESS;
        }

        // Archive du jour courant
        $archive = storage_path('logs/browser-'.now()->format('Y-m-d').'.log');
        File::copy($logPath, $archive);
        File::put($logPath, '');

        $this->info('browser.log archivé → '.basename($archive));

        // Suppression des archives de plus de N jours
        $cutoff = now()->subDays($retention);
        $deleted = 0;

        foreach (File::glob(storage_path('logs/browser-*.log')) as $file) {
            if (preg_match('/browser-(\d{4}-\d{2}-\d{2})\.log$/', $file, $m)) {
                if (Carbon::parse($m[1])->lt($cutoff)) {
                    File::delete($file);
                    $deleted++;
                }
            }
        }

        if ($deleted > 0) {
            $this->info("{$deleted} fichier(s) browser.log supprimé(s) (> {$retention} jours).");
        }

        return self::SUCCESS;
    }
}
