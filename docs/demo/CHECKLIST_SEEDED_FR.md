# Liste de Vérification — Démo avec Données Pré-chargées
**Script :** `DEMO_SCRIPT_FR.md`
**À exécuter après :** `php artisan migrate:fresh --seed` et avant la présentation.

---

## ✅ Problèmes critiques — Tous corrigés

### Problème 1 — Les limites d'abonnement n'étaient pas appliquées ✅ CORRIGÉ
**Avant :** `WorkspaceSeeder` n'assignait jamais de `plan_id` — aucune limite n'était imposée.
**Corrigé dans :** `database/seeders/WorkspaceSeeder.php` — le plan Gratuit est maintenant assigné aux 3 workspaces immédiatement après leur création. Le WS1 a 11 membres pour une limite de 5 (plan Gratuit) — le toast de dépassement se déclenche dès la première tentative d'invitation.

---

## ⚠️ Problèmes mineurs (ajuster le wording uniquement — aucun code à corriger)

### Problème 2 — Le paramètre "fréquence du résumé" n'existe pas dans l'interface
**Ce que le script dit :** *"Les mises à jour peu prioritaires peuvent être regroupées dans un résumé quotidien ou hebdomadaire."*
**Ce qui existe réellement :** Des interrupteurs par événement (in-app, email, push) et un paramètre **Heures de silence** — aucun menu déroulant de fréquence de résumé.

**Dans la démo, dire à la place :**
> "L'utilisateur peut définir des heures de silence — pas de notifications entre 22h et 7h par exemple. En dehors de ces plages, tout arrive en temps réel."

La fonctionnalité de résumé quotidien existe côté backend (commande `notifications:send-digest`) mais n'est pas exposée dans l'interface — ne pas en parler.

---

### Problème 3 — L'autocomplétion des @mentions dans le chat n'existe pas visuellement
**Ce que le script dit :** *"J'ai utilisé la mention @Éric pour le notifier directement."*
**Ce qui existe réellement :** Le backend déclenche `ChatMentionNotification` quand un message contient `@Nom` — mais il n'y a pas de menu déroulant d'autocomplétion. Taper la mention en texte brut.

**Dans la démo, dire à la place :**
> "Les membres peuvent mentionner leurs collègues par leur nom. Éric recevra une notification instantanément."

---

### Problème 4 — "Partager par Email" est dans un sous-composant
**Ce que le script dit :** Naviguer vers Documents du Workspace → cliquer Partager par Email.
**Ce qui existe réellement :** Le bouton se trouve dans le sous-composant `DocumentManager` — un niveau plus profond. Cliquer d'abord sur un document, puis chercher Partager dans le menu d'actions.

---

### Problème 5 — Le glisser-déposer du Kanban a un handler incomplet
**Ce que le script dit :** Faire glisser une carte de projet d'Actif → Terminé.
**Ce qui existe réellement :** Le binding `@task-drop` est câblé dans `Dashboard.vue` (ligne 171) mais le handler a un commentaire TODO — le changement d'état pourrait ne pas persister côté backend.

**Approche plus sûre :** Ne pas faire glisser. Dire à la place :
> "Le tableau Kanban me donne une vue visuelle de tous mes projets en un coup d'œil."

Tester avant la démo — conserver si ça persiste, supprimer si ça ne fonctionne pas.

---

## ✅ Fonctionnalités confirmées — Aucune action requise

| Fonctionnalité | Fichier | Statut |
|----------------|---------|--------|
| Cartes statistiques du tableau de bord avec flèches de tendance | `Dashboard.vue` | ✅ |
| Graphique en aire — progression mensuelle (ApexCharts) | `Dashboard.vue` | ✅ |
| Panneau Mes Tâches + membres de l'équipe | `Dashboard.vue` | ✅ |
| Édition en ligne des tâches (statut/priorité/date) via PATCH | `TacheTable.vue` + `routes/api.php` | ✅ |
| Onglets du détail de tâche (Info, Sous-tâches, Commentaires, Documents, Audit) | `TacheDetail.vue` | ✅ |
| Barre de progression pondérée des sous-tâches | `SousTacheList.vue` | ✅ |
| Commentaires seedés sur les tâches (fil Éric → Kofi → Aïcha) | `WorkspaceSeeder.php` | ✅ |
| Scénarios TacheResultat seedés (file N0 avec résultats en attente) | `WorkspaceSeeder.php` | ✅ |
| Guard du routeur bloquant `/admin/*` pour les non-super-admins | `router/index.ts` ligne 909 | ✅ |
| Page 403 affichée en cas d'accès non autorisé | `FourZeroFour.vue` | ✅ |
| Page Tâches du Workspace avec export Excel | `WorkspaceTaches.vue` | ✅ |
| Notification temps réel via Echo (`App.Models.User.{id}`) | `useLiveNotifications.js` | ✅ |
| Badge cloche + menu déroulant de notifications | `NotificationMenu.vue` | ✅ |
| Formulaire de soumission de résultat (bouton Soumettre) | `TacheResultsTab.vue` | ✅ |
| Boutons Approuver / Renvoyer la validation | `TacheResultsTab.vue` | ✅ |
| Chat d'équipe avec réponses et indicateur de saisie | `Teams/Show.vue` | ✅ |
| Abonnement Echo temps réel dans le chat | `Teams/Show.vue` ligne 1891 | ✅ |
| Page de recherche avec onglets de type + bouton Export | `Search.vue` | ✅ |
| Palette Cmd+K / Ctrl+K | `SearchBar.vue` ligne 112 | ✅ |
| Tableau de bord Évaluations (Top Performers + Alertes + filtre date) | `EvaluationDashboard.vue` | ✅ |
| Fiche Agent (8 barres de critères) | `AgentSheet.vue` | ✅ |
| Performance Équipe (barres de progression intégrées) | `PerformanceEquipe.vue` | ✅ |
| MFA / TOTP : QR code + codes de récupération avec animation stagger | `TwoFactorSettings.vue` | ✅ |
| Onglet Sécurité dans le Profil utilisateur | `UserProfile.vue` | ✅ |
| Préférences de notifications — interrupteurs par événement (3 canaux) | `NotificationPreferences.vue` | ✅ |
| Cartes de plans d'abonnement avec limites dynamiques | `Plans.vue` | ✅ |
| Modal de paiement : sélection MTN / Orange Money | `Plans.vue` | ✅ |
| Middleware CheckSubscriptionLimits bloque l'ajout de membres | `CheckSubscriptionLimits.php` | ✅ |
| Admin Dashboard : 6 cartes workspace + 4 cartes utilisateurs | `AdminDashboard.vue` | ✅ |
| Admin Workspaces : bouton Prolonger l'essai câblé | `AdminWorkspaces.vue` | ✅ |
| Admin Journaux : onglet Journal d'Audit de Validation | `AdminLogs.vue` | ✅ |
| Centre d'aide : barre de recherche + grille de catégories | `HelpIndex.vue` | ✅ |
| Formulaire article d'aide : auto-sauvegarde + bannière de restauration | `HelpArticleForm.vue` | ✅ |
| Sous-tâches seedées sur les tâches ERP | `WorkspaceSeeder.php` inline | ✅ |
| 3 workspaces, 16 utilisateurs après le seed | `WorkspaceSeeder.php` | ✅ |
| Préférences de notifications seedées par utilisateur de démo | `NotificationDemoSeeder.php` | ✅ |
| Ordre d'exécution des seeders correct | `DatabaseSeeder.php` | ✅ |

---

## Vérification rapide des données seedées

Après le seed, exécuter dans `php artisan tinker` :

```php
User::count();                                              // attendu : 16
Workspace::count();                                         // attendu : 3
Plan::pluck('name', 'slug');                                // attendu : free / starter / pro
Projet::count();                                            // attendu : 12
Tache::count();                                             // attendu : 50+
TacheResultat::where('statut', 'en_verification_n0')->count(); // attendu : 3
SousTache::count();                                         // attendu : 10+
Workspace::whereNull('plan_id')->count();                   // attendu : 0
```

---

## Checklist des serveurs avant la présentation

```bash
php artisan migrate:fresh --seed   # remise à zéro avec données réalistes
php artisan serve                  # API backend
npm run dev                        # Frontend Vite
php artisan queue:work             # Jobs asynchrones (exports, emails)
php artisan reverb:start           # WebSocket (notifications + chat temps réel)
```

---

## Configuration des onglets navigateur

| Onglet | Compte | Mot de passe | Rôle |
|--------|--------|--------------|------|
| Onglet 1 | `directeur@worktracking.com` | `password` | Principal — Directeur |
| Onglet 2 | `collaborateur@worktracking.com` | `password` | Kofi — soumet les résultats |
| Onglet 3 | `superadmin@worktracking.com` | `password` | Administration plateforme |

Se connecter aux 3 onglets **avant l'arrivée du public**.

---

## Récapitulatif

| Priorité | Problème | Action |
|----------|----------|--------|
| ✅ Corrigé | Limites d'abonnement non appliquées (plan_id NULL) | Corrigé dans WorkspaceSeeder |
| ✅ Corrigé | Script mentionnait l'interface de fréquence de résumé | Dire "Heures de silence" à la place |
| ✅ Corrigé | Script affirmait l'autocomplétion @mention | Taper @nom en texte brut |
| ⚠️ À tester | Glisser-déposer Kanban pourrait ne pas persister | Tester avant ; supprimer si cassé |
| ✅ Prêt | Toutes les autres fonctionnalités | Aucune action requise |
