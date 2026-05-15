<?php

namespace App\Jobs;

use App\Models\TacheResultat;
use App\Services\TacheResultatService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class TransmettreResultatAuN1Job implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public function __construct(public readonly int $tacheResultatId) {}

    public function handle(TacheResultatService $service): void
    {
        $resultat = TacheResultat::find($this->tacheResultatId);

        if (! $resultat) {
            Log::warning('TransmettreResultatAuN1Job: résultat introuvable', [
                'tache_resultat_id' => $this->tacheResultatId,
            ]);

            return;
        }

        $service->transmettreAuN1($resultat);
    }
}
