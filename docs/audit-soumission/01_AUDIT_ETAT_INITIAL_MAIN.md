# Audit — État Initial de la Branche `main`

> **Objet :** Photographie complète du projet au moment où la branche `jonas` a été créée depuis `main`.
> **Date de référence :** dernier commit commun — `3642f6ec` (« feat(projets): implémenter le retrait d'un membre avec transfert des responsabilités »)
> **Établi le :** 2026-06-11
> **Auteur de l'audit :** Jonas Levis

---

## 1. Contexte et méthode

La branche `main` correspond à l'état du projet **avant toute intervention de développement avancé**. Elle contient 410 commits issus du développement initial de la base de code. La branche `jonas` a été créée depuis `main` ; elle est maintenant 297 commits en avance, sans aucun retour vers `main`.

Méthode : lecture de `git show main:…`, comparaison `git diff --stat main jonas`, analyse des fichiers de configuration, des routes, des modèles et du code source tel qu'il existait sur `main`.

---

## 2. Pile technologique en place

| Couche | Technologie | Version |
|---|---|---|
| Langage backend | PHP | ^8.1 |
| Framework backend | Laravel | ^10.10 |
| Authentification | Laravel Fortify + Sanctum | v1 / v3 |
| Permissions | Spatie Laravel Permission | — |
| Logs d'activité | Spatie Laravel ActivityLog | — |
| Images | Intervention Image | — |
| HTTP client | Guzzle | — |
| Framework frontend | Vue.js 3 | — |
| Routeur frontend | Vue Router | — |
| État global | Pinia | — |
| UI | AdminLTE 3 + TailwindCSS | — |
| Build | Vite + @vitejs/plugin-vue | — |
| Internationalisation | vue-i18n | — |
| Toasts | vue-toastification | — |
| Calendrier | FullCalendar/Vue3 | — |
| Graphiques | ApexCharts + Chart.js (les deux) | — |
| Glisser-déposer | vuedraggable | — |
| Sélecteur de date | flatpickr / @vuepic/vue-datepicker | — |

**Dépendances backend absentes sur `main`** (ajoutées sur `jonas`) :
- Laravel Reverb (WebSockets temps réel)
- Laravel Horizon (surveillance des files)
- Laravel Pulse (métriques serveur)
- Laravel Scout + Typesense (recherche full-text)
- Laravel Socialite (OAuth social)
- barryvdh/laravel-dompdf (export PDF)
- maatwebsite/excel + phpoffice/phpspreadsheet (export Excel)
- phpoffice/phpword, smalot/pdfparser (extraction texte)
- mews/purifier (assainissement HTML)
- minishlink/web-push (notifications push navigateur)
- opcodesio/log-viewer (interface de lecture des logs)

---

## 3. Modèle de données

### 3.1 Tables présentes sur `main` (68 migrations)

La base comprend les entités fondamentales du cahier des charges :

| Domaine | Tables principales |
|---|---|
| Authentification | `users`, `password_reset_tokens`, `personal_access_tokens`, `failed_jobs` |
| Organisation | `workspaces`, `workspace_user` (pivot) |
| Hiérarchie travaux | `projets`, `projet_user` (pivot), `activites`, `activite_user`, `taches`, `tache_user` |
| Sous-tâches | `sous_taches`, `sous_tache_user` |
| Résultats / Circuit | `tache_resultats`, `validation_audit_logs` |
| Scores | `evaluation_scores` |
| Documents | `documents`, `document_permissions`, `document_versions`, `document_views` |
| Communications | `teams`, `team_user`, `team_messages`, `team_message_attachments`, `announcements`, `events` |
| Étiquettes | `labels`, `tache_label` |
| Commentaires | `comments`, `comment_reactions` |
| Notifications | `notifications`, `notification_preferences`, `push_subscriptions` |
| Invitations | `workspace_invitations` |
| Support | `support_tickets`, `support_replies` |
| Accès temporaire | `temporary_access` |

**Tables absentes sur `main`** (ajoutées sur `jonas`) :
`help_categories`, `help_articles`, `plans`, `payments`, `job_batches`, `pulse_*`, `horizon_*`

---

## 4. Architecture backend

### 4.1 Contrôleurs API présents

| Contrôleur | Couverture fonctionnelle |
|---|---|
| `AuthController` | Inscription, connexion, déconnexion, /me |
| `WorkspaceController` | CRUD espace de travail, membres, invitations |
| `ProjetController` | CRUD projets, membres |
| `ActiviteController` | CRUD activités, membres |
| `TacheController` | CRUD tâches, affectation, listes |
| `TacheResultatController` | Soumission, circuit N0/N1/N2 |
| `EvaluationController` | Score, fiche agent, tableau en attente |
| `DocumentController` | Upload, versions, permissions, partage |
| `DashboardController` | Statistiques, Kanban, progression mensuelle |
| `UserController` | Profil, avatar, préférences |
| `LabelController` | CRUD étiquettes |
| `CommentController` | CRUD commentaires + réactions |
| `TeamController` | CRUD équipes, membres, messages |
| `NotificationController` | Liste, marquer lu, préférences |

**Contrôleurs absents sur `main`** (ajoutés sur `jonas`) :
`AdminController`, `SubscriptionController`, `PaymentController` (MTN/Orange), `SearchController`, `HelpController`, `AdminHelpController`, `AdminPlanController`, `ActivityController`, `SousTacheController`, `AuthMfaController`, `SocialAuthController`, `SupportController`, `WebPushController`, `AdminHorizonController`

### 4.2 Routes API (routes/api.php)

| | Nb de lignes |
|---|---|
| `main` | 815 lignes |
| `jonas` | 984 lignes |

### 4.3 Services métier présents

`PermissionService`, `TacheService`, `TacheResultatService`, `EvaluationScoreService`, `DocumentService`, `NotificationService` (partiel), `SubscriptionService` (initial, sans abonnements payants)

---

## 5. Sécurité et authentification

| Mécanisme | État sur `main` |
|---|---|
| Authentification par token Sanctum | ✅ En place |
| Authentification à deux facteurs (MFA) | ❌ Absent |
| OAuth Google (Socialite) | ❌ Absent |
| Throttle fin sur login/register | ⚠️ Throttle générique `throttle:60,1` seulement |
| CORS | ⚠️ Wildcard `*` + credentials (non sécurisé) |
| Protection des webhooks | ❌ Absent |
| Chiffrement des données sensibles | Non documenté |

---

## 6. Frontend Vue.js

### 6.1 Pages présentes sur `main`

| Section | Pages / composants principaux |
|---|---|
| Auth | Signin.vue, Register.vue, ForgotPassword.vue |
| Dashboard | Dashboard.vue (statistiques, Kanban) |
| Projets | Projets.vue, ProjetDetail.vue, ProjetDocuments.vue |
| Activités | ActiviteDetail.vue |
| Tâches | Taches.vue, TacheDetail.vue, TacheDetailModal.vue |
| Documents | Documents.vue, WorkspaceDocuments.vue |
| Évaluations | AgentSheet.vue, EvaluationDashboard.vue, PendingValidations.vue |
| Admin | AdminDashboard.vue, AdminWorkspaces.vue, AdminUsers.vue |
| Abonnement | Plans.vue (sans paiement réel) |
| Profil / Paramètres | UserProfile.vue |
| Équipes | Teams/Show.vue |
| Recherche | Search.vue (sans moteur full-text) |

**Pages absentes sur `main`** (ajoutées sur `jonas`) :
Pages Help Center (lecteur + auteur), WorkspaceUsers.vue, page MFA, page Social Auth, pages Paiement/MTN/Orange, ManagePlans.vue, WorkspaceTaches.vue, WeeklyTasksReport.vue

### 6.2 Internationalisation

Fichiers de traduction présents côté frontend : `fr.json`, `en.json` (partiels).
Côté backend : `lang/fr/*.php`, `lang/en/*.php` pour les modules principaux.

---

## 7. Tests

| Suite | État sur `main` |
|---|---|
| Tests PHPUnit Feature | Présents, ~274 cas (estimé d'après le tracker) |
| Tests PHPUnit Unit | Présents (PolicyTest, PermissionServiceTest) |
| Laravel Dusk | ❌ Non configuré |
| Couverture Larastan | ⚠️ Non systématique (154 erreurs connues sur la branche de départ) |

---

## 8. Performance

| Indicateur | État estimé sur `main` |
|---|---|
| Requêtes DB — endpoint Dashboard | ~74 requêtes (N+1 non corrigés) |
| Requêtes DB — endpoint Admin stats | ~44 requêtes |
| Requêtes DB — Admin workspaces | ~41 requêtes |
| Taille bundle JS (app.js) | ~1 MB (non découpé) |
| Mise en cache API | Absente (pas d'AdaptiveCache) |

---

## 9. Infrastructure / déploiement

| Élément | État sur `main` |
|---|---|
| File de jobs | `QUEUE_CONNECTION=sync` (pas de vrai worker) |
| Broadcasting temps réel | Aucun (pas de Reverb) |
| Cache | File/array (pas de Redis) |
| Sessions | File |
| Script de déploiement | Absent |
| Redis | Non configuré |

---

## 10. Documentation projet

| Document | Présence sur `main` |
|---|---|
| `CLAUDE.md` (règles d'équipe) | Partiel — règles initiales |
| `docs/IMPLEMENTATION_PLAN.md` | Présent (v1) |
| `docs/PROGRESSION.md` | Présent (v1, tâches 0–16) |
| `docs/SESSION_STATE.md` | Présent |
| `docs/PERMISSIONS_MATRIX.md` | Présent (v1) |
| `docs/WORKING_GUIDELINES.md` | Présent (guides 0–16) |
| Documentation technique française | Absente |
| Guides de déploiement | Absents |

---

## 11. Synthèse des manques identifiés sur `main`

| # | Manque | Criticité |
|---|---|---|
| 1 | MFA / 2FA | Haute |
| 2 | Authentification sociale (Google) | Moyenne |
| 3 | Notifications temps réel (Reverb) | Haute |
| 4 | Notifications Web Push navigateur | Moyenne |
| 5 | Moteur de recherche full-text (Typesense/Scout) | Haute |
| 6 | Help Center bilingue (lecteur + auteur) | Moyenne |
| 7 | Gestion des abonnements / plans tarifaires | Haute |
| 8 | Passerelle de paiement (MTN MoMo / Orange Money) | Haute |
| 9 | Durcissement sécurité (CORS, throttles, CVE) | Haute |
| 10 | Optimisation performances (N+1, cache adaptatif, bundle JS) | Haute |
| 11 | Export PDF (fiche agent) et Excel (tâches) | Moyenne |
| 12 | Gestion complète des logs admin (interface native) | Moyenne |
| 13 | Chat temps réel par équipe (@mentions, typing, non-lus) | Haute |
| 14 | Statistiques dashboard précises (période-sur-période) | Moyenne |
| 15 | Page de profil complète (sessions, préférences, activité) | Basse |
| 16 | Dusk (tests navigateur) | Haute |
| 17 | Larastan niveau 5 (0 erreur) | Haute |
| 18 | Script de déploiement production | Moyenne |
| 19 | Documentation technique française | Basse |
| 20 | Redis pour cache/sessions/files/rate-limiting | Haute |
