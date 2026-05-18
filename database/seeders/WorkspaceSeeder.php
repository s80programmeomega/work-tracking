<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\User;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

class WorkspaceSeeder extends Seeder
{
    public function run(): void
    {
        $superAdmin = User::where('email', 'superadmin@worktracking.com')->firstOrFail();
        $directeur = User::where('email', 'directeur@worktracking.com')->firstOrFail();

        // Pre-load all contextual role IDs from Spatie — replaces role ENUM strings
        $roleIds = SpatieRole::whereIn('name', ['owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'])
            ->where('guard_name', 'web')
            ->pluck('id', 'name')
            ->all();

        foreach ([
            ['nom' => 'Kouassi',  'prenom' => 'Éric',    'email' => 'manager@worktracking.com'],
            ['nom' => 'Traoré',   'prenom' => 'Aïcha',   'email' => 'cadre@worktracking.com'],
            ['nom' => 'Mensah',   'prenom' => 'Kofi',    'email' => 'collaborateur@worktracking.com'],
            ['nom' => 'Diallo',   'prenom' => 'Fatouma', 'email' => 'stagiaire@worktracking.com'],
            ['nom' => 'Sanogo',   'prenom' => 'Mamadou', 'email' => 'observateur@worktracking.com'],
        ] as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'nom' => $data['nom'],
                    'prenom' => $data['prenom'],
                    'nom_complet' => $data['prenom'].' '.$data['nom'],
                    'password' => Hash::make('password'),
                    'email_verified_at' => now(),
                    'is_active' => true,
                ]
            );
            $user->syncRoles([Role::UTILISATEUR->value]);
        }

        $manager = User::where('email', 'manager@worktracking.com')->firstOrFail();
        $cadre = User::where('email', 'cadre@worktracking.com')->firstOrFail();
        $collaborateur = User::where('email', 'collaborateur@worktracking.com')->firstOrFail();
        $stagiaire = User::where('email', 'stagiaire@worktracking.com')->firstOrFail();
        $observateur = User::where('email', 'observateur@worktracking.com')->firstOrFail();

        // ── Workspace principal ───────────────────────────────────────────────
        $workspace = Workspace::create([
            'nom' => 'Direction Générale — CERD Africa',
            'description' => 'Espace de travail principal regroupant les projets stratégiques de la direction.',
            'code' => 'CERD-WS-001',
            'owner_id' => $directeur->id,
            'is_active' => true,
            'settings' => [
                'default_project_visibility' => 'team',
                'members_can_create_projects' => true,
                'members_can_invite' => false,
                'require_task_validation' => true,
            ],
        ]);

        foreach ([$superAdmin, $directeur, $manager, $cadre, $collaborateur, $stagiaire, $observateur] as $user) {
            $user->update(['current_workspace_id' => $workspace->id]);
        }

        $workspace->members()->attach($directeur->id, [
            'role_id' => $roleIds['owner'],
            'invited_at' => now(),
            'invited_by' => $directeur->id,
        ]);

        foreach ([
            [$manager,       'manager'],
            [$cadre,         'cadre'],
            [$collaborateur, 'collaborateur'],
            [$stagiaire,     'stagiaire'],
            [$observateur,   'observateur'],
        ] as [$user, $role]) {
            $workspace->members()->attach($user->id, [
                'role_id' => $roleIds[$role],
                'invited_at' => now(),
                'invited_by' => $directeur->id,
            ]);
        }

        // ── Projets ───────────────────────────────────────────────────────────
        $projects = [
            [
                'nom' => 'Déploiement ERP RH & Paie',
                'description' => 'Déploiement et paramétrage du système ERP pour la gestion des ressources humaines et de la paie sur l\'ensemble des sites.',
                'objectif' => 'Disposer d\'un système centralisé et fiable pour la gestion du personnel avant la fin du trimestre.',
                'responsable' => $manager,
                'activites' => [
                    [
                        'nom' => 'Cadrage et analyse des besoins',
                        'description' => 'Recueil et formalisation des besoins auprès des responsables RH, financiers et DSI.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Rédiger le cahier des charges fonctionnel',
                                'description' => 'Formaliser l\'ensemble des exigences fonctionnelles validées avec les parties prenantes RH et finance.',
                                'objectif' => 'Obtenir un document de référence signé par toutes les parties prenantes avant le démarrage du développement.',
                                'statut' => 'termine',
                                'taux' => 100,
                                'priorite' => 'elevee',
                                'sous_taches' => [
                                    ['titre' => 'Interviews parties prenantes', 'description' => 'Conduire les entretiens avec les responsables RH, finance et DSI.', 'poids' => 40, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Rédaction du document',        'description' => 'Rédiger le cahier des charges en intégrant toutes les exigences collectées.', 'poids' => 35, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Validation et signature',       'description' => 'Organiser la réunion de validation et recueillir les signatures.', 'poids' => 25, 'statut' => 'en_cours', 'progression' => 60],
                                ],
                            ],
                            [
                                'titre' => 'Analyser les processus métier existants',
                                'description' => 'Cartographier les processus RH actuels pour identifier les écarts avec la solution cible.',
                                'objectif' => 'Produire une cartographie complète avec les axes d\'amélioration priorisés.',
                                'statut' => 'en_cours',
                                'taux' => 65,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Définir les indicateurs de performance',
                                'description' => 'Identifier et valider les KPIs permettant de mesurer l\'atteinte des objectifs du projet.',
                                'objectif' => 'Disposer d\'un tableau de bord de suivi validé par la direction.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Paramétrage et développement',
                        'description' => 'Configuration de l\'ERP et développement des modules spécifiques selon les besoins validés.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Configurer le module paie',
                                'description' => 'Paramétrer les règles de calcul de la paie selon la convention collective applicable.',
                                'objectif' => 'Garantir un calcul de paie conforme à la réglementation locale avec zéro écart.',
                                'statut' => 'en_cours',
                                'taux' => 45,
                                'priorite' => 'critique',
                                'sous_taches' => [
                                    ['titre' => 'Paramétrage des rubriques',   'description' => 'Configurer les rubriques de paie (salaire de base, primes, retenues).', 'poids' => 50, 'statut' => 'termine',  'progression' => 100],
                                    ['titre' => 'Règles de calcul cotisations', 'description' => 'Configurer les règles de calcul des cotisations sociales et fiscales.', 'poids' => 30, 'statut' => 'en_cours', 'progression' => 40],
                                    ['titre' => 'Tests de paie à blanc',        'description' => 'Exécuter des simulations de paie et comparer avec les bulletins actuels.', 'poids' => 20, 'statut' => 'a_faire', 'progression' => 0],
                                ],
                            ],
                            [
                                'titre' => 'Développer le module de reporting RH',
                                'description' => 'Créer les tableaux de bord et rapports RH requis par la direction.',
                                'objectif' => 'Permettre aux managers de générer leurs rapports de suivi de manière autonome.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Mettre en place la gestion des congés',
                                'description' => 'Paramétrer et tester le module de gestion des congés et absences.',
                                'objectif' => 'Automatiser le circuit de validation des demandes de congés.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Tests et mise en production',
                        'description' => 'Recette utilisateur, correction des anomalies et déploiement en production.',
                        'responsable' => $manager,
                        'taches' => [
                            [
                                'titre' => 'Réaliser les tests de recette utilisateur',
                                'description' => 'Exécuter les scénarios de test avec les utilisateurs clés et consigner les anomalies.',
                                'objectif' => 'Obtenir un taux d\'anomalies bloquantes nul avant le passage en production.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Former les administrateurs RH',
                                'description' => 'Organiser et animer les sessions de formation pour les équipes RH.',
                                'objectif' => 'Avoir 100% des utilisateurs RH opérationnels sur le nouveau système.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Basculer en production',
                                'description' => 'Effectuer la mise en production et assurer la supervision post-déploiement.',
                                'objectif' => 'Garantir une transition sans interruption de service.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'critique',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'nom' => 'Portail Client Self-Service',
                'description' => 'Développement d\'un portail web permettant aux clients de suivre leurs commandes, télécharger leurs factures et contacter le support.',
                'objectif' => 'Réduire de 40% les sollicitations du service client en permettant aux clients de gérer leurs demandes en autonomie.',
                'responsable' => $manager,
                'activites' => [
                    [
                        'nom' => 'Conception UX/UI',
                        'description' => 'Conception des maquettes et prototypes interactifs du portail client.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Réaliser les wireframes du portail',
                                'description' => 'Concevoir l\'architecture d\'information et les wireframes de toutes les pages du portail.',
                                'objectif' => 'Valider la structure de navigation avec le client avant le démarrage du développement.',
                                'statut' => 'termine',
                                'taux' => 100,
                                'priorite' => 'elevee',
                                'sous_taches' => [
                                    ['titre' => 'Cartographie des parcours',  'description' => 'Définir les parcours utilisateurs pour chaque profil client.', 'poids' => 35, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Wireframes basse fidélité',  'description' => 'Créer les wireframes en basse fidélité pour validation rapide.', 'poids' => 35, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Prototype interactif',       'description' => 'Assembler le prototype cliquable pour les tests utilisateurs.', 'poids' => 30, 'statut' => 'termine', 'progression' => 100],
                                ],
                            ],
                            [
                                'titre' => 'Concevoir la charte graphique',
                                'description' => 'Définir la charte graphique du portail en cohérence avec l\'identité de marque.',
                                'objectif' => 'Disposer d\'un guide de style validé servant de référence pour tous les développements frontend.',
                                'statut' => 'termine',
                                'taux' => 100,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Valider les maquettes avec le client',
                                'description' => 'Présenter les maquettes haute fidélité et recueillir les retours du client.',
                                'objectif' => 'Obtenir la validation formelle des maquettes avant le démarrage du développement.',
                                'statut' => 'en_cours',
                                'taux' => 70,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Développement frontend et backend',
                        'description' => 'Développement des interfaces et services du portail selon les maquettes validées.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Développer le module authentification',
                                'description' => 'Implémenter le système d\'authentification client avec gestion des sessions et 2FA.',
                                'objectif' => 'Garantir un accès sécurisé et tracé au portail pour tous les clients.',
                                'statut' => 'en_cours',
                                'taux' => 80,
                                'priorite' => 'critique',
                                'sous_taches' => [
                                    ['titre' => 'Formulaire login/register', 'description' => 'Développer les pages de connexion et d\'inscription.', 'poids' => 30, 'statut' => 'termine',  'progression' => 100],
                                    ['titre' => 'Authentification 2FA',      'description' => 'Intégrer la double authentification par SMS ou email.', 'poids' => 40, 'statut' => 'en_cours', 'progression' => 75],
                                    ['titre' => 'Gestion des sessions',      'description' => 'Implémenter la gestion sécurisée des sessions et tokens.', 'poids' => 30, 'statut' => 'a_faire', 'progression' => 0],
                                ],
                            ],
                            [
                                'titre' => 'Développer le suivi des commandes',
                                'description' => 'Créer le module permettant aux clients de suivre l\'état de leurs commandes en temps réel.',
                                'objectif' => 'Afficher l\'état de toute commande en moins de 2 secondes depuis n\'importe quel appareil.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Intégrer la gestion des factures',
                                'description' => 'Développer le module de consultation et téléchargement des factures.',
                                'objectif' => 'Permettre aux clients de télécharger toutes leurs factures sans intervention du service client.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Recette et lancement',
                        'description' => 'Tests de charge, correction des anomalies et lancement progressif du portail.',
                        'responsable' => $manager,
                        'taches' => [
                            [
                                'titre' => 'Exécuter les tests de charge',
                                'description' => 'Simuler la charge nominale et les pics d\'utilisation pour valider les performances.',
                                'objectif' => 'Valider la capacité à supporter 500 utilisateurs simultanés sans dégradation.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Corriger les anomalies de recette',
                                'description' => 'Traiter et corriger toutes les anomalies remontées lors de la recette utilisateur.',
                                'objectif' => 'Atteindre un taux de résolution de 100% des anomalies bloquantes.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'critique',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Déployer en production et monitorer',
                                'description' => 'Lancer le portail en production et mettre en place la supervision applicative.',
                                'objectif' => 'Garantir une disponibilité de 99.9% dès le lancement.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'critique',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                ],
            ],
            [
                'nom' => 'Migration Infrastructure Cloud',
                'description' => 'Migration progressive des serveurs on-premise vers une architecture cloud hybride AWS/Azure pour améliorer la résilience et réduire les coûts.',
                'objectif' => 'Réduire les coûts d\'infrastructure de 30% et atteindre une disponibilité de 99.9% sur l\'ensemble des services.',
                'responsable' => $cadre,
                'activites' => [
                    [
                        'nom' => 'Audit et planification',
                        'description' => 'Inventaire de l\'infrastructure existante et planification détaillée de la migration.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Auditer l\'infrastructure existante',
                                'description' => 'Réaliser un inventaire exhaustif des serveurs, services et dépendances en production.',
                                'objectif' => 'Disposer d\'une cartographie complète de l\'infrastructure avant toute migration.',
                                'statut' => 'termine',
                                'taux' => 100,
                                'priorite' => 'elevee',
                                'sous_taches' => [
                                    ['titre' => 'Inventaire serveurs physiques', 'description' => 'Lister et documenter tous les serveurs physiques et leurs configurations.', 'poids' => 40, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Cartographie des dépendances',  'description' => 'Identifier toutes les dépendances inter-services critiques.', 'poids' => 35, 'statut' => 'termine', 'progression' => 100],
                                    ['titre' => 'Analyse des risques',           'description' => 'Évaluer les risques de chaque composant à migrer.', 'poids' => 25, 'statut' => 'termine', 'progression' => 100],
                                ],
                            ],
                            [
                                'titre' => 'Définir l\'architecture cible',
                                'description' => 'Concevoir l\'architecture cloud hybride cible et valider les choix technologiques.',
                                'objectif' => 'Disposer d\'une architecture validée par le RSSI et la direction technique.',
                                'statut' => 'en_cours',
                                'taux' => 50,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Élaborer le plan de migration',
                                'description' => 'Planifier les vagues de migration par ordre de priorité et de complexité.',
                                'objectif' => 'Disposer d\'un plan de migration détaillé avec dates et responsables.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Migration des services',
                        'description' => 'Exécution des vagues de migration selon le plan établi.',
                        'responsable' => $cadre,
                        'taches' => [
                            [
                                'titre' => 'Migrer les bases de données',
                                'description' => 'Migrer les bases de données vers les services managés cloud avec réplication et failover.',
                                'objectif' => 'Garantir zéro perte de données et moins de 30 minutes d\'interruption.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'critique',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Migrer les applications web',
                                'description' => 'Containeriser et déployer les applications web sur l\'infrastructure cloud.',
                                'objectif' => 'Disposer de toutes les applications web sur le cloud avec autoscaling configuré.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'elevee',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Configurer la supervision cloud',
                                'description' => 'Mettre en place les outils de monitoring et les alertes sur l\'infrastructure cloud.',
                                'objectif' => 'Détecter tout incident en moins de 2 minutes avec alerte automatique.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                    [
                        'nom' => 'Validation et optimisation',
                        'description' => 'Tests de la nouvelle infrastructure et optimisation des coûts et performances.',
                        'responsable' => $manager,
                        'taches' => [
                            [
                                'titre' => 'Valider la reprise après sinistre',
                                'description' => 'Tester les procédures de reprise après sinistre (PRA) sur la nouvelle infrastructure.',
                                'objectif' => 'Valider un RTO inférieur à 4 heures et un RPO inférieur à 1 heure.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'critique',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Optimiser les coûts cloud',
                                'description' => 'Analyser la consommation et optimiser les ressources pour atteindre l\'objectif de réduction des coûts.',
                                'objectif' => 'Atteindre une réduction de 30% des coûts d\'infrastructure par rapport à l\'on-premise.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'moyenne',
                                'sous_taches' => [],
                            ],
                            [
                                'titre' => 'Rédiger la documentation opérationnelle',
                                'description' => 'Produire toute la documentation d\'exploitation de la nouvelle infrastructure cloud.',
                                'objectif' => 'Permettre à l\'équipe ops de gérer l\'infrastructure sans assistance externe.',
                                'statut' => 'a_faire',
                                'taux' => 0,
                                'priorite' => 'faible',
                                'sous_taches' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ];

        foreach ($projects as $pd) {
            $projet = Projet::create([
                'workspace_id' => $workspace->id,
                'nom' => $pd['nom'],
                'description' => $pd['description'],
                'responsable_id' => $pd['responsable']->id,
                'date_debut' => now()->subDays(10),
                'date_fin' => now()->addMonths(4),
                'visibility' => 'team',
                'status' => 'active',
            ]);

            $projet->members()->attach($manager->id, ['role_id' => $roleIds['manager']]);
            $projet->members()->attach($cadre->id, ['role_id' => $roleIds['cadre']]);
            $projet->members()->attach($collaborateur->id, ['role_id' => $roleIds['collaborateur']]);
            $projet->members()->attach($stagiaire->id, ['role_id' => $roleIds['stagiaire']]);
            $projet->members()->attach($observateur->id, ['role_id' => $roleIds['observateur']]);

            foreach ($pd['activites'] as $ad) {
                $activite = Activite::create([
                    'projet_id' => $projet->id,
                    'nom' => $ad['nom'],
                    'description' => $ad['description'],
                    'responsable_id' => $ad['responsable']->id,
                    'date_debut' => now()->subDays(5),
                    'date_fin' => now()->addMonths(2),
                ]);

                $activite->members()->attach($cadre->id, [
                    'role_id' => $roleIds['cadre'],
                    'can_create_tasks' => true,
                    'can_edit_tasks' => true,
                    'can_delete_tasks' => true,
                    'can_validate_results' => true,
                    'can_assign_users' => true,
                ]);
                $activite->members()->attach($collaborateur->id, [
                    'role_id' => $roleIds['collaborateur'],
                    'can_create_tasks' => false,
                    'can_edit_tasks' => false,
                ]);
                $activite->members()->attach($stagiaire->id, [
                    'role_id' => $roleIds['stagiaire'],
                    'can_create_tasks' => false,
                    'can_edit_tasks' => false,
                ]);

                foreach ($ad['taches'] as $td) {
                    $tache = Tache::create([
                        'activite_id' => $activite->id,
                        'responsable_id' => $cadre->id,
                        'titre' => $td['titre'],
                        'description' => $td['description'],
                        'objectif' => $td['objectif'],
                        'statut' => $td['statut'],
                        'priorite' => $td['priorite'],
                        'echeance' => now()->addDays(rand(7, 30)),
                        'taux_realisation' => $td['taux'],
                        'validation_n1_required' => true,
                        'validation_n2_required' => true,
                    ]);

                    $tache->assignees()->attach($collaborateur->id, [
                        'role_id' => $roleIds['collaborateur'],
                        'is_responsable' => true,
                        'can_edit' => false,
                    ]);
                    $tache->assignees()->attach($stagiaire->id, [
                        'role_id' => $roleIds['stagiaire'],
                        'is_responsable' => false,
                        'can_edit' => false,
                    ]);

                    foreach ($td['sous_taches'] as $i => $std) {
                        SousTache::create([
                            'tache_id' => $tache->id,
                            'responsable_id' => $i === 2 ? $stagiaire->id : $collaborateur->id,
                            'titre' => $std['titre'],
                            'description' => $std['description'],
                            'statut' => $std['statut'],
                            'progression' => $std['progression'],
                            'poids' => $std['poids'],
                            'ordre' => $i + 1,
                            'date_echeance' => now()->addDays(($i + 1) * 5),
                        ]);
                    }
                }
            }
        }

        // ── Workspace secondaire ──────────────────────────────────────────────
        $workspace2 = Workspace::create([
            'nom' => 'Département Technique — CERD Africa',
            'description' => 'Espace de travail de l\'équipe technique pour les projets d\'infrastructure et d\'innovation.',
            'code' => 'CERD-WS-002',
            'owner_id' => $manager->id,
            'is_active' => true,
            'settings' => ['default_project_visibility' => 'team'],
        ]);

        $workspace2->members()->attach($manager->id, [
            'role_id' => $roleIds['owner'], 'invited_at' => now(), 'invited_by' => $manager->id,
        ]);
        $workspace2->members()->attach($directeur->id, [
            'role_id' => $roleIds['manager'], 'invited_at' => now(), 'invited_by' => $manager->id,
        ]);

        Projet::create([
            'workspace_id' => $workspace2->id,
            'nom' => 'Refonte du système de monitoring',
            'description' => 'Remplacement de l\'ancien système de monitoring par une solution centralisée basée sur Grafana et Prometheus.',
            'responsable_id' => $manager->id,
            'date_debut' => now(),
            'date_fin' => now()->addMonths(3),
            'visibility' => 'team',
            'status' => 'active',
        ]);

        $this->command->info('Workspace seeded successfully!');
        $this->command->info('3 projets × 3 activités × 3 tâches = 27 tâches avec objectifs réels');
        $this->command->info('Sous-tâches détaillées sur les tâches prioritaires');
        $this->command->info('');
        $this->command->info('Utilisateurs de test (mot de passe : password) :');
        $this->command->info('- superadmin@worktracking.com    → super_admin');
        $this->command->info('- directeur@worktracking.com     → directeur (propriétaire workspace)');
        $this->command->info('- manager@worktracking.com       → Éric Kouassi (manager, validateur N2)');
        $this->command->info('- cadre@worktracking.com         → Aïcha Traoré (cadre, validateur N1)');
        $this->command->info('- collaborateur@worktracking.com → Kofi Mensah (collaborateur)');
        $this->command->info('- stagiaire@worktracking.com     → Fatouma Diallo (stagiaire)');
        $this->command->info('- observateur@worktracking.com   → Mamadou Sanogo (observateur)');
    }
}
