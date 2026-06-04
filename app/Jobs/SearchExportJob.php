<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Exports\MultiSheetSearchExport;
use App\Exports\SearchExport;
use App\Http\Controllers\Api\SearchController;
use App\Models\User;
use App\Notifications\SearchExportReadyNotification;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Http\Request;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Maatwebsite\Excel\Facades\Excel;

/**
 * Exporte tous les résultats de recherche (cap = "all") en arrière-plan.
 * Une fois terminé, envoie un email à l'utilisateur avec le lien de téléchargement.
 *
 * Dispatché par SearchController::export() quand cap=all.
 */
class SearchExportJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public int $timeout = 300;

    /** @param  array<string, mixed>  $params */
    public function __construct(
        private readonly array $params,
        private readonly User $user,
    ) {}

    public function handle(SearchController $controller): void
    {
        Log::info('Export de recherche global démarré', [
            'user_id' => $this->user->id,
            'query' => $this->params['q'] ?? '',
        ]);

        $query = $this->params['q'] ?? '';
        $types = $this->params['types'] ?? ['projets', 'activites', 'taches', 'documents', 'users', 'messages'];
        // Plafond interne pour les exports "all" : 5 000 résultats par type max
        $capPerType = 5_000;
        $sheets = [];

        foreach ($types as $type) {
            // Réutiliser la logique de recherche via une requête HTTP interne simulée
            $fakeRequest = new Request(array_merge($this->params, [
                'per_page' => $capPerType,
                'page' => 1,
                'types' => [$type],
            ]));
            $fakeRequest->setUserResolver(fn () => $this->user);

            // Lancer la recherche directement via la méthode interne
            $response = $controller->search($fakeRequest);
            $data = json_decode($response->getContent(), true);
            $hits = $data['results'][$type] ?? [];

            if (! empty($hits)) {
                $sheets[] = new SearchExport(collect($hits), $type);
            }
        }

        if (empty($sheets)) {
            Log::info('Export de recherche global — aucun résultat à exporter', ['user_id' => $this->user->id]);

            return;
        }

        // Stocker le fichier dans le disque local et envoyer le lien par email
        $filename = 'exports/recherche-'.$this->user->id.'-'.now()->format('Y-m-d-His').'.xlsx';
        Excel::store(new MultiSheetSearchExport($sheets), $filename, 'local');

        $downloadUrl = route('search.export.download', ['file' => basename($filename)]);

        // Notifier l'utilisateur via email (notification inline)
        $this->user->notify(new SearchExportReadyNotification($downloadUrl));

        Log::info('Export de recherche global terminé', [
            'user_id' => $this->user->id,
            'fichier' => $filename,
        ]);
    }
}
