<?php

return [
    'status' => [
        'a_faire' => 'À faire',
        'en_cours' => 'En cours',
        'en_retard' => 'En retard',
        'termine' => 'Terminé',
        'a_refaire' => 'À refaire',
        'annule' => 'Annulé',
    ],
    'errors' => [
        'max_depth_exceeded' => 'Une sous-tâche ne peut pas contenir d\'autres sous-tâches.',
        'weights_sum_invalid' => 'La somme des poids des sous-tâches ne peut pas dépasser 100%.',
    ],
    'success' => [
        'created' => 'Sous-tâche créée avec succès.',
        'updated' => 'Sous-tâche mise à jour.',
        'deleted' => 'Sous-tâche supprimée.',
    ],
];
