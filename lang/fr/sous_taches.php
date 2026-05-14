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
        'date_exceeds_parent' => 'La date d\'échéance ne peut pas dépasser celle de la tâche parente.',
        'status_blocked' => 'Le statut de la tâche parente ne peut pas être modifié manuellement tant que des sous-tâches existent.',
        'unauthorized' => 'Vous n\'êtes pas autorisé à effectuer cette action.',
    ],
    'success' => [
        'created' => 'Sous-tâche créée avec succès.',
        'updated' => 'Sous-tâche mise à jour.',
        'deleted' => 'Sous-tâche supprimée.',
        'intervenant_assigned' => 'Intervenant assigné à la sous-tâche.',
    ],
    'notifications' => [
        'assigned' => [
            'subject' => 'Vous avez été assigné à une sous-tâche',
            'line1' => 'Vous avez été assigné à la sous-tâche : :titre',
            'line2' => 'Assigné par : :by',
            'due' => 'Date d\'échéance : :date',
        ],
    ],
];
