<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Activite;
use App\Models\Comment;
use App\Models\Projet;
use App\Models\SousTache;
use App\Models\Tache;
use App\Models\TacheResultat;
use App\Models\User;
use App\Models\ValidationAuditLog;
use App\Models\Workspace;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role as SpatieRole;

class WorkspaceSeeder extends Seeder
{
    /** @var array<string,int> */
    private array $roleIds = [];

    public function run(): void
    {
        $this->roleIds = SpatieRole::whereIn('name', [
            'owner', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur', 'task_responsable',
        ])
            ->where('guard_name', 'web')
            ->pluck('id', 'name')
            ->all();

        // ── Users ──────────────────────────────────────────────────────────────
        $superAdmin = User::where('email', 'superadmin@worktracking.com')->firstOrFail();
        $directeur = User::where('email', 'directeur@worktracking.com')->firstOrFail();

        // Core test accounts
        $eric = $this->ensureUser('Kouassi', 'Éric', 'manager@worktracking.com', Role::UTILISATEUR);
        $aicha = $this->ensureUser('Traoré', 'Aïcha', 'cadre@worktracking.com', Role::UTILISATEUR);
        $kofi = $this->ensureUser('Mensah', 'Kofi', 'collaborateur@worktracking.com', Role::UTILISATEUR);
        $fatouma = $this->ensureUser('Diallo', 'Fatouma', 'stagiaire@worktracking.com', Role::UTILISATEUR);
        $mamadou = $this->ensureUser('Sanogo', 'Mamadou', 'observateur@worktracking.com', Role::UTILISATEUR);

        // Extended team
        $aminata = $this->ensureUser('Koné', 'Aminata', 'aminata@worktracking.com', Role::UTILISATEUR);
        $ibrahim = $this->ensureUser('Bamba', 'Ibrahim', 'ibrahim@worktracking.com', Role::UTILISATEUR);
        $celestin = $this->ensureUser('Akpo', 'Céléstin', 'celestin@worktracking.com', Role::UTILISATEUR);
        $seydou = $this->ensureUser('Coulibaly', 'Seydou', 'seydou@worktracking.com', Role::UTILISATEUR);
        $mariam = $this->ensureUser('Ouédraogo', 'Mariam', 'mariam@worktracking.com', Role::UTILISATEUR);
        $patrick = $this->ensureUser('Gbagbo', 'Patrick', 'patrick@worktracking.com', Role::UTILISATEUR);
        $alice = $this->ensureUser('Assi', 'Alice', 'alice@worktracking.com', Role::UTILISATEUR);
        $omar = $this->ensureUser('Diakité', 'Omar', 'omar@worktracking.com', Role::UTILISATEUR);

        // ════════════════════════════════════════════════════════════════════════
        // WS 1 — Direction Générale (owned by directeur)
        // 5 projects · 15 activities · ~45 tasks
        // ════════════════════════════════════════════════════════════════════════
        $ws1 = $this->makeWorkspace(
            'Direction Générale — CERD Africa',
            'Espace de travail principal de la direction. Regroupe les projets stratégiques.',
            'CERD-WS-001',
            $directeur,
            ['default_project_visibility' => 'team', 'require_task_validation' => true]
        );

        foreach ([$directeur, $eric, $aicha, $kofi, $fatouma, $mamadou, $aminata, $ibrahim, $seydou, $mariam, $superAdmin] as $u) {
            $u->update(['current_workspace_id' => $ws1->id]);
        }

        $this->attachMember($ws1, $directeur, 'owner', $directeur);
        $this->attachMember($ws1, $eric, 'manager', $directeur);
        $this->attachMember($ws1, $aicha, 'cadre', $directeur);
        $this->attachMember($ws1, $kofi, 'collaborateur', $directeur);
        $this->attachMember($ws1, $fatouma, 'stagiaire', $directeur);
        $this->attachMember($ws1, $mamadou, 'observateur', $directeur);
        $this->attachMember($ws1, $aminata, 'collaborateur', $directeur);
        $this->attachMember($ws1, $ibrahim, 'cadre', $directeur);
        $this->attachMember($ws1, $seydou, 'collaborateur', $directeur);
        $this->attachMember($ws1, $mariam, 'stagiaire', $directeur);

        // ── WS1 · Project 1 — ERP RH (active, in progress) ──────────────────
        $p1 = $this->makeProjet($ws1, 'Déploiement ERP RH & Paie',
            'Déploiement et paramétrage du système ERP pour la gestion RH et paie.',
            $eric, now()->subMonths(3), now()->addMonths(4));
        $this->attachProjetMembers($p1, $eric, $aicha, $kofi, $fatouma, $mamadou, $ibrahim, $seydou);

        // Activity 1.1
        $a = $this->makeActivite($p1, 'Cadrage et analyse des besoins',
            'Recueil et formalisation des besoins RH, finance et DSI.',
            $aicha, now()->subMonths(3), now()->subMonths(2));
        $this->attachActiviteMembers($a, $aicha, $kofi, $fatouma, $seydou);

        $t = $this->makeTache($a, $aicha, 'Rédiger le cahier des charges fonctionnel',
            'Formaliser toutes les exigences validées avec les parties prenantes.',
            'CDC signé par toutes les parties avant démarrage.',
            'termine', 'elevee', 100, now()->subMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'Interviews parties prenantes', 'termine', 100, 40, 1, now()->subWeeks(10));
        $this->makeSousTache($t, $kofi, 'Rédaction du document', 'termine', 100, 35, 2, now()->subWeeks(9));
        $this->makeSousTache($t, $fatouma, 'Validation et signature', 'termine', 100, 25, 3, now()->subWeeks(8));
        $this->addComment($t, $aicha, 'Cahier des charges finalisé et signé. Excellent travail d\'équipe.');
        $this->addComment($t, $kofi, 'Merci. Les 34 exigences ont été documentées et validées.');

        $t = $this->makeTache($a, $aicha, 'Analyser les processus métier existants',
            'Cartographier les processus RH actuels pour identifier les écarts.',
            'Cartographie complète avec axes d\'amélioration priorisés.',
            'termine', 'elevee', 100, now()->subMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $ibrahim, 'cadre', false);
        $this->makeSousTache($t, $kofi, 'Cartographie AS-IS', 'termine', 100, 50, 1, now()->subWeeks(9));
        $this->makeSousTache($t, $ibrahim, 'Analyse des écarts', 'termine', 100, 50, 2, now()->subWeeks(8));

        $t = $this->makeTache($a, $aicha, 'Définir les indicateurs de performance',
            'Identifier et valider les KPIs pour mesurer l\'atteinte des objectifs.',
            'Tableau de bord de suivi validé par la direction.',
            'termine', 'moyenne', 100, now()->subWeeks(7));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->addComment($t, $eric, 'KPIs validés en CODIR. Bonne initiative de les lier aux objectifs Q3.');

        // Activity 1.2
        $a = $this->makeActivite($p1, 'Paramétrage et développement',
            'Configuration ERP et développement des modules spécifiques.',
            $aicha, now()->subMonths(2), now()->addMonths(2));
        $this->attachActiviteMembers($a, $aicha, $kofi, $fatouma, $ibrahim, $seydou);

        $t = $this->makeTache($a, $aicha, 'Configurer le module paie',
            'Paramétrer les règles de calcul selon la convention collective.',
            'Calcul de paie conforme à la réglementation avec zéro écart.',
            'en_cours', 'critique', 45, now()->addWeeks(3));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'Paramétrage des rubriques', 'termine', 100, 50, 1, now()->subWeeks(3));
        $this->makeSousTache($t, $kofi, 'Règles de calcul cotisations', 'en_cours', 40, 30, 2, now()->addWeeks(2));
        $this->makeSousTache($t, $fatouma, 'Tests de paie à blanc', 'a_faire', 0, 20, 3, now()->addWeeks(4));
        $this->addComment($t, $kofi, 'Les rubriques de base sont paramétrées. La CNSS et l\'IRPP restent à configurer.');
        $this->addComment($t, $aicha, 'Prévoir un test à blanc avec les données de mars avant validation.');

        $t = $this->makeTache($a, $aicha, 'Développer le module de reporting RH',
            'Créer les tableaux de bord et rapports RH requis par la direction.',
            'Managers capables de générer leurs rapports en autonomie.',
            'en_cours', 'moyenne', 30, now()->addMonths(2));
        $this->attachTacheUser($t, $ibrahim, 'cadre', true);
        $this->attachTacheUser($t, $seydou, 'collaborateur', false);

        $t = $this->makeTache($a, $aicha, 'Mettre en place la gestion des congés',
            'Paramétrer et tester le module congés et absences.',
            'Circuit de validation des demandes de congés automatisé.',
            'a_faire', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);

        $t = $this->makeTache($a, $aicha, 'Configurer l\'interface de gestion des contrats',
            'Créer les workflows de création et renouvellement de contrats.',
            'Processus contractuel 100% dématérialisé.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $aicha, 'Développer le portail self-service employé',
            'Module permettant aux employés de consulter leurs bulletins et demandes.',
            'Réduction de 60% des sollicitations RH récurrentes.',
            'en_retard', 'elevee', 20, now()->subDays(5));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->addComment($t, $eric, 'La tâche est en retard. Kofi, qu\'est-ce qui bloque ?');
        $this->addComment($t, $kofi, 'Attente des specs UI définitives de la part de la DRH.');
        $this->addComment($t, $aicha, 'Je relance la DRH aujourd\'hui.');

        // Activity 1.3
        $a = $this->makeActivite($p1, 'Tests et mise en production',
            'Recette utilisateur, correction et déploiement.',
            $eric, now()->addMonths(2), now()->addMonths(4));
        $this->attachActiviteMembers($a, $aicha, $kofi, $fatouma, $seydou);

        $t = $this->makeTache($a, $eric, 'Réaliser les tests de recette utilisateur',
            'Exécuter les scénarios de test avec les utilisateurs clés.',
            'Zéro anomalie bloquante avant passage en production.',
            'a_faire', 'elevee', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $eric, 'Former les administrateurs RH',
            'Organiser et animer les sessions de formation pour les équipes RH.',
            '100% des utilisateurs RH opérationnels sur le nouveau système.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $eric, 'Basculer en production',
            'Mise en production et supervision post-déploiement.',
            'Transition sans interruption de service.',
            'a_faire', 'critique', 0, now()->addMonths(4));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        // ── WS1 · Project 2 — Portail Client (active, advanced) ─────────────
        $p2 = $this->makeProjet($ws1, 'Portail Client Self-Service',
            'Portail web permettant aux clients de suivre commandes, factures et support.',
            $eric, now()->subMonths(4), now()->addMonths(2));
        $this->attachProjetMembers($p2, $eric, $aicha, $kofi, $fatouma, $mamadou, $aminata, $mariam);

        // Activity 2.1
        $a = $this->makeActivite($p2, 'Conception UX/UI',
            'Maquettes et prototypes interactifs du portail client.',
            $aicha, now()->subMonths(4), now()->subMonths(3));
        $this->attachActiviteMembers($a, $aicha, $aminata, $fatouma, $mariam);

        $t = $this->makeTache($a, $aicha, 'Réaliser les wireframes du portail',
            'Architecture d\'information et wireframes de toutes les pages.',
            'Structure de navigation validée avant démarrage du développement.',
            'termine', 'elevee', 100, now()->subMonths(3));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);
        $this->attachTacheUser($t, $mariam, 'stagiaire', false);
        $this->makeSousTache($t, $aminata, 'Cartographie des parcours', 'termine', 100, 35, 1, now()->subMonths(4));
        $this->makeSousTache($t, $aminata, 'Wireframes basse fidélité', 'termine', 100, 35, 2, now()->subMonths(4));
        $this->makeSousTache($t, $mariam, 'Prototype interactif', 'termine', 100, 30, 3, now()->subMonths(3));
        $this->addComment($t, $aicha, 'Wireframes validés par le client en réunion de revue.');
        $this->addComment($t, $aminata, 'Prototype Figma disponible sur le drive partagé.');

        $t = $this->makeTache($a, $aicha, 'Concevoir la charte graphique',
            'Charte graphique cohérente avec l\'identité de marque.',
            'Guide de style validé servant de référence pour tous les développements.',
            'termine', 'moyenne', 100, now()->subMonths(3));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);

        $t = $this->makeTache($a, $aicha, 'Valider les maquettes avec le client',
            'Maquettes haute fidélité et retours client formalisés.',
            'Validation formelle des maquettes avant démarrage du développement.',
            'termine', 'elevee', 100, now()->subMonths(2));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);
        $this->attachTacheUser($t, $mariam, 'stagiaire', false);

        // Activity 2.2
        $a = $this->makeActivite($p2, 'Développement frontend et backend',
            'Interfaces et services selon les maquettes validées.',
            $aicha, now()->subMonths(3), now()->addMonth());
        $this->attachActiviteMembers($a, $aicha, $kofi, $aminata, $fatouma, $mariam);

        $t = $this->makeTache($a, $aicha, 'Développer le module authentification',
            'Authentification client avec gestion des sessions et 2FA.',
            'Accès sécurisé et tracé au portail.',
            'en_cours', 'critique', 80, now()->addWeeks(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $aminata, 'collaborateur', false);
        $this->makeSousTache($t, $kofi, 'Formulaire login/register', 'termine', 100, 30, 1, now()->subWeeks(6));
        $this->makeSousTache($t, $kofi, 'Authentification 2FA', 'en_cours', 75, 40, 2, now()->addWeek());
        $this->makeSousTache($t, $aminata, 'Gestion des sessions', 'a_faire', 0, 30, 3, now()->addWeeks(3));

        $t = $this->makeTache($a, $aicha, 'Développer le suivi des commandes',
            'Module de suivi en temps réel des commandes client.',
            'État de toute commande affiché en moins de 2 secondes.',
            'en_cours', 'elevee', 50, now()->addMonth());
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'API de suivi commandes', 'en_cours', 60, 50, 1, now()->addWeeks(2));
        $this->makeSousTache($t, $fatouma, 'Interface de suivi frontend', 'a_faire', 0, 50, 2, now()->addWeeks(4));

        $t = $this->makeTache($a, $aicha, 'Intégrer la gestion des factures',
            'Module de consultation et téléchargement des factures.',
            'Clients autonomes pour toutes leurs factures.',
            'a_faire', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);
        $this->attachTacheUser($t, $mariam, 'stagiaire', false);

        $t = $this->makeTache($a, $aicha, 'Développer le module de messagerie support',
            'Système de ticketing et messagerie client ↔ support.',
            'Temps de première réponse inférieur à 4h garanti par le SLA.',
            'a_faire', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        $t = $this->makeTache($a, $aicha, 'Intégrer le tableau de bord client',
            'Vue synthétique des commandes, factures et tickets en cours.',
            'Dashboard chargé en moins d\'une seconde.',
            'en_attente', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);
        $this->addComment($t, $aminata, 'En attente de la finalisation du module authentification pour démarrer.');

        // Activity 2.3
        $a = $this->makeActivite($p2, 'Recette et lancement',
            'Tests de charge, corrections et lancement progressif.',
            $eric, now()->addMonth(), now()->addMonths(2));
        $this->attachActiviteMembers($a, $aicha, $kofi, $aminata, $mariam);

        $t = $this->makeTache($a, $eric, 'Exécuter les tests de charge',
            'Simuler la charge nominale et les pics d\'utilisation.',
            'Capacité à supporter 500 utilisateurs simultanés sans dégradation.',
            'a_faire', 'elevee', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        $t = $this->makeTache($a, $eric, 'Corriger les anomalies de recette',
            'Traiter et corriger toutes les anomalies remontées lors de la recette.',
            '100% des anomalies bloquantes résolues.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $aminata, 'collaborateur', false);

        $t = $this->makeTache($a, $eric, 'Déployer en production et monitorer',
            'Lancement en production et supervision applicative.',
            'Disponibilité de 99.9% dès le lancement.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        // ── WS1 · Project 3 — Gouvernance Documentaire (active) ───────────────
        $p3 = $this->makeProjet($ws1, 'Gouvernance Documentaire',
            'Mise en place d\'une politique de gestion documentaire centralisée et sécurisée.',
            $aicha, now()->subMonth(), now()->addMonths(5));
        $this->attachProjetMembers($p3, $aicha, $ibrahim, $seydou, $fatouma, $mamadou);

        // Activity 3.1
        $a = $this->makeActivite($p3, 'Audit et politique documentaire',
            'Audit de l\'existant et rédaction de la politique de gestion documentaire.',
            $ibrahim, now()->subMonth(), now()->subWeeks(2));
        $this->attachActiviteMembers($a, $ibrahim, $seydou, $fatouma);

        $t = $this->makeTache($a, $ibrahim, 'Auditer les pratiques documentaires actuelles',
            'Recenser tous les types de documents et les pratiques de classement existantes.',
            'Rapport d\'audit validé avec recommandations priorisées.',
            'termine', 'elevee', 100, now()->subWeeks(2));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $seydou, 'Recensement des documents', 'termine', 100, 40, 1, now()->subWeeks(4));
        $this->makeSousTache($t, $fatouma, 'Analyse des pratiques', 'termine', 100, 30, 2, now()->subWeeks(3));
        $this->makeSousTache($t, $seydou, 'Rédaction du rapport d\'audit', 'termine', 100, 30, 3, now()->subWeeks(2));

        $t = $this->makeTache($a, $ibrahim, 'Rédiger la politique de gestion documentaire',
            'Formaliser les règles de nommage, classement, accès et archivage.',
            'Politique validée par la direction et publiée en intranet.',
            'en_cours', 'elevee', 70, now()->addWeeks(2));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->makeSousTache($t, $seydou, 'Règles de nommage', 'termine', 100, 30, 1, now()->subWeek());
        $this->makeSousTache($t, $seydou, 'Matrice des habilitations', 'en_cours', 60, 40, 2, now()->addWeek());
        $this->makeSousTache($t, $seydou, 'Circuit de validation', 'a_faire', 0, 30, 3, now()->addWeeks(2));

        // Activity 3.2
        $a = $this->makeActivite($p3, 'Déploiement de la GED',
            'Installation et configuration du logiciel de gestion électronique de documents.',
            $aicha, now(), now()->addMonths(3));
        $this->attachActiviteMembers($a, $ibrahim, $seydou, $fatouma);

        $t = $this->makeTache($a, $aicha, 'Installer et configurer la GED',
            'Déploiement du logiciel GED et paramétrage des espaces documentaires.',
            'GED opérationnelle avec les espaces créés pour chaque département.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $ibrahim, 'cadre', true);
        $this->attachTacheUser($t, $seydou, 'collaborateur', false);

        $t = $this->makeTache($a, $aicha, 'Migrer les documents existants',
            'Indexation et migration de l\'ensemble des documents vers la GED.',
            'Zéro document perdu, tous les documents migrés et accessibles.',
            'a_faire', 'elevee', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $aicha, 'Former les équipes à la GED',
            'Sessions de formation utilisateur pour toutes les équipes.',
            '100% des agents formés et autonomes sur la GED.',
            'a_faire', 'moyenne', 0, now()->addMonths(4));
        $this->attachTacheUser($t, $fatouma, 'stagiaire', true);

        // ── WS1 · Project 4 — Intranet RH (completed) ────────────────────────
        $p4 = $this->makeProjet($ws1, 'Refonte Intranet RH',
            'Refonte de l\'intranet RH pour améliorer l\'accès aux informations collaborateurs.',
            $aicha, now()->subMonths(7), now()->subMonth(), 'completed');
        $this->attachProjetMembers($p4, $eric, $aicha, $kofi, $fatouma, $mamadou, $seydou);

        $a = $this->makeActivite($p4, 'Développement et intégration',
            'Développement des nouvelles fonctionnalités et intégration SI.',
            $aicha, now()->subMonths(6), now()->subMonths(3));
        $this->attachActiviteMembers($a, $aicha, $kofi, $seydou, $fatouma);

        $t = $this->makeTache($a, $aicha, 'Développer le répertoire collaborateurs',
            'Module d\'annuaire et de profil collaborateur.',
            'Tous les collaborateurs accessibles en moins de 2 clics.',
            'termine', 'elevee', 100, now()->subMonths(4));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'Modèle de données profil', 'termine', 100, 40, 1, now()->subMonths(5));
        $this->makeSousTache($t, $kofi, 'Interface annuaire', 'termine', 100, 40, 2, now()->subMonths(5));
        $this->makeSousTache($t, $fatouma, 'Tests de recette', 'termine', 100, 20, 3, now()->subMonths(4));

        $t = $this->makeTache($a, $aicha, 'Intégrer la gestion documentaire RH',
            'Module de partage et versioning de documents RH.',
            'Zéro document perdu, toutes les versions traçables.',
            'termine', 'moyenne', 100, now()->subMonths(3));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        $t = $this->makeTache($a, $aicha, 'Développer le module actualités RH',
            'Flux d\'actualités RH avec système de notifications.',
            'Taux de lecture des actualités RH supérieur à 70%.',
            'termine', 'faible', 100, now()->subMonths(3));
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $a = $this->makeActivite($p4, 'Déploiement et formation',
            'Mise en production de l\'intranet et formation des utilisateurs.',
            $eric, now()->subMonths(3), now()->subMonth());
        $this->attachActiviteMembers($a, $aicha, $kofi, $seydou);

        $t = $this->makeTache($a, $eric, 'Déployer l\'intranet en production',
            'Mise en production avec reprise des contenus existants.',
            'Intranet accessible à 100% du personnel.',
            'termine', 'critique', 100, now()->subMonths(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);

        $t = $this->makeTache($a, $eric, 'Animer les sessions de formation utilisateur',
            'Formation de l\'ensemble du personnel au nouvel intranet.',
            '100% du personnel formé.',
            'termine', 'moyenne', 100, now()->subMonth());
        $this->attachTacheUser($t, $seydou, 'collaborateur', true);
        $this->addComment($t, $eric, 'Formation terminée. 98% de satisfaction sur les retours utilisateurs.');

        // ── WS1 · Project 5 — Communication Interne (active, planning phase) ──
        $p5 = $this->makeProjet($ws1, 'Stratégie Communication Interne',
            'Refonte de la stratégie de communication interne pour améliorer l\'engagement des collaborateurs.',
            $eric, now(), now()->addMonths(6));
        $this->attachProjetMembers($p5, $eric, $aicha, $aminata, $mariam, $mamadou);

        $a = $this->makeActivite($p5, 'Diagnostic et stratégie',
            'Diagnostic de la communication interne existante et définition de la nouvelle stratégie.',
            $aminata, now(), now()->addMonths(2));
        $this->attachActiviteMembers($a, $aminata, $mariam, $fatouma);

        $t = $this->makeTache($a, $aminata, 'Conduire le sondage satisfaction communication',
            'Sondage anonyme sur la qualité de la communication interne.',
            'Taux de réponse supérieur à 70% avec résultats exploitables.',
            'en_cours', 'elevee', 40, now()->addWeeks(3));
        $this->attachTacheUser($t, $mariam, 'stagiaire', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $mariam, 'Rédiger le questionnaire', 'termine', 100, 30, 1, now()->subWeek());
        $this->makeSousTache($t, $mariam, 'Diffuser le sondage', 'en_cours', 50, 40, 2, now()->addWeeks(2));
        $this->makeSousTache($t, $fatouma, 'Analyser les résultats', 'a_faire', 0, 30, 3, now()->addWeeks(4));

        $t = $this->makeTache($a, $aminata, 'Rédiger la stratégie de communication',
            'Définir les axes, canaux et fréquences de communication interne.',
            'Plan de communication validé par la direction.',
            'a_faire', 'elevee', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $aminata, 'collaborateur', true);

        $t = $this->makeTache($a, $aminata, 'Sélectionner les outils de communication',
            'Benchmark et sélection des outils collaboratifs adaptés.',
            'Sélection validée avec plan de déploiement.',
            'a_faire', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $mariam, 'stagiaire', true);

        // ════════════════════════════════════════════════════════════════════════
        // WS 2 — Département Technique (owned by Eric)
        // 4 projects · 12 activities · ~36 tasks
        // ════════════════════════════════════════════════════════════════════════
        $ws2 = $this->makeWorkspace(
            'Département Technique — CERD Africa',
            'Espace de l\'équipe technique pour les projets infrastructure et innovation.',
            'CERD-WS-002',
            $eric,
            ['default_project_visibility' => 'team', 'members_can_create_projects' => true]
        );

        $eric->update(['current_workspace_id' => $ws2->id]);

        $this->attachMember($ws2, $eric, 'owner', $eric);
        $this->attachMember($ws2, $directeur, 'manager', $eric);
        $this->attachMember($ws2, $aicha, 'cadre', $eric);
        $this->attachMember($ws2, $ibrahim, 'collaborateur', $eric);
        $this->attachMember($ws2, $celestin, 'collaborateur', $eric);
        $this->attachMember($ws2, $patrick, 'collaborateur', $eric);
        $this->attachMember($ws2, $alice, 'collaborateur', $eric);
        $this->attachMember($ws2, $omar, 'cadre', $eric);
        $this->attachMember($ws2, $fatouma, 'stagiaire', $eric);
        $this->attachMember($ws2, $mamadou, 'observateur', $eric);

        foreach ([$celestin, $patrick, $alice, $omar] as $u) {
            $u->update(['current_workspace_id' => $ws2->id]);
        }

        // ── WS2 · Project 1 — Migration Cloud (active, in progress) ───────────
        $p6 = $this->makeProjet($ws2, 'Migration Infrastructure Cloud',
            'Migration progressive vers architecture cloud hybride AWS/Azure.',
            $eric, now()->subMonths(2), now()->addMonths(5));
        $p6->members()->attach($aicha->id, ['role_id' => $this->roleIds['cadre']]);
        $p6->members()->attach($omar->id, ['role_id' => $this->roleIds['cadre']]);
        $p6->members()->attach($ibrahim->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p6->members()->attach($celestin->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p6->members()->attach($patrick->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p6->members()->attach($fatouma->id, ['role_id' => $this->roleIds['stagiaire']]);
        $p6->members()->attach($mamadou->id, ['role_id' => $this->roleIds['observateur']]);

        // Activity 6.1
        $a = $this->makeActivite($p6, 'Audit et planification',
            'Inventaire infrastructure et planification de la migration.',
            $aicha, now()->subMonths(2), now()->subWeeks(3));
        $this->attachActiviteMembers($a, $aicha, $ibrahim, $celestin, $fatouma);

        $t = $this->makeTache($a, $aicha, 'Auditer l\'infrastructure existante',
            'Inventaire exhaustif des serveurs, services et dépendances.',
            'Cartographie complète avant toute migration.',
            'termine', 'elevee', 100, now()->subWeeks(4));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $celestin, 'collaborateur', false);
        $this->makeSousTache($t, $ibrahim, 'Inventaire serveurs physiques', 'termine', 100, 40, 1, now()->subWeeks(7));
        $this->makeSousTache($t, $celestin, 'Cartographie des dépendances', 'termine', 100, 35, 2, now()->subWeeks(6));
        $this->makeSousTache($t, $fatouma, 'Analyse des risques', 'termine', 100, 25, 3, now()->subWeeks(5));
        $this->addComment($t, $aicha, 'Audit terminé. 47 serveurs inventoriés, 12 dépendances critiques identifiées.');
        $this->addComment($t, $ibrahim, '3 serveurs en fin de vie détectés — à prioriser dans le plan de migration.');

        $t = $this->makeTache($a, $aicha, 'Définir l\'architecture cible',
            'Architecture cloud hybride cible et validation des choix technologiques.',
            'Architecture validée par le RSSI et la direction technique.',
            'en_cours', 'elevee', 60, now()->addWeeks(2));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $omar, 'cadre', false);
        $this->makeSousTache($t, $ibrahim, 'Choix des services cloud', 'termine', 100, 40, 1, now()->subWeek());
        $this->makeSousTache($t, $omar, 'Architecture réseau cible', 'en_cours', 50, 35, 2, now()->addWeek());
        $this->makeSousTache($t, $ibrahim, 'Validation RSSI', 'a_faire', 0, 25, 3, now()->addWeeks(3));

        $t = $this->makeTache($a, $aicha, 'Élaborer le plan de migration',
            'Planifier les vagues de migration par priorité et complexité.',
            'Plan de migration détaillé avec dates et responsables.',
            'en_cours', 'moyenne', 40, now()->addMonth());
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);

        // Activity 6.2
        $a = $this->makeActivite($p6, 'Migration des services prioritaires',
            'Première vague : services critiques et bases de données.',
            $aicha, now(), now()->addMonths(3));
        $this->attachActiviteMembers($a, $omar, $ibrahim, $celestin, $patrick, $fatouma);

        $t = $this->makeTache($a, $omar, 'Migrer les bases de données',
            'Migration vers les services managés cloud avec réplication et failover.',
            'Zéro perte de données, moins de 30 minutes d\'interruption.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $celestin, 'collaborateur', false);

        $t = $this->makeTache($a, $omar, 'Migrer les applications web',
            'Containeriser et déployer les applications web sur l\'infrastructure cloud.',
            'Toutes les applications sur le cloud avec autoscaling configuré.',
            'a_faire', 'elevee', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $omar, 'Configurer la supervision cloud',
            'Outils de monitoring et alertes sur l\'infrastructure cloud.',
            'Détection de tout incident en moins de 2 minutes.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);

        $t = $this->makeTache($a, $omar, 'Configurer les sauvegardes automatisées',
            'Mise en place des politiques de backup et restauration.',
            'RPO inférieur à 1h sur tous les services critiques.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);

        // Activity 6.3
        $a = $this->makeActivite($p6, 'Validation et optimisation des coûts',
            'Tests PRA et optimisation des ressources cloud.',
            $eric, now()->addMonths(3), now()->addMonths(5));
        $this->attachActiviteMembers($a, $omar, $ibrahim, $celestin);

        $t = $this->makeTache($a, $eric, 'Valider la reprise après sinistre',
            'Tester les procédures PRA sur la nouvelle infrastructure.',
            'RTO < 4h et RPO < 1h validés.',
            'a_faire', 'critique', 0, now()->addMonths(5));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);

        $t = $this->makeTache($a, $eric, 'Optimiser les coûts cloud',
            'Analyser la consommation et optimiser les ressources.',
            'Réduction de 30% des coûts infrastructure.',
            'a_faire', 'moyenne', 0, now()->addMonths(5));
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $eric, 'Rédiger la documentation opérationnelle',
            'Documentation d\'exploitation de la nouvelle infrastructure.',
            'Équipe ops autonome sans assistance externe.',
            'a_faire', 'faible', 0, now()->addMonths(5));
        $this->attachTacheUser($t, $fatouma, 'stagiaire', true);

        // ── WS2 · Project 2 — Monitoring (active) ───────────────────────────
        $p7 = $this->makeProjet($ws2, 'Refonte Système de Monitoring',
            'Remplacement de l\'ancien monitoring par Grafana + Prometheus centralisé.',
            $eric, now()->subMonth(), now()->addMonths(3));
        $p7->members()->attach($omar->id, ['role_id' => $this->roleIds['cadre']]);
        $p7->members()->attach($ibrahim->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p7->members()->attach($celestin->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p7->members()->attach($patrick->id, ['role_id' => $this->roleIds['collaborateur']]);

        $a = $this->makeActivite($p7, 'Installation et configuration',
            'Installation de Grafana, Prometheus et des exporters.',
            $omar, now()->subMonth(), now()->addMonth());
        $this->attachActiviteMembers($a, $omar, $ibrahim, $celestin, $patrick);

        $t = $this->makeTache($a, $omar, 'Installer Grafana et Prometheus',
            'Déployer la stack de monitoring sur les serveurs dédiés.',
            'Stack opérationnelle avec accès sécurisé.',
            'en_cours', 'critique', 70, now()->addWeeks(2));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $celestin, 'collaborateur', false);
        $this->makeSousTache($t, $ibrahim, 'Installation Prometheus', 'termine', 100, 35, 1, now()->subWeeks(3));
        $this->makeSousTache($t, $celestin, 'Installation Grafana', 'termine', 100, 35, 2, now()->subWeeks(2));
        $this->makeSousTache($t, $ibrahim, 'Configuration des exporters', 'en_cours', 50, 30, 3, now()->addWeek());

        $t = $this->makeTache($a, $omar, 'Configurer les dashboards métier',
            'Dashboards pour les équipes dev, ops et direction.',
            'Chaque équipe dispose de son tableau de bord opérationnel.',
            'en_cours', 'elevee', 40, now()->addMonths(2));
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);

        $t = $this->makeTache($a, $omar, 'Paramétrer les alertes critiques',
            'Alertes pour les incidents de production P1/P2.',
            'Alerte automatique en moins de 2 minutes sur tout incident P1.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);

        $t = $this->makeTache($a, $omar, 'Former l\'équipe ops à Grafana',
            'Sessions de formation à la lecture et création de dashboards.',
            'Équipe ops autonome sur Grafana.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $patrick, 'collaborateur', true);

        $a = $this->makeActivite($p7, 'Intégration et tests',
            'Intégration avec les services existants et tests de couverture.',
            $omar, now()->addMonth(), now()->addMonths(3));
        $this->attachActiviteMembers($a, $omar, $ibrahim, $celestin);

        $t = $this->makeTache($a, $eric, 'Intégrer le monitoring applicatif',
            'Connexion des applications métier aux collecteurs Prometheus.',
            'Couverture de monitoring à 100% des services critiques.',
            'a_faire', 'elevee', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);

        $t = $this->makeTache($a, $eric, 'Valider les seuils d\'alerte',
            'Affiner les seuils d\'alerte après 2 semaines de données en prod.',
            'Taux de faux positifs inférieur à 5%.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);

        // ── WS2 · Project 3 — Sécurité SI (active, urgent) ──────────────────
        $p8 = $this->makeProjet($ws2, 'Audit et Renforcement Sécurité SI',
            'Audit de sécurité du système d\'information et plan de remédiation.',
            $eric, now()->subWeeks(3), now()->addMonths(4));
        $p8->members()->attach($omar->id, ['role_id' => $this->roleIds['cadre']]);
        $p8->members()->attach($alice->id, ['role_id' => $this->roleIds['cadre']]);
        $p8->members()->attach($ibrahim->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p8->members()->attach($patrick->id, ['role_id' => $this->roleIds['collaborateur']]);

        $a = $this->makeActivite($p8, 'Audit de sécurité',
            'Audit complet de la sécurité du SI : réseau, applications et accès.',
            $alice, now()->subWeeks(3), now()->addWeeks(2));
        $this->attachActiviteMembers($a, $alice, $omar, $ibrahim, $patrick);

        $t = $this->makeTache($a, $alice, 'Réaliser le pentest du SI',
            'Tests d\'intrusion sur le périmètre réseau et les applications critiques.',
            'Rapport de pentest avec vulnérabilités classées par criticité.',
            'en_cours', 'critique', 55, now()->addWeek());
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);
        $this->makeSousTache($t, $ibrahim, 'Reconnaissance et cartographie', 'termine', 100, 30, 1, now()->subWeeks(2));
        $this->makeSousTache($t, $ibrahim, 'Tests d\'intrusion réseau', 'en_cours', 60, 40, 2, now()->addWeek());
        $this->makeSousTache($t, $patrick, 'Tests d\'intrusion applicatif', 'a_faire', 0, 30, 3, now()->addWeeks(2));
        $this->addComment($t, $alice, 'Premières vulnérabilités identifiées sur le pare-feu périmétrique.');
        $this->addComment($t, $ibrahim, '2 CVE critiques trouvées sur le serveur d\'authentification.');
        $this->addComment($t, $eric, 'Prioriser la remédiation des CVE critiques immédiatement.');

        $t = $this->makeTache($a, $alice, 'Auditer les politiques d\'accès',
            'Revue des droits utilisateurs et des politiques IAM.',
            '100% des accès conformes au principe du moindre privilège.',
            'en_cours', 'elevee', 30, now()->addWeeks(3));
        $this->attachTacheUser($t, $patrick, 'collaborateur', true);

        $t = $this->makeTache($a, $alice, 'Auditer la sécurité des données sensibles',
            'Identification et classification des données sensibles.',
            'Cartographie des données sensibles avec niveau de protection actuel.',
            'a_faire', 'elevee', 0, now()->addWeeks(4));
        $this->attachTacheUser($t, $omar, 'cadre', true);

        $a = $this->makeActivite($p8, 'Plan de remédiation',
            'Définition et mise en œuvre du plan de remédiation des vulnérabilités.',
            $alice, now()->addWeeks(2), now()->addMonths(4));
        $this->attachActiviteMembers($a, $alice, $omar, $ibrahim, $patrick);

        $t = $this->makeTache($a, $eric, 'Corriger les vulnérabilités critiques',
            'Application des correctifs et mises à jour de sécurité urgentes.',
            'Toutes les CVE critiques corrigées en moins de 72h.',
            'a_faire', 'critique', 0, now()->addWeeks(3));
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);

        $t = $this->makeTache($a, $eric, 'Mettre en place le MFA sur tous les accès',
            'Déploiement de l\'authentification multi-facteurs sur tous les systèmes.',
            'Zéro accès critique sans MFA.',
            'a_faire', 'critique', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $omar, 'cadre', true);
        $this->attachTacheUser($t, $ibrahim, 'collaborateur', false);

        $t = $this->makeTache($a, $eric, 'Rédiger la politique de sécurité SI',
            'Formalisation de la politique de sécurité et des procédures associées.',
            'Politique validée par la direction et intégrée au SMSI.',
            'a_faire', 'elevee', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $alice, 'cadre', true);

        // ── WS2 · Project 4 — DevOps Pipeline (active) ───────────────────────
        $p9 = $this->makeProjet($ws2, 'Mise en place Pipeline DevOps',
            'Automatisation des chaînes CI/CD pour accélérer les livraisons et réduire les erreurs.',
            $omar, now()->subWeeks(2), now()->addMonths(4));
        $p9->members()->attach($alice->id, ['role_id' => $this->roleIds['cadre']]);
        $p9->members()->attach($celestin->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p9->members()->attach($patrick->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p9->members()->attach($fatouma->id, ['role_id' => $this->roleIds['stagiaire']]);

        $a = $this->makeActivite($p9, 'Configuration CI/CD',
            'Mise en place des pipelines d\'intégration et déploiement continus.',
            $alice, now()->subWeeks(2), now()->addMonths(2));
        $this->attachActiviteMembers($a, $alice, $celestin, $patrick, $fatouma);

        $t = $this->makeTache($a, $alice, 'Configurer GitLab CI pour les projets critiques',
            'Pipelines de build, test et déploiement automatisés.',
            'Temps de déploiement réduit de 60%.',
            'en_cours', 'elevee', 45, now()->addMonth());
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);
        $this->attachTacheUser($t, $patrick, 'collaborateur', false);
        $this->makeSousTache($t, $celestin, 'Pipeline de build et tests', 'en_cours', 60, 40, 1, now()->addWeeks(2));
        $this->makeSousTache($t, $patrick, 'Pipeline de déploiement', 'a_faire', 0, 35, 2, now()->addWeeks(4));
        $this->makeSousTache($t, $fatouma, 'Documentation des pipelines', 'a_faire', 0, 25, 3, now()->addMonths(2));

        $t = $this->makeTache($a, $alice, 'Mettre en place les tests automatisés',
            'Intégration des suites de tests dans les pipelines CI.',
            'Couverture de tests supérieure à 80%.',
            'a_faire', 'elevee', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $celestin, 'collaborateur', true);

        $t = $this->makeTache($a, $alice, 'Containeriser les applications',
            'Migration des applications vers Docker et orchestration Kubernetes.',
            'Toutes les applications critiques containerisées.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $patrick, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $alice, 'Former l\'équipe aux pratiques DevOps',
            'Ateliers pratiques sur CI/CD, Docker et Kubernetes.',
            'Équipe autonome sur les outils DevOps.',
            'a_faire', 'faible', 0, now()->addMonths(4));
        $this->attachTacheUser($t, $fatouma, 'stagiaire', true);

        // ════════════════════════════════════════════════════════════════════════
        // WS 3 — Pôle Innovation (owned by Aïcha)
        // 3 projects · 6 activities · 18 tasks
        // ════════════════════════════════════════════════════════════════════════
        $ws3 = $this->makeWorkspace(
            'Pôle Innovation — CERD Africa',
            'Espace dédié aux expérimentations et projets d\'innovation interne.',
            'CERD-WS-003',
            $aicha,
            ['default_project_visibility' => 'team', 'members_can_create_projects' => true]
        );

        $aicha->update(['current_workspace_id' => $ws3->id]);

        $this->attachMember($ws3, $aicha, 'owner', $aicha);
        $this->attachMember($ws3, $eric, 'manager', $aicha);
        $this->attachMember($ws3, $aminata, 'cadre', $aicha);
        $this->attachMember($ws3, $kofi, 'collaborateur', $aicha);
        $this->attachMember($ws3, $alice, 'collaborateur', $aicha);
        $this->attachMember($ws3, $fatouma, 'stagiaire', $aicha);
        $this->attachMember($ws3, $mariam, 'stagiaire', $aicha);
        $this->attachMember($ws3, $mamadou, 'observateur', $aicha);

        // ── WS3 · Project 1 — Chatbot RH (active) ────────────────────────────
        $p10 = $this->makeProjet($ws3, 'Chatbot RH Interne',
            'Assistant conversationnel IA pour les demandes RH courantes.',
            $aicha, now()->subWeeks(3), now()->addMonths(4));
        $p10->members()->attach($aminata->id, ['role_id' => $this->roleIds['cadre']]);
        $p10->members()->attach($kofi->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p10->members()->attach($alice->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p10->members()->attach($fatouma->id, ['role_id' => $this->roleIds['stagiaire']]);
        $p10->members()->attach($mariam->id, ['role_id' => $this->roleIds['stagiaire']]);
        $p10->members()->attach($mamadou->id, ['role_id' => $this->roleIds['observateur']]);

        $a = $this->makeActivite($p10, 'Conception et prototypage',
            'Définition du scope et création du premier prototype.',
            $aminata, now()->subWeeks(3), now()->addWeeks(4));
        $this->attachActiviteMembers($a, $aminata, $kofi, $alice, $fatouma, $mariam);

        $t = $this->makeTache($a, $aminata, 'Définir les cas d\'usage du chatbot',
            'Lister et prioriser les 20 demandes RH les plus fréquentes.',
            'Document validé par la DRH avec les cas d\'usage retenus.',
            'en_cours', 'elevee', 60, now()->addWeeks(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'Analyse des tickets RH existants', 'termine', 100, 40, 1, now()->subWeeks(2));
        $this->makeSousTache($t, $fatouma, 'Priorisation avec la DRH', 'en_cours', 50, 35, 2, now()->addWeek());
        $this->makeSousTache($t, $kofi, 'Rédaction du document final', 'a_faire', 0, 25, 3, now()->addWeeks(2));
        $this->addComment($t, $aminata, 'La DRH a validé 18 des 20 cas d\'usage proposés. Très bon résultat.');

        $t = $this->makeTache($a, $aminata, 'Créer le prototype conversationnel',
            'Premier prototype du chatbot avec 5 cas d\'usage pilotes.',
            'Démo fonctionnelle présentable à la direction.',
            'a_faire', 'elevee', 0, now()->addWeeks(5));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $alice, 'collaborateur', false);

        $t = $this->makeTache($a, $aminata, 'Rédiger le cahier de recette',
            'Critères d\'acceptation et scénarios de test.',
            'Cahier de recette complet couvrant tous les cas d\'usage.',
            'a_faire', 'faible', 0, now()->addWeeks(6));
        $this->attachTacheUser($t, $mariam, 'stagiaire', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        $t = $this->makeTache($a, $aminata, 'Intégrer le chatbot à l\'intranet',
            'Embedding du chatbot dans l\'intranet RH existant.',
            'Chatbot accessible depuis l\'intranet sans authentification supplémentaire.',
            'a_faire', 'moyenne', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $alice, 'collaborateur', true);

        // ── WS3 · Project 2 — BI & Analytics (active) ────────────────────────
        $p11 = $this->makeProjet($ws3, 'Tableau de Bord BI Décisionnel',
            'Mise en place d\'un outil de Business Intelligence pour la direction.',
            $aicha, now()->subWeeks(4), now()->addMonths(5));
        $p11->members()->attach($aminata->id, ['role_id' => $this->roleIds['cadre']]);
        $p11->members()->attach($kofi->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p11->members()->attach($alice->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p11->members()->attach($mariam->id, ['role_id' => $this->roleIds['stagiaire']]);
        $p11->members()->attach($mamadou->id, ['role_id' => $this->roleIds['observateur']]);

        $a = $this->makeActivite($p11, 'Conception et modélisation des données',
            'Identification des sources de données et modélisation du datawarehouse.',
            $aminata, now()->subWeeks(4), now()->subWeeks(1));
        $this->attachActiviteMembers($a, $aminata, $kofi, $mariam);

        $t = $this->makeTache($a, $aminata, 'Identifier les sources de données',
            'Cartographier toutes les sources de données disponibles.',
            'Catalogue de données complet avec qualité évaluée.',
            'termine', 'elevee', 100, now()->subWeeks(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $mariam, 'stagiaire', false);
        $this->makeSousTache($t, $kofi, 'Audit des bases de données ERP', 'termine', 100, 40, 1, now()->subWeeks(4));
        $this->makeSousTache($t, $mariam, 'Recensement des fichiers Excel', 'termine', 100, 35, 2, now()->subWeeks(3));
        $this->makeSousTache($t, $kofi, 'Évaluation de la qualité des données', 'termine', 100, 25, 3, now()->subWeeks(2));

        $t = $this->makeTache($a, $aminata, 'Modéliser le datawarehouse',
            'Conception du schéma en étoile du datawarehouse décisionnel.',
            'Modèle dimensionnel validé par la MOA.',
            'en_cours', 'elevee', 70, now()->addWeeks(2));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->makeSousTache($t, $kofi, 'Identification des faits et dimensions', 'termine', 100, 40, 1, now()->subWeek());
        $this->makeSousTache($t, $kofi, 'Modèle physique de données', 'en_cours', 60, 40, 2, now()->addWeek());
        $this->makeSousTache($t, $kofi, 'Validation avec la MOA', 'a_faire', 0, 20, 3, now()->addWeeks(3));

        $a = $this->makeActivite($p11, 'Développement des dashboards',
            'Développement des tableaux de bord décisionnels.',
            $aminata, now(), now()->addMonths(4));
        $this->attachActiviteMembers($a, $aminata, $kofi, $alice, $mariam);

        $t = $this->makeTache($a, $aminata, 'Développer le dashboard direction',
            'Tableau de bord synthétique pour la direction avec KPIs stratégiques.',
            'Dashboard chargé en moins d\'une seconde avec données actualisées.',
            'en_cours', 'critique', 25, now()->addMonths(2));
        $this->attachTacheUser($t, $alice, 'collaborateur', true);
        $this->attachTacheUser($t, $kofi, 'collaborateur', false);

        $t = $this->makeTache($a, $aminata, 'Développer les dashboards opérationnels',
            'Tableaux de bord par département (RH, Finance, Technique).',
            'Un dashboard par département, accessible aux responsables.',
            'a_faire', 'elevee', 0, now()->addMonths(3));
        $this->attachTacheUser($t, $kofi, 'collaborateur', true);
        $this->attachTacheUser($t, $mariam, 'stagiaire', false);

        $t = $this->makeTache($a, $aminata, 'Former les utilisateurs BI',
            'Formation à la lecture et à l\'exploitation des dashboards.',
            '100% des utilisateurs cibles formés et autonomes.',
            'a_faire', 'moyenne', 0, now()->addMonths(5));
        $this->attachTacheUser($t, $mariam, 'stagiaire', true);

        // ── WS3 · Project 3 — Automatisation RPA (active, early stage) ────────
        $p12 = $this->makeProjet($ws3, 'Automatisation par RPA',
            'Identification et automatisation des tâches répétitives par robotique de processus.',
            $aicha, now()->subWeek(), now()->addMonths(6));
        $p12->members()->attach($aminata->id, ['role_id' => $this->roleIds['cadre']]);
        $p12->members()->attach($alice->id, ['role_id' => $this->roleIds['collaborateur']]);
        $p12->members()->attach($fatouma->id, ['role_id' => $this->roleIds['stagiaire']]);
        $p12->members()->attach($mamadou->id, ['role_id' => $this->roleIds['observateur']]);

        $a = $this->makeActivite($p12, 'Identification des opportunités RPA',
            'Recensement et priorisation des processus candidats à l\'automatisation.',
            $aminata, now()->subWeek(), now()->addMonths(2));
        $this->attachActiviteMembers($a, $aminata, $alice, $fatouma);

        $t = $this->makeTache($a, $aminata, 'Cartographier les processus répétitifs',
            'Recenser tous les processus manuels et répétitifs dans les métiers cibles.',
            'Liste de 20 processus candidats avec estimation du ROI.',
            'en_cours', 'elevee', 30, now()->addMonths(2));
        $this->attachTacheUser($t, $alice, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);
        $this->makeSousTache($t, $alice, 'Ateliers de recensement RH', 'en_cours', 40, 35, 1, now()->addWeeks(3));
        $this->makeSousTache($t, $fatouma, 'Ateliers de recensement Finance', 'a_faire', 0, 35, 2, now()->addWeeks(5));
        $this->makeSousTache($t, $alice, 'Priorisation par ROI', 'a_faire', 0, 30, 3, now()->addMonths(2));

        $t = $this->makeTache($a, $aminata, 'Évaluer les outils RPA du marché',
            'Benchmark des solutions RPA (UiPath, Automation Anywhere, Power Automate).',
            'Recommandation outillée avec preuve de concept.',
            'a_faire', 'moyenne', 0, now()->addMonths(2));
        $this->attachTacheUser($t, $alice, 'collaborateur', true);
        $this->addComment($t, $aicha, 'Prioriser les outils compatibles avec notre stack Microsoft existante.');

        $t = $this->makeTache($a, $aminata, 'Réaliser un pilote d\'automatisation',
            'Automatiser le processus le plus rapide à implémenter comme pilote.',
            'Pilote opérationnel avec mesure du gain de temps réalisé.',
            'a_faire', 'elevee', 0, now()->addMonths(4));
        $this->attachTacheUser($t, $alice, 'collaborateur', true);
        $this->attachTacheUser($t, $fatouma, 'stagiaire', false);

        // ── current_workspace_id assignments ─────────────────────────────────
        $directeur->update(['current_workspace_id' => $ws1->id]);
        $superAdmin->update(['current_workspace_id' => $ws1->id]);
        $kofi->update(['current_workspace_id' => $ws1->id]);
        $aminata->update(['current_workspace_id' => $ws1->id]);
        $fatouma->update(['current_workspace_id' => $ws1->id]);
        $mamadou->update(['current_workspace_id' => $ws1->id]);
        $seydou->update(['current_workspace_id' => $ws1->id]);
        $mariam->update(['current_workspace_id' => $ws3->id]);

        // ── Résultats de validation — scénarios complets ──────────────────────
        $this->seedResultats($kofi, $aicha, $eric);
        $this->seedResultats($aminata, $aicha, $eric);
        $this->seedResultats($ibrahim, $aicha, $eric);

        $this->command->info('Workspace seeded successfully!');
        $this->command->newLine();
        $this->command->info('3 workspaces · 12 projets · 30 activités · ~100 tâches');
        $this->command->newLine();
        $this->command->info('WS1 — Direction Générale (directeur) : 5 projets');
        $this->command->info('WS2 — Département Technique (manager) : 4 projets');
        $this->command->info('WS3 — Pôle Innovation (cadre) : 3 projets');
        $this->command->newLine();
        $this->command->info('Comptes de test (mot de passe : password) :');
        $this->command->info('  superadmin@worktracking.com    → super_admin');
        $this->command->info('  directeur@worktracking.com     → directeur (owner WS1)');
        $this->command->info('  manager@worktracking.com       → Éric Kouassi (owner WS2, manager WS1+WS3)');
        $this->command->info('  cadre@worktracking.com         → Aïcha Traoré (owner WS3, cadre WS1+WS2)');
        $this->command->info('  collaborateur@worktracking.com → Kofi Mensah (WS1+WS3)');
        $this->command->info('  stagiaire@worktracking.com     → Fatouma Diallo (WS1+WS2+WS3)');
        $this->command->info('  observateur@worktracking.com   → Mamadou Sanogo (WS1+WS2+WS3)');
        $this->command->info('  aminata@worktracking.com       → Aminata Koné (cadre WS3, collab WS1)');
        $this->command->info('  ibrahim@worktracking.com       → Ibrahim Bamba (cadre WS1, collab WS2)');
        $this->command->info('  celestin@worktracking.com      → Céléstin Akpo (collab WS2)');
        $this->command->info('  seydou@worktracking.com        → Seydou Coulibaly (collab WS1)');
        $this->command->info('  mariam@worktracking.com        → Mariam Ouédraogo (stagiaire WS1+WS3)');
        $this->command->info('  patrick@worktracking.com       → Patrick Gbagbo (collab WS2)');
        $this->command->info('  alice@worktracking.com         → Alice Assi (cadre WS2, collab WS3)');
        $this->command->info('  omar@worktracking.com          → Omar Diakité (cadre WS2)');
    }

    // ── helpers ───────────────────────────────────────────────────────────────

    private function ensureUser(string $nom, string $prenom, string $email, Role $globalRole): User
    {
        $user = User::firstOrCreate(
            ['email' => $email],
            [
                'nom' => $nom,
                'prenom' => $prenom,
                'nom_complet' => "$prenom $nom",
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_active' => true,
            ]
        );
        $user->syncRoles([$globalRole->value]);

        return $user;
    }

    /** @param array<string,mixed> $settings */
    private function makeWorkspace(string $nom, string $description, string $code, User $owner, array $settings = []): Workspace
    {
        return Workspace::create([
            'nom' => $nom,
            'description' => $description,
            'code' => $code,
            'owner_id' => $owner->id,
            'is_active' => true,
            'settings' => $settings,
        ]);
    }

    private function attachMember(Workspace $ws, User $user, string $role, User $invitedBy): void
    {
        $ws->members()->attach($user->id, [
            'role_id' => $this->roleIds[$role],
            'invited_at' => now(),
            'invited_by' => $invitedBy->id,
        ]);
    }

    private function makeProjet(Workspace $ws, string $nom, string $description, User $responsable, \DateTimeInterface $debut, \DateTimeInterface $fin, string $status = 'active'): Projet
    {
        return Projet::create([
            'workspace_id' => $ws->id,
            'nom' => $nom,
            'description' => $description,
            'responsable_id' => $responsable->id,
            'date_debut' => $debut,
            'date_fin' => $fin,
            'visibility' => 'team',
            'status' => $status,
        ]);
    }

    private function attachProjetMembers(Projet $p, User ...$users): void
    {
        $roleMap = ['manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'];
        foreach ($users as $i => $user) {
            $role = $roleMap[$i] ?? 'collaborateur';
            $p->members()->attach($user->id, ['role_id' => $this->roleIds[$role]]);
        }
    }

    private function makeActivite(Projet $p, string $nom, string $description, User $responsable, \DateTimeInterface $debut, \DateTimeInterface $fin): Activite
    {
        return Activite::create([
            'projet_id' => $p->id,
            'nom' => $nom,
            'description' => $description,
            'responsable_id' => $responsable->id,
            'date_debut' => $debut,
            'date_fin' => $fin,
        ]);
    }

    private function attachActiviteMembers(Activite $a, User ...$users): void
    {
        foreach ($users as $i => $user) {
            $isFirst = $i === 0;
            $a->members()->attach($user->id, [
                'role_id' => $this->roleIds[$isFirst ? 'cadre' : 'collaborateur'],
                'can_create_tasks' => $isFirst,
                'can_edit_tasks' => $isFirst,
                'can_delete_tasks' => $isFirst,
                'can_validate_results' => $isFirst,
                'can_assign_users' => $isFirst,
            ]);
        }
    }

    private function makeTache(
        Activite $activite,
        User $responsable,
        string $titre,
        string $description,
        string $objectif,
        string $statut,
        string $priorite,
        int $taux,
        \DateTimeInterface $echeance
    ): Tache {
        return Tache::create([
            'activite_id' => $activite->id,
            'responsable_id' => $responsable->id,
            'titre' => $titre,
            'description' => $description,
            'objectif' => $objectif,
            'statut' => $statut,
            'priorite' => $priorite,
            'echeance' => $echeance,
            'taux_realisation' => $taux,
            'validation_n1_required' => true,
            'validation_n2_required' => true,
        ]);
    }

    private function attachTacheUser(Tache $tache, User $user, string $role, bool $isResponsable): void
    {
        $tache->assignees()->attach($user->id, [
            'role_id' => $this->roleIds[$role],
            'is_responsable' => $isResponsable,
            'can_edit' => false,
        ]);
    }

    private function makeSousTache(
        Tache $tache,
        User $responsable,
        string $titre,
        string $statut,
        int $progression,
        int $poids,
        int $ordre,
        \DateTimeInterface $echeance
    ): SousTache {
        return SousTache::create([
            'tache_id' => $tache->id,
            'responsable_id' => $responsable->id,
            'titre' => $titre,
            'statut' => $statut,
            'progression' => $progression,
            'poids' => $poids,
            'ordre' => $ordre,
            'date_echeance' => $echeance,
        ]);
    }

    private function addComment(Tache $tache, User $author, string $content): void
    {
        Comment::create([
            'commentable_type' => Tache::class,
            'commentable_id' => $tache->id,
            'user_id' => $author->id,
            'content' => $content,
            'content_html' => "<p>$content</p>",
        ]);
    }

    private function seedResultats(User $collaborateur, User $n1, User $n2): void
    {
        $tasks = Tache::whereHas('assignees', fn ($q) => $q
            ->where('tache_user.user_id', $collaborateur->id)
            ->where('tache_user.is_responsable', true)
        )->take(6)->get();

        $scenarios = [
            // 0 — brouillon
            fn (Tache $t) => TacheResultat::create([
                'tache_id' => $t->id,
                'user_id' => $collaborateur->id,
                'is_individual' => true,
                'statut' => 'brouillon',
                'resultats_attendus' => 'Livrer un rapport d\'analyse complet.',
                'resultats_obtenus' => 'Brouillon en cours de rédaction.',
                'taux_realisation' => 20,
            ]),

            // 1 — en_verification_n0
            function (Tache $t) use ($collaborateur) {
                $r = TacheResultat::factory()->enVerificationN0()->create([
                    'tache_id' => $t->id,
                    'user_id' => $collaborateur->id,
                    'resultats_attendus' => 'Analyse complète des besoins fonctionnels.',
                    'resultats_obtenus' => 'Analyse avec cartographie de 12 processus et 34 exigences documentées.',
                    'taux_realisation' => 90,
                ]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'soumis', 'context' => ['taux_realisation' => 90]]);
            },

            // 2 — a_refaire (renvoyé par N0)
            function (Tache $t) use ($collaborateur) {
                $n0 = $t->assignees()->wherePivot('is_responsable', true)->first();
                $n0Id = $n0?->id ?? $collaborateur->id;
                $r = TacheResultat::factory()->renvoyeParN0($n0Id)->create([
                    'tache_id' => $t->id,
                    'user_id' => $collaborateur->id,
                    'resultats_attendus' => 'Conception de la base de données conforme au CDC.',
                    'resultats_obtenus' => 'Schéma ERD complet avec 18 tables, toutes les relations documentées.',
                    'taux_realisation' => 75,
                    'commentaire_n0' => 'Les index de performance ne sont pas documentés.',
                ]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'soumis', 'context' => ['taux_realisation' => 75]]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $n0Id, 'action' => 'renvoye', 'context' => ['commentaire' => 'Les index de performance ne sont pas documentés.']]);
            },

            // 3 — bypass actif → en_validation_n1
            function (Tache $t) use ($collaborateur) {
                $motif = 'L\'absence d\'index est un choix de performance documenté dans l\'annexe technique. Le renvoi n\'est pas justifié.';
                $r = TacheResultat::factory()->bypassActive($motif)->create([
                    'tache_id' => $t->id,
                    'user_id' => $collaborateur->id,
                    'resultats_attendus' => 'Développement des endpoints API REST v2.',
                    'resultats_obtenus' => 'API complète avec 47 endpoints documentés, tests Postman inclus.',
                    'taux_realisation' => 95,
                ]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'soumis', 'context' => ['taux_realisation' => 95]]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'bypass', 'context' => ['motif' => $motif]]);
            },

            // 4 — en_validation_n1 (approuvé par N0)
            function (Tache $t) use ($collaborateur) {
                $r = TacheResultat::factory()->create([
                    'tache_id' => $t->id,
                    'user_id' => $collaborateur->id,
                    'statut' => 'en_validation_n1',
                    'soumis_le' => now()->subDays(2),
                    'soumis_n0_le' => now()->subDays(2),
                    'action_n0' => 'approuve',
                    'action_n0_le' => now()->subDay(),
                    'bypass_active' => false,
                    'resultats_attendus' => 'Dashboard analytique avec KPIs temps réel.',
                    'resultats_obtenus' => 'Dashboard livré avec 8 widgets, données actualisées toutes les 5 min.',
                    'taux_realisation' => 100,
                ]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'soumis', 'context' => ['taux_realisation' => 100]]);
                ValidationAuditLog::create(['tache_resultat_id' => $r->id, 'actor_id' => $collaborateur->id, 'action' => 'approuve', 'context' => []]);
            },

            // 5 — validé N1 + N2
            function (Tache $t) use ($collaborateur, $n1, $n2) {
                TacheResultat::factory()->valideN2()->create([
                    'tache_id' => $t->id,
                    'user_id' => $collaborateur->id,
                    'resultats_attendus' => 'Documentation technique complète.',
                    'resultats_obtenus' => 'Documentation de 45 pages couvrant architecture, déploiement et maintenance.',
                    'taux_realisation' => 100,
                    'validateur_n1_id' => $n1->id,
                    'validateur_n2_id' => $n2->id,
                    'commentaire_n1' => 'Travail de qualité, bien structuré.',
                    'commentaire_n2' => 'Validé. Excellent rapport.',
                ]);
            },
        ];

        foreach ($tasks as $index => $tache) {
            if (isset($scenarios[$index])) {
                ($scenarios[$index])($tache);
            }
        }
    }
}
