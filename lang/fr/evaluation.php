<?php

return [
    'errors' => [
        'cannot_view_pending' => 'Vous n\'avez pas la permission de voir les validations en attente.',
        'cannot_view_score' => 'Vous n\'avez pas la permission de voir les scores d\'évaluation.',
        'cannot_view_others_score' => 'Vous n\'avez pas la permission de voir le score d\'un autre utilisateur.',
        'cannot_view_fiche' => 'Vous n\'avez pas la permission de consulter cette fiche d\'évaluation.',
        'no_workspace' => 'Aucun workspace courant n\'est sélectionné.',
        'immutable_post_n2' => 'Cette tâche est verrouillée: elle a été validée au niveau N2 et ne peut plus être modifiée.',
        'cannot_view_dashboard' => 'Vous n\'avez pas la permission de consulter le tableau de bord évaluations.',
        'cannot_view_workspace_taches' => 'Vous n\'avez pas la permission de consulter la vue globale des tâches.',
    ],

    'criteria' => [
        'n1_validated_despite_return' => 'Renvoi non justifié (pénalité)',
        'n1_confirmed_return' => 'Renvoi confirmé (bonus)',

        // Task 9 — Les 8 critères pondérés de la fiche d'évaluation.
        // Libellés courts (≤ 30 chars) optimisés pour l'affichage en grille.
        'completion_rate' => 'Taux de complétion',
        'deadline_respect' => 'Respect des échéances',
        'result_quality' => 'Qualité des résultats',
        'first_pass_validation' => 'Validation 1er passage',
        'justified_returns' => 'Renvois justifiés',
        'inactions' => 'Inactions (timeouts)',
        'work_volume' => 'Volume de travail',
        'team_coordination' => 'Coordination d\'équipe',
    ],

    // Task 9 — Page Fiche d'évaluation (FichesEvaluation / AgentSheet).
    'sheet' => [
        'title' => 'Fiche d\'évaluation',
        'global_score' => 'Score global',
        'period_label' => 'Période',
        'sections' => [
            'directed_tasks' => 'Tâches dirigées',
            'directed_subtasks' => 'Sous-tâches dirigées',
            'assignee_tasks' => 'Tâches assignées',
            'assignee_subtasks' => 'Sous-tâches assignées',
        ],
        'filters' => [
            'from' => 'Du',
            'to' => 'Au',
            'statut' => 'Statut',
            'apply' => 'Appliquer',
            'reset' => 'Réinitialiser',
            'all' => 'Tous',
        ],
        'indicators' => [
            'return_quality' => 'Qualité des renvois',
            'unjustified_threshold' => 'Au-dessus du seuil (40%)',
            'escalades_abusives' => 'Escalades abusives',
            'export' => 'Exporter',
            'justified' => 'Renvois justifiés',
            'unjustified' => 'Renvois injustifiés',
            'threshold_note' => 'Un taux d\'injustifiés supérieur à 40% déclenche une alerte automatique au manager.',
        ],
        'criteria_panel_title' => 'Détail des 8 critères',
        'weight_label' => 'Poids',
        'raw_label' => 'Valeur',
        'weighted_label' => 'Pondéré',
        'subtask_coefficient_note' => 'Les sous-tâches contribuent au score à coefficient 0.5.',
        'empty' => 'Aucun élément dans cette section sur la période.',
    ],

    'dashboard' => [
        'title' => 'Validations en attente',
        'subtitle' => 'Résultats en attente d\'action N1 ou N2, triés par échéance.',
        'refresh' => 'Rafraîchir',
        'columns' => [
            'task' => 'Tâche',
            'assignee' => 'Intervenant',
            'statut' => 'Statut',
            'deadline' => 'Échéance',
            'bypass' => 'Bypass',
        ],
        'stats' => [
            'pending_n1' => 'N1 en attente',
            'pending_n2' => 'N2 en attente',
            'urgent' => 'Urgents (< 24h)',
            'total' => 'Total',
        ],
        'alerts' => [
            'urgent' => 'Urgent (< 24h)',
            'bypass' => 'Bypass actif',
            'escalades_abusives' => 'Escalades abusives',
        ],
        'empty' => [
            'n1' => 'Aucun résultat en attente N1',
            'n2' => 'Aucun résultat en attente N2',
        ],
    ],

    'notifications' => [
        'score_updated' => [
            'subject' => 'Votre score a été mis à jour',
        ],
        'sheet_ready' => [
            'subject' => 'Votre fiche d\'évaluation est prête',
        ],
        'unjustified_return_alert' => [
            'subject' => 'Alerte: taux de renvois injustifiés élevé',
            'line1' => ':nom a dépassé le seuil de renvois injustifiés (:rate% des décisions N1 ont inversé un renvoi).',
            'line2' => 'Période évaluée: :start → :end.',
            'action' => 'Consulter sa fiche',
        ],
        'abusive_escalation_alert' => [
            'subject' => 'Alerte: escalades abusives détectées',
            'line1' => ':nom a déclenché le drapeau d\'escalades abusives sur la tâche « :tache ».',
            'line2' => 'Nombre de contournements consécutifs non justifiés : :count.',
            'action' => 'Voir le tableau de bord évaluations',
        ],
        'high_inaction_rate_alert' => [
            'subject' => 'Alerte: taux d\'inaction N0 élevé',
            'line1' => ':nom présente un taux d\'inaction N0 de :rate% sur la période.',
        ],
    ],

    'dashboard' => [
        'title' => 'Tableau de bord évaluations',
        'period_label' => 'Période',
        'top_performers' => 'Meilleurs performers',
        'alerts' => 'Alertes',
        'alert_escalades_abusives' => 'Escalades abusives',
        'alert_high_inaction' => 'Taux d\'inaction élevé',
        'no_scores' => 'Aucun score enregistré pour cette période.',
        'no_alerts' => 'Aucune alerte active.',
        'score_total' => 'Score total',
        'decisions_count' => 'Décisions',
        'inaction_rate' => 'Taux d\'inaction',
    ],

    'workspace_tasks' => [
        'title' => 'Toutes les tâches',
        'filters' => [
            'project' => 'Projet',
            'activity' => 'Activité',
            'status' => 'Statut',
            'assignee' => 'Assigné',
        ],
        'columns' => [
            'titre' => 'Titre',
            'statut' => 'Statut',
            'priorite' => 'Priorité',
            'echeance' => 'Échéance',
            'assignees' => 'Assignés',
            'projet' => 'Projet',
            'activite' => 'Activité',
            'sous_taches' => 'Sous-tâches',
        ],
        'no_tasks' => 'Aucune tâche trouvée pour ces critères.',
    ],
];
