<?php

declare(strict_types=1);

namespace App\Services;

use App\Events\Realtime\SousTacheChanged;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Notifications\SousTacheAssigneeNotification;
use Illuminate\Support\Facades\Log;

class SousTacheService
{
    public function create(Tache $tache, array $data, User $actor): SousTache
    {
        $poids = $data['poids'] ?? 0;

        SousTache::enforceWeights($tache->id, $poids, $actor->id);

        $sousTache = SousTache::create(array_merge($data, [
            'tache_id' => $tache->id,
            'poids' => $poids,
        ]));

        event(new SousTacheChanged($sousTache->load(['tache.activite']), 'created'));

        Log::info('SousTache created', [
            'user_id' => $actor->id,
            'tache_id' => $tache->id,
            'sous_tache_id' => $sousTache->id,
            'action' => 'create',
        ]);

        return $sousTache->load('responsable');
    }

    public function update(SousTache $sousTache, array $data, User $actor): SousTache
    {
        if (isset($data['poids'])) {
            SousTache::enforceWeights(
                $sousTache->tache_id,
                $data['poids'],
                $actor->id,
                $sousTache->id
            );
        }

        if (isset($data['date_echeance'])) {
            $this->validateDateAgainstParent($sousTache->tache, $data['date_echeance']);
        }

        $sousTache->update($data);

        event(new SousTacheChanged($sousTache->load(['tache.activite']), 'updated'));

        Log::info('SousTache updated', [
            'user_id' => $actor->id,
            'sous_tache_id' => $sousTache->id,
            'action' => 'update',
        ]);

        return $sousTache->fresh('responsable');
    }

    public function delete(SousTache $sousTache, User $actor): void
    {
        $sousTacheId = $sousTache->id;
        $tacheId = $sousTache->tache_id;

        event(new SousTacheChanged($sousTache->loadMissing(['tache.activite']), 'deleted'));

        $sousTache->delete();

        Log::info('SousTache deleted', [
            'user_id' => $actor->id,
            'sous_tache_id' => $sousTacheId,
            'tache_id' => $tacheId,
            'action' => 'delete',
        ]);
    }

    public function assignIntervenant(SousTache $sousTache, User $intervenant, array $options, User $actor): void
    {
        $sousTache->intervenants()->syncWithoutDetaching([
            $intervenant->id => [
                'can_edit' => $options['can_edit'] ?? false,
                'can_complete' => $options['can_complete'] ?? true,
            ],
        ]);

        // G2: garde-fou — l'acteur peut s'auto-assigner (rare, mais ne sert
        // à rien de se notifier soi-même).
        app(NotificationService::class)->sendUnlessSelf(
            $intervenant,
            $actor,
            new SousTacheAssigneeNotification($sousTache, $actor)
        );

        Log::info('SousTache intervenant assigned', [
            'user_id' => $actor->id,
            'sous_tache_id' => $sousTache->id,
            'intervenant_id' => $intervenant->id,
            'action' => 'assign_intervenant',
        ]);
    }

    private function validateDateAgainstParent(Tache $tache, string $date): void
    {
        if ($tache->echeance && $date > $tache->echeance->format('Y-m-d')) {
            throw new \InvalidArgumentException(__('sous_taches.errors.date_exceeds_parent'));
        }
    }
}
