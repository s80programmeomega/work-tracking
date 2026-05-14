<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\SousTache;
use App\Models\Tache;
use App\Notifications\TacheStatutAutoChangeNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SousTacheObserver
{
    public function updated(SousTache $sousTache): void
    {
        if ($sousTache->isDirty(['statut', 'progression', 'poids'])) {
            $this->recalculateParentProgress($sousTache->tache_id);
        }
    }

    public function created(SousTache $sousTache): void
    {
        $this->recalculateParentProgress($sousTache->tache_id);
    }

    public function deleted(SousTache $sousTache): void
    {
        $this->recalculateParentProgress($sousTache->tache_id);
    }

    private function recalculateParentProgress(int $tacheId): void
    {
        $tache = Tache::with('sousTaches')->find($tacheId);

        if (! $tache) {
            return;
        }

        $sousTaches = $tache->sousTaches()->whereNull('deleted_at')->get();

        if ($sousTaches->isEmpty()) {
            return;
        }

        $newProgression = $this->calculateWeightedProgression($sousTaches);
        $oldStatut = $tache->statut->value;
        $newStatut = $this->determineParentStatut($tache, $sousTaches);

        $updates = ['taux_realisation' => $newProgression];

        if ($newStatut !== $oldStatut) {
            $updates['statut'] = $newStatut;

            Log::info('Tache statut auto-changed by SousTache observer', [
                'tache_id' => $tache->id,
                'old_status' => $oldStatut,
                'new_status' => $newStatut,
                'triggered_by' => 'observer',
            ]);

            if ($tache->responsable) {
                $tache->responsable->notify(
                    new TacheStatutAutoChangeNotification($tache, $oldStatut, $newStatut)
                );
            }
        }

        // Use DB update to bypass model observers (including the Tache updating hook)
        DB::table('taches')
            ->where('id', $tache->id)
            ->update(array_merge($updates, ['updated_at' => now()]));
    }

    private function calculateWeightedProgression($sousTaches): int
    {
        $totalPoids = $sousTaches->sum('poids');

        if ($totalPoids === 0) {
            // Unweighted — simple average
            return (int) round($sousTaches->avg('progression'));
        }

        $weighted = $sousTaches->sum(fn ($st) => ($st->progression * $st->poids) / 100);

        return (int) round($weighted);
    }

    private function determineParentStatut(Tache $tache, $sousTaches): string
    {
        $current = $tache->statut->value;

        // If manually set to annule, don't override
        if ($current === 'annule') {
            return $current;
        }

        $allTermine = $sousTaches->every(fn ($st) => $st->statut === 'termine');
        $anyEnRetard = $sousTaches->contains(fn ($st) => $st->statut === 'en_retard');
        $anyEnCours = $sousTaches->contains(fn ($st) => in_array($st->statut, ['en_cours', 'en_retard']));

        if ($allTermine) {
            return 'termine';
        }

        if ($anyEnRetard) {
            return 'en_retard';
        }

        if ($anyEnCours) {
            return 'en_cours';
        }

        return 'a_faire';
    }
}
