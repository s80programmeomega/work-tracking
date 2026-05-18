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
    'ui' => [
        'section_title' => 'Sous-tâches',
        'add_button' => 'Ajouter',
        'create_first' => 'Créer la première sous-tâche',
        'empty' => 'Aucune sous-tâche pour l\'instant.',
        'form' => [
            'title_placeholder' => 'Titre de la sous-tâche…',
            'description_placeholder' => 'Description (optionnel)…',
            'poids_label' => 'Poids (%)',
            'poids_remaining' => 'restant: :n%',
            'echeance_label' => 'Échéance',
            'echeance_max' => 'max: :date',
            'needs_validation' => 'Nécessite validation',
            'blocks_progress' => 'Bloque la progression',
            'submit' => 'Créer',
            'cancel' => 'Annuler',
        ],
        'weighted_progress' => 'Progression pondérée',
        'total_poids' => 'Poids total alloué: :n% / 100%',
        'mark_done' => 'Marquer comme terminé',
        'mark_in_progress' => 'Marquer comme en cours',
        'blocking_badge' => 'Bloquante',
        'submit_disabled_tooltip' => 'Des sous-tâches bloquantes ne sont pas encore terminées.',
        'kanban_badge' => ':done/:total ST',
    ],
];
