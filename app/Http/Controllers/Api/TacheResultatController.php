<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\TacheResultat;
use App\Models\User;
use App\Notifications\TemporaryAccessGrantedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TacheResultatController
{
    public function validateN1(Request $request, TacheResultat $resultat)
    {
        $tache = $resultat->tache;
        $activite = $tache->activite;

        // Vérifier si l'utilisateur peut valider N1
        if (!$activite->canUserValidateN1(auth()->user())) {
            abort(403, 'Vous ne pouvez pas valider ce résultat (N1)');
        }

        DB::transaction(function () use ($resultat, $request) {
            $resultat->update([
                'statut_validation_n1' => 'approuve',
                'validated_n1_by' => auth()->id(),
                'validated_n1_at' => now(),
                'commentaire_n1' => $request->input('commentaire'),
            ]);

            // Notifier le responsable N2 (Manager du projet)
            $projet = $resultat->tache->activite->projet;
            $manager = $projet->responsable;

            // $manager->notify(new ValidationN2RequiseNotification($resultat));
        });

        return response()->json([
            'message' => 'Résultat validé N1 avec succès',
            'resultat' => $resultat->load('tache.activite.projet')
        ]);
    }

    public function validateN2(Request $request, TacheResultat $resultat)
    {
        $tache = $resultat->tache;
        $projet = $tache->activite->projet;

        // Vérifier si l'utilisateur peut valider N2
        if (!$projet->canUserValidateN2(auth()->user())) {
            abort(403, 'Vous ne pouvez pas valider ce résultat (N2)');
        }

        // Vérifier que N1 est déjà validé
        if ($resultat->statut_validation_n1 !== 'approuve') {
            abort(400, 'Le résultat doit d\'abord être validé par N1');
        }

        DB::transaction(function () use ($resultat, $request) {
            $resultat->update([
                'statut_validation_n2' => 'approuve',
                'validated_n2_by' => auth()->id(),
                'validated_n2_at' => now(),
                'commentaire_n2' => $request->input('commentaire'),
            ]);

            // Marquer la tâche comme validée
            $resultat->tache->update([
                'validation_superieur' => true,
            ]);

            // Notifier l'utilisateur
            // $resultat->user->notify(new TacheValideeNotification($resultat));
        });

        return response()->json([
            'message' => 'Résultat validé N2 avec succès (validation complète)',
            'resultat' => $resultat->load('tache.activite.projet')
        ]);
    }
}
