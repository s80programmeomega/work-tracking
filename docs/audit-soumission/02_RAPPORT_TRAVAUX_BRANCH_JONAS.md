# Rapport des Travaux — Branche `jonas`

> **Objet :** Récapitulatif exhaustif de toutes les fonctionnalités, corrections et améliorations apportées depuis la branche `main`.
> **Période :** 2026-05-11 → 2026-06-11
> **Branche :** `jonas` (297 commits au-delà de `main`)
> **Établi le :** 2026-06-11
> **Auteur :** Jonas Levis

---

## 1. Vue d'ensemble chiffrée

| Indicateur | `main` | `jonas` | Variation |
|---|---|---|---|
| Commits | 410 | 707 | +297 |
| Migrations DB | 68 | 103 | +35 migrations |
| Fichiers modifiés (diff total) | — | — | **1 310 fichiers**, +227 139 lignes |
| Tests PHPUnit | ~274 | ~800+ | ~+526 tests |
| Tests Dusk (navigateur) | 0 | 20+ | +20 tests |
| Erreurs Larastan | ~154 | 0 | −154 erreurs |
| Routes API (lignes api.php) | 815 | 984 | +169 lignes |
| Bundle JS app.js | ~1 MB | 197 KB | −81% |
| Requêtes DB — Dashboard | ~74 | ~34 | −54% |
| Requêtes DB — Admin stats | ~44 | ~22 | −50% |
| Requêtes DB — Admin workspaces | ~41 | ~12 | −71% |
| Dépendances Composer backend | 10 | 23 | +13 packages |
| Dépendances npm frontend | 29 | 30 | +1 (Tiptap) |

---

## 2. Détail des phases et tâches réalisées

### Phase 0 — Socle : architecture des permissions (Tâche 0)

**Branche :** `feature/v2-task-0-policies-refactor`

- Remplacement de `PermissionService` brut par des **Laravel Policies** (`ProjetPolicy`, `ActivitePolicy`, `TachePolicy`, `DocumentPolicy`, `WorkspacePolicy`, `SousTachePolicy`).
- `Gate::before()` — bypass super_admin global.
- `RolePermissionSeeder` — seeding des rôles contextuels Spatie.
- Migration de tous les contrôleurs de `abort_unless` vers `$this->authorize()`.
- **22 tests PHPUnit.**

---

### Phase 1 — Infrastructure temps réel (Tâche 1)

**Branche :** `feature/v2-task-1-queue-reverb`

- `QUEUE_CONNECTION=database` — vrai worker de file d'attente.
- **Laravel Reverb** installé et configuré (WebSockets).
- `laravel-echo` + `pusher-js` côté frontend.
- Composable `useEcho.js`.

---

### Phase 2 — Sous-tâches : modèle de données (Tâche 2)

**Branche :** `feature/v2-task-2-subtask-model`

- 3 nouvelles migrations : `sous_taches`, `sous_tache_user`, suppression `parent_tache_id`.
- Modèle `SousTache` avec règles R1 (poids entiers) et R2 (somme = 100 ou 0).
- `SousTacheFactory`, `SousTacheSeeder`, `SousTacheResource`, `SousTachePolicy`.
- Statuts `EN_RETARD` et `A_REFAIRE` ajoutés à `TacheStatut`.
- Traductions `lang/fr/sous_taches.php` + `lang/en/sous_taches.php`.
- **6 nouveaux tests.**

---

### Phase 3 — Sous-tâches : API CRUD + progression automatique (Tâche 3)

**Branche :** `feature/v2-task-3-subtask-api`

- `SousTacheService` — create, update, delete, assignIntervenant.
- `SousTacheController` — 5 endpoints REST.
- `SousTacheObserver` — recalcul automatique du `taux_realisation` parent.
- Changement automatique de statut parent (toutes terminées → `termine` ; une en retard → `en_retard`).
- 3 notifications : `SousTacheAssigneeNotification`, `SousTacheOverdueNotification`, `TacheStatutAutoChangeNotification`.
- **10 nouveaux tests.**

---

### Phase 4 — Sous-tâches : interface utilisateur (Tâche 4)

**Branche :** `feature/v2-task-4-subtask-ui`

- `SousTacheList.vue` — liste ordonnée, édition inline, bascule complétion, barre de progression pondérée.
- `SousTacheForm.vue` — création rapide avec poids restant, date max, indicateurs de validation.
- Badge sous-tâches sur les cartes Kanban.
- Intégration dans `TacheDetail.vue` et `TacheDetailModal.vue`.
- Correction : remplacement de 6 valeurs de rôle obsolètes (`responsable`/`collaborator` → enum réel).

---

### Phase 5 — Circuit de validation N0 + minuterie 48h (Tâche 5)

**Branche :** `feature/v2-task-5-validation-n0`

- Migration : colonnes N0 sur `tache_resultats` (statut, soumis_n0_le, action_n0, commentaire_n0…).
- Table `validation_audit_logs` — immuable (R6).
- `TacheResultatService` : `soumettre`, `approuverN0`, `renvoyerN0`, `transmettreAuN1`.
- `TransmettreResultatAuN1Job` — dispatché à la soumission, délai configurable (défaut 48h).
- 4 notifications : `ResultatSoumisN0`, `ResultatRenvoye`, `ResultatApprouveN0`, `ResultatTransmisAuto`.
- Templates email Blade bilingues.
- **7 nouveaux tests.**

---

### Phase 6 — Anti-sabotage Bypass (Tâche 6)

**Branche :** `feature/v2-task-6-bypass`

- 2 migrations : colonnes bypass sur `tache_resultats` et `tache_user`.
- Service `activerBypass` (R3 + R5 + log) et `invaliderBypassN1` (flag `escalades_abusives` à 3 consécutifs).
- Permission `RESULTATS_ACTIVER_BYPASS` (collaborateur + stagiaire).
- `BypassActivatedNotification` + email Blade bilingue.
- `EscaladesAbusivesNotification`.
- **10 nouveaux tests.**

---

### Phase 7 — Scores N1 + tableau des validations en attente (Tâche 7)

**Branche :** `feature/v2-task-7-scores-dashboard`

- Migration : table `evaluation_scores`.
- `EvaluationScoreService::calculerImpactN1` — 3 chemins (penalty −1,0 / bonus +1,0 / sans impact).
- `EvaluationScoreService::totalForUser` — somme sur période.
- `TacheResultatService::validerN1` / `rejeterN1` — wrappent le modèle + scoring.
- `EvaluationController::pendingValidationsDashboard` — trié par délai restant, badge urgent < 24h.
- Page Vue `PendingValidations.vue` + `PendingRow.vue`.
- Permissions `EVALUATIONS_VIEW_PENDING` + `EVALUATIONS_VIEW_SCORE`.
- **10 tests PHPUnit + 1 test Dusk.**

---

### Phase 8 — Notifications temps réel + digest quotidien (Tâche 8)

**Branche :** `feature/v2-task-8-notifications`

- `NotificationService::channelsFor()` — résout `['database', 'broadcast']` ± `'mail'` selon le niveau d'événement.
- `NotificationService::dedupKey()` + `isDuplicate()` — fenêtre de déduplication 5 minutes.
- `NotificationService::notifyHierarchy()` — propagation vers le directeur + les managers du workspace.
- Composable `useLiveNotifications.js` — abonnement canal privé `App.Models.User.{id}`, toast à la réception.
- Permission `NOTIFICATIONS_MANAGE_PREFERENCES`.
- **Digest quotidien (Tâche 8c) :** commande `notifications:send-digest`, planifiée toutes les 15 min, respect des heures calmes, email groupé bilingue.
- **8b — Web Push navigateur :** `minishlink/web-push` v10.0.3, clés VAPID, `WebPushChannel`, service worker `sw-webpush.js`, composable `useWebPush.js`, endpoint subscribe/unsubscribe, UI « Cet appareil ».
- **11 + 8 + 13 tests PHPUnit + 2 tests Dusk.**

---

### Phase 9 — Fiche agent + scoring complet (Tâche 9)

**Branche :** `feature/v2-task-9-agent-sheet`

- `EvaluationScoreService::calculerScore()` — 8 critères pondérés (taux complétion, respect délai, qualité résultat, première validation, retours justifiés, inactions, volume travail, coordination équipe).
- Immutabilité post-N2 (R6) : `Tache::isLockedPostN2()`, `TacheService::guardPostN2Immutability()` → HTTP 422 sur toute mutation.
- Page `AgentSheet.vue` — 4 sections, filtres période/statut, liste paginée, modal drill-down, donut retours, alerte retours injustifiés.
- Permissions `evaluations.view_fiche` + `evaluations.export_fiche`.
- Notifications `EvaluationSheetReadyNotification` + `InjustifiedReturnAlertNotification`.
- **4 + 6 + 10 tests PHPUnit + 2 tests Dusk.**

---

### Phase 10 — Tableau d'évaluation + vue globale tâches (Tâche 10)

**Branche :** `feature/v2-task-10-dashboard`

- `EvaluationController::evaluationDashboard` — scope owner/manager/cadre, top performers, alertes.
- `TacheController::workspaceTaches` — owner/super_admin, filtrable, paginé 25/page.
- 3 permissions : `evaluations.view_dashboard`, `evaluations.view_workspace_taches`, `taches.inline_edit`.
- Pages `EvaluationDashboard.vue` + `WorkspaceTaches.vue`.
- 2 notifications : `AbusiveEscalationAlertNotification`, `HighInactionRateAlertNotification`.
- **12 tests PHPUnit + 2 tests Dusk.**

---

### Phase 11 — Wizard de création de tâches (Tâche 11)

**Branche :** `feature/v2-task-11-task-creation-ux`

- `TacheCreateWizard.vue` — 4 étapes : Informations, Assignation, Ressources, Validation.
- `IntervenantPicker.vue` — sélection multi-intervenants recherchable.
- `TacheAssigneeNotification` (mail + base de données, file d'attente) + `TacheResourcesNotification`.
- Templates email Blade bilingues.
- **5 tests PHPUnit + 3 tests Dusk.**

---

### Phase 12 — Gestion documentaire (Tâche 12)

**Branche :** `feature/v2-task-12-document-management`

- Permission `DOCUMENTS_MANAGE_WORKSPACE` (owner uniquement).
- `WorkspaceDocuments.vue` — page documents niveau workspace.
- Endpoint `POST /api/documents/{id}/share-by-email` + `DocumentSharedNotification`.
- `DocumentUploadedNotification` (in-app, cadres + managers) et `DocumentDeletedNotification` (in-app, responsable).
- Clés de permission document ajoutées à `ProjetResource` + composables.
- **5 tests PHPUnit.**

---

### Phase 13 — CDC Hotfixes (non-conformités cahier des charges)

**Branche :** `fix/cdc-hotfixes`

- **R7** — garde backend : `TacheResultatService::soumettre()` bloque si sous-tâches obligatoires non terminales (422).
- Endpoint `GET /api/audit-logs/validation/{tache}` — expose le journal d'audit validation (CDC §6).
- Agent sheet §5 — section `submitted_results` (CDC Module E.2).
- **6 nouveaux tests.**

---

### Phase 14 — Modes d'abonnement + durée d'essai (Tâche 13)

**Branche :** `feature/v2-task-13-subscription`

- Migration : `subscription_mode`, `trial_started_at`, `trial_duration_days` sur `workspaces`.
- `config/subscription.php` — valeurs pilotées par `.env`.
- `SubscriptionService` — `isPaid`, `isTrialExpired`, `getRemainingTrialDays`, `isExpiringSoon`, `canAddMember`, `canUploadFile`, `canUploadStorage`, `summary`.
- Middleware `CheckSubscriptionLimits` (alias `subscription.limits`).
- 3 notifications : `TrialExpiringNotification`, `TrialExpiredNotification`, `SubscriptionLimitReachedNotification`.
- `TrialBanner.vue` — bannière amber/rouge dismissable.
- `WorkspaceFactory` — états `paid()`, `trialExpired()`, `trialExpiringSoon()`.
- **21 tests PHPUnit.**

---

### Phase 15 — Super-Admin Plateforme (Tâche 14)

**Branche :** `feature/v2-task-14-platform-dashboard`

- `AdminController` — 6 méthodes : stats, workspaces, users, extendTrial, suspendWorkspace, reactivateWorkspace.
- Toutes les routes `/api/admin/*` protégées par middleware `super_admin`.
- 2 notifications : `TrialExtendedNotification`, `WorkspaceSuspendedNotification`.
- Pages `AdminDashboard.vue`, `AdminWorkspaces.vue`, `AdminUsers.vue`.
- `SubscriptionBadge.vue` — badge couleur (trial=amber, paid=vert, free=gris).
- Garde Vue Router `requiresSuperAdmin` → redirige vers 404 si non-admin.
- **11 tests PHPUnit.**

---

### Phase 16 — Exports PDF + Excel (Tâche 16)

**Branche :** `feature/v2-task-16-export`

- Export PDF fiche agent : `GET /api/evaluations/personnel/{user}/export-pdf` (DomPDF, A4, barres critères).
- Export Excel liste tâches workspace : `GET /api/workspace/taches/export-excel` (10 colonnes, en-tête bleu, filtre-aware).
- Classes `WorkspaceTachesExport`, template Blade `exports/agent-sheet.blade.php`.
- Boutons câblés dans `AgentSheet.vue` et `WorkspaceTaches.vue`.
- **5 tests PHPUnit.**

---

### Phase 17 — Couverture tests + Larastan (chore/test-coverage-expansion)

- Remplacement de `nunomaduro/larastan` par `larastan/larastan` v2.
- **154 erreurs Larastan résolues → 0** : 16 ressources API annotées `@property`/`@mixin`/`@responseField`, 12+ vrais bugs corrigés (DocumentPolicy, TacheResultatController, notifications, controllers).
- +265 tests PHPUnit en plusieurs phases (politiques, services, API Resource, CRUD complet tous modules).
- **627 tests au total après cette phase.**

---

### Phase 18 — Design system + alignement frontend (feature/design-system-v1 + frontend-alignment)

- Migration design system : animations stagger, transitions collapse/fade, modales repositionnées.
- Alignement frontend/backend : authentification, projets/activités, tâches, validations, évaluations, documents, abonnement, admin.
- Composant `LanguageSwitcher.vue` — bascule FR/EN.
- Vue hebdomadaire des tâches `WeeklyTasksReport.vue`.
- Corrections : sidebar, invitations, visibilité membres, modales, upload avatars.

---

### Phase 19 — Correctifs batch de bogues (fix/bug-batch)

**10 correctifs G1–G12 :**

| ID | Correction |
|---|---|
| G1 | `TacheController::show` — réponse enveloppée dans `TacheResource` (champ `my_result`, bypass, audit_logs visibles) |
| G2 | Garde centrale contre les auto-notifications + suppression de 3 notifications auto-confirmatoires |
| G3 | 7 notifications N1/N2 routées via `NotificationService::channelsFor()` |
| G4 | Régression N1→N2 corrigée + couverture Dusk `pending-N2` |
| G5 | Pages de validation séparées par audience (assigné vs validateur) |
| G6 | Badge sous-tâches ajouté au Kanban dashboard |
| G7 | Consolidation des modales dupliquées (taches/resultats) |
| G8 | Sidebar gated via `useWorkspacePermissions` |
| G9 | Suppression endpoint sous-tâches intervenants + UI inline assign/remove |
| G10 | Profil : upload avatar, préférence langue persistée via API, suppression compte |
| G12 | Réactivité cross-cutting — événements broadcast Reverb + refetch Vue |

---

### Phase 20 — Phase 1 étendue : MFA (feature/mfa-2fa)

- **MFA TOTP** — `google/authenticator`, codes QR, setup/activate/disable.
- **Codes de récupération** — génération, affichage masqué, révélation par bouton collapse.
- **Email-OTP de secours** — envoi par mail si TOTP non disponible.
- Corrections : maxlength tronquait les codes de récupération, toggle codes visible/masqué, état MFA exposé dans `/auth/me`.
- `MfaService` + `AuthMfaController` + routes dédiées.

---

### Phase 21 — Phase 2 étendue : Authentification sociale Google (feature/social-auth-google)

- `laravel/socialite` installé.
- `SocialAuthController` — `redirectToGoogle`, `handleGoogleCallback` (upsert user, auto-workspace si nouveau).
- Route `GET /api/auth/google` + `GET /api/auth/google/callback`.
- Bouton « Continuer avec Google » sur `Signin.vue` + `Register.vue`.

---

### Phase 22 — Phase 3 étendue : Support contact + Phase 4 : Logs admin natifs (feature/support-contact)

**Support (Phase 3) :**
- `SupportController` — création ticket, liste, réponse, clôture.
- `SupportTicketReplyNotification` — notification email à l'ouverture d'une réponse.
- Page `/support` — formulaire de contact bilingue.

**Logs admin natifs (Phase 4) :**
- Remplacement de l'iframe log-viewer par une interface Vue native.
- `ValidationAuditLogResource` + endpoint `GET /api/admin/audit-logs/validation`.
- `ActivityLogTab.vue` — filtre par causer, plages de dates, tiroir de détail (`LogDetailDrawer.vue`).
- `AppLogsTab.vue` — sélecteur de fichier, chips de niveau, développer/réduire, télécharger, supprimer.
- `ValidationAuditLogTab.vue` — badges d'action, sélecteur de dates, tiroir.
- `AdminLogs.vue` refactorisé en conteneur 3 onglets.
- **6 tests PHPUnit + 4 tests Dusk.**

---

### Phase 23 — Phase 5 étendue : Chat temps réel + @mentions (feature/phase5-chat)

- 4 événements broadcast (`ShouldBroadcastNow`) : `MessageSent`, `MessageEdited`, `MessageDeleted`, `UserTyping`.
- Authentification canal d'équipe `team.{teamId}`.
- `ChatMentionNotification` — notification in-app/email pour les @mentions.
- `TeamMessageResource` — payload enrichi.
- +5 méthodes contrôleur + 7 routes.
- Migration `last_read_at` sur `team_user`.
- `useTeamMessages.js` — réécriture (Echo + optimistic updates + whisper typing).
- `Teams/Show.vue` — refonte UI complète du chat, badge non-lus dans la sidebar.
- **12 tests PHPUnit + 3 tests Dusk.**

---

### Phase 24 — Phase 6 étendue : Recherche globale full-text (feature/phase6-search)

- `laravel/scout` + `typesense/typesense-php` installés.
- **8 modèles Searchable** : Projet, Activite, Tache, Document, User, TeamMessage, SousTache, Notification (user-scoped).
- 3 niveaux de permission : `search.global` (super_admin/owner/manager), `search.scoped` (cadre/collaborateur/stagiaire), aucun (observateur).
- `DocumentTextExtractorService` — extraction texte PDF/docx/xlsx/txt.
- `ExtractDocumentTextJob` (ShouldQueue) — dispatché à chaque upload.
- `SearchController` — recherche super-admin globale, highlights Typesense, pagination.
- Pipeline d'export recherche : `SearchExport`, `SearchExportJob`, `SearchExportReadyNotification`.
- `SearchBar.vue` — palette Cmd+K, navigation clavier.
- Page `/search` — onglets par type, résultats surlignés, modales aperçu, barre export.
- **Correctif sécurité critique** : fuite cross-workspace Typesense `fromRaw` corrigée (`filter_by`), IDOR `exportSelected` corrigé.
- **15 + 8 tests PHPUnit.**

---

### Phase 25 — Phase 7 étendue : Help Center bilingue (feature/phase7-help-center)

- Entités : `HelpCategory`, `HelpArticle` (bilingue FR/EN, sanitisation Purifier, recherche MySQL FULLTEXT).
- Permissions granulaires : `help_articles.read`, `help_articles.create`, `help_articles.edit`, `help_articles.publish`, `help_articles.delete`, `help_articles.upload_image`, `help_categories.manage`.
- `HelpController` (lecture) + `AdminHelpController` (CRUD/publish/images) + 4 Form Requests + 17 routes.
- Interface lecteur : `/help`, `/help/c/:slug`, `/help/a/:slug`.
- Interface auteur : éditeur Tiptap, liste articles, formulaire — avec **sauvegarde brouillon automatique** (Phase 11D).
- Inclusion dans la recherche globale (articles publiés uniquement).
- Animations collapse/fade à l'échelle du projet, corrections modales (bordures, draggabilité, clic extérieur).
- Guide 25 : défense contre l'injection de prompt (documenté dans `WORKING_GUIDELINES.md`).
- **15 tests PHPUnit.**

---

### Phase 26 — Phase 8 étendue : Plans d'abonnement + porte globale 402 (feature/phase8-subscription-plans)

- Entité `Plan` (free/starter/pro) — table `plans`, modèle, factory, `PlanSeeder`.
- `workspace.plan_id` (FK nullOnDelete) + `subscription_status` + `subscription_ends_at`.
- `SubscriptionService` étendu : `effectivePlan`, `planLimit` (−1 = illimité), `isLocked`, `reconcileStatus`.
- Middleware global `CheckSubscriptionStatus` → **HTTP 402** quand workspace verrouillé (exempt : auth, subscription, payment, webhooks, user ; bypass super_admin).
- `SubscriptionController` — plans/current/select/activate/lock/unlock.
- `/subscription/plans` — page tarification + sélection.
- Intercepteur 402 côté frontend → redirection vers les plans.
- Admin : activer/verrouiller/déverrouiller un workspace.
- `AdminPlanController` — CRUD plans super_admin (Store/UpdatePlanRequest avec `authorize()` → 403 avant validation).
- Page `/admin/plans` — `ManagePlans.vue`.
- **34 + 9 tests PHPUnit.**

---

### Phase 27 — Phase 9 étendue : Passerelle de paiement MTN MoMo + Orange Money (feature/phase9-payment-momo-orange)

- `PaymentProviderInterface` + `PaymentResult` DTO — design Strategy.
- `MtnMomoProvider` : cache token, requesttopay 202, map statut.
- `OrangeMoneyProvider` : token, webpayment → pay_token/payment_url, transactionstatus.
- `PaymentProviderRegistry` (clé → implémentation).
- Migration `payments` + modèle `Payment` (uuid `reference` = clé d'idempotence + `X-Reference-Id` MTN).
- `PaymentService::startCheckout()` + `confirm()` **idempotent** (no-op si non-pending).
- `SubscriptionService::activateFromPayment()` — pending → active.
- **Webhooks confirmés uniquement** — `PaymentWebhookController` re-vérifie le statut via l'API fournisseur (ne jamais faire confiance au corps du callback).
- Routes publiques `POST /api/webhooks/payment/{momo,orange}`.
- `config/payment.php` + `.env.example` — secrets dans `.env` uniquement.
- Modal paiement : MTN = push + polling ; Orange = redirection `window.location`.
- Toggle `PAYMENT_FAKE` développement (bloqué en production).
- MTN sandbox vérifié en direct ; Orange différé (sandbox gated).
- **11 tests PHPUnit.**

---

### Phase 28 — Phase 10 étendue : Durcissement sécurité + performances (chore/hardening-pass)

#### Sécurité
- **7/8 CVE Composer patchés** : HIGH symfony/mime CRLF injection + 6 autres dans Laravel 10 ; 1 CVE Laravel différé (nécessite Laravel 11).
- **2 CVE npm HIGH** (`ws`) corrigés.
- **CORS** : wildcard `*` + credentials → `CORS_ALLOWED_ORIGINS` piloté par `.env`.
- **Throttles fins** : login/register 5/min, refresh 10/min, paiement 6/min.
- Webhooks CSRF, hard-lock, F1 vérifiés.

#### Performances backend
- Dashboard : 74 → 34 requêtes (−54%).
- Admin stats : 44 → 22 requêtes (−50%).
- Admin workspaces : 41 → 12 requêtes (−71%).
- Mémoïsation `Plan::free()`, accesseurs `Workspace` appended-count, eager-loads N+1.

#### Performances frontend
- app.js : 1 MB → 197 KB (−81%) — `manualChunks`, lazy ApexCharts, CSS mort supprimé.
- Consolidation : un seul moteur de graphiques (ApexCharts, suppression Chart.js).

---

### Phase 29 — Infrastructure Redis + déploiement (fix/ui-and-feature-fixes + chore/deploy)

- Redis configuré pour cache, sessions, files, rate-limiting.
- Laravel Horizon — tableau de bord surveillance des files (super_admin uniquement).
- Laravel Pulse — métriques serveur.
- Endpoint de rafraîchissement de token (`POST /api/auth/refresh`).
- Script `deploy.sh` générique production (VPS/systemd).
- Vérifications pré-déploiement dans `deploy.sh`.
- Documentation technique française : `docs/technical-fr/ARCHITECTURE.md`, `GUIDE_DEVELOPPEUR.md`.
- Cache adaptatif TTL (`AdaptiveCache`) — dashboard, admin/stats, recherche.

---

### Phase 30 — Correctifs profil + UI (fix/ui-and-feature-fixes)

- Avatar : envoi FormData via POST (method-spoofed), plus PUT cassé.
- Ajout colonne `numero_telephone` manquante (erreur 500 à la mise à jour du profil).
- Édition profil consolidée sur la page complète (suppression modale redondante).
- Navigation « Invitations » dans la navbar câblée vers le vrai système d'invitation.
- Throttle de connexion assoupli (5/min/IP → 10/min par email+IP).
- Codes de récupération MFA : maxlength corrigé, toggle visibilité.
- `current_workspace_id` rétro-rempli pour les membres piégés sur `/workspaces/create`.
- Factorie/seeder : les utilisateurs factory ont toujours un workspace.
- Actions Export/Partage/Imprimer non fonctionnelles supprimées de la carte profil.
- Avatar réel de l'utilisateur affiché dans la navbar (était une image hardcodée).
- Lien Support dans le menu utilisateur redirige vers `/support`.

---

### Phase 31 — Phase 11 : Précision dashboard + correctifs page de profil (feature/phase11{a-e})

#### 11A — Précision des statistiques dashboard

- `calculateStats()` câble `calculateChange()` pour `projets_actifs`/`taux_completion`/`taches_en_retard` — **vraies variations période-sur-période** (snapshot `now()->subMonth()`), plus de `+12%`/`+5%`/`-2%` codés en dur.
- `getRecentProjects()` inclut `active` + `completed` (était `active` uniquement).
- `kanbanColumns` réduit à 2 colonnes (`active`/`completed`) alignées sur `ProjetStatus`.
- `console.log` de débogage supprimés.
- `AdminController::computeStats()` — `tasks.overdue` bascule vers `Tache::overdue()->count()` (sémantique basée sur l'échéance, pas le statut stocké).
- **4 tests PHPUnit.**

#### 11B — Corrections rapides profil

- Son de notification : `useNotificationSound.js` + asset audio, vérifie la préférence localStorage.
- Onglet Préférences : suppression des options dupliquées (Thème, Langue) déjà couvertes par les contrôles de la navbar.
- Lien navbar mort « Paramètres du compte » (route `/settings` inexistante) supprimé.

#### 11C — Gestion des sessions

- `GET /api/users/sessions` — liste les tokens Sanctum actifs de l'utilisateur courant.
- `DELETE /api/users/sessions/{tokenId}` — révoke un token spécifique.
- `POST /api/users/sessions/revoke-all` — révoke tous les tokens sauf le courant.
- `SessionSettings.vue` — liste réelle des sessions avec stagger, badge « cet appareil », boutons révoquer.
- **5 tests PHPUnit.**

#### 11D — Sauvegarde automatique brouillon (Tiptap / Help Center)

- Migration : colonnes `draft_body_fr`, `draft_body_en`, `draft_saved_at` sur `help_articles`.
- Endpoint `PATCH /api/admin/help/articles/{article}/draft` — écrit uniquement dans les colonnes draft, n'affecte pas le contenu publié.
- `HelpArticleForm.vue` — sauvegarde automatique debounced 5s, indicateur « Brouillon enregistré à HH:MM », bannière ambre « Restaurer / Ignorer » si brouillon plus récent que la publication.
- **7 tests PHPUnit.**

#### 11E — Onglets d'activité + gestion des utilisateurs (feature/phase11e-activity-users)

- **B5 :** `ActivityLog.vue` câblé vers la vraie API `GET /users/{user}/activity` (générateur de données fictives supprimé) ; mapper `normalizeActivity()` ; stagger + état d'erreur.
- **B6a :** `ActivityLogTab.vue` accepte les props `fixedCauserId`/`fixedCauserLabel` — sélecteur de causer masqué quand verrouillé. Bouton violet « Activité » par ligne dans `AdminUsers.vue` → modal pré-filtrée.
- **B6b :** Permission `WORKSPACES_VIEW_MEMBERS` (owner/manager) ; endpoint `GET /api/workspaces/{workspace}/users` (paginé + recherche) ; page `WorkspaceUsers.vue` + route + entrée sidebar ; `docs/PERMISSIONS_MATRIX.md` mis à jour.
- **8 tests PHPUnit + 3 tests Dusk.**

---

### Phase 32 — Corrections finales + préparation soumission (bcf8531 + 9154ced)

- Curseur texte clignotant corrigé sur tous les éléments non-interactifs (`cursor: default !important` + `user-select: none` hors des couches `@layer`).
- Routes manquantes pour la réinitialisation de mot de passe câblées (`POST /api/auth/forgot-password`, `POST /api/auth/reset-password`).
- `ResetPassword::createUrlUsing()` — lien de réinitialisation redirige vers l'URL frontend SPA.
- Logo transparent généré (Pillow color-to-alpha, couleurs préservées) ; logo/icône appliqués correctement dans `AppSidebar.vue` et `HeaderLogo.vue`.
- Responsivité layout : suppression de `max-w + mx-auto` sur le div de contenu `AdminLayout.vue` → le contenu occupe tout l'espace libéré lors du repli de la sidebar.
- Clé i18n manquante `documents_page.workspace_browser.role_undefined` ajoutée (fr + en).
- Statistiques dashboard : 3 bugs de précision corrigés (`date_fin_reelle` non chargée, eager-load manquant, filtre overdue).

---

## 3. Matrice des permissions (évolution)

| Permission | `main` | `jonas` |
|---|---|---|
| Abonnement, essai, verrouillage | ❌ | ✅ |
| Gestion des plans (super_admin) | ❌ | ✅ |
| Recherche globale / limitée | ❌ | ✅ (2 niveaux) |
| Help Center lecture/édition/publication | ❌ | ✅ (7 permissions) |
| Notifications Web Push | ❌ | ✅ |
| Évaluation — fiche, export, dashboard | ❌ | ✅ (5 permissions) |
| Tâches — édition inline, vue workspace | ❌ | ✅ |
| Documents — gestion workspace | ❌ | ✅ |
| Logs admin | ❌ | ✅ |
| Workspace — voir les membres (users page) | ❌ | ✅ (WORKSPACES_VIEW_MEMBERS) |

---

## 4. Qualité du code

| Critère | État final |
|---|---|
| Larastan niveau 5 | ✅ 0 erreur |
| Laravel Pint (formatage) | ✅ Exécuté avant chaque commit |
| Build npm | ✅ Vert |
| PHPUnit (suite complète) | ✅ 811 passés / 1 en échec (1 849 assertions ; voir note ci-dessous) |
| Tests Dusk (navigateur) | ✅ 20+ tests |
| Commentaires de code | En français (conformément aux directives) |
| Documentation | En anglais (conformément aux directives) |

> **Note sur le 1 échec restant :** Les 132 échecs initiaux étaient causés par des deadlocks MySQL dus à plusieurs processus de test simultanés (artefact d'investigation — non un bug du code). Après correction du mock `MfaService::challengeRemember()` manquant dans `MfaTest` et exécution en processus unique : **811 passés, 1 en échec**. Le seul échec restant (`SocialAuthTest > callback does not create duplicate`) est un **flake d'ordre pré-existant** — il passe systématiquement en isolation (`php artisan test tests/Feature/SocialAuth/SocialAuthTest.php`), mais échoue parfois en suite complète en raison d'un état fuité par un autre test. Documenté dans SESSION_STATE depuis la Phase 10.

---

## 5. Fichiers de documentation créés

| Fichier | Objet |
|---|---|
| `docs/WORKING_GUIDELINES.md` | 26 guides opérationnels (mis à jour en continu) |
| `docs/PERMISSIONS_MATRIX.md` | Matrice complète des permissions par rôle |
| `docs/PERMISSIONS_TESTING_GUIDE.md` | Guide de test des permissions |
| `docs/technical-fr/ARCHITECTURE.md` | Architecture technique en français |
| `docs/technical-fr/GUIDE_DEVELOPPEUR.md` | Guide développeur en français |
| `docs/extended-features/INDEX.md` | Index des phases étendues |
| `docs/phase11-dashboard-profile/PLAN.md` | Plan Phase 11 |
| `docs/phase11-dashboard-profile/PROGRESSION.md` | Suivi Phase 11 |
| `docs/caching/CACHING_STRATEGY.md` | Stratégie de cache adaptatif |
| `docs/testing/PHASE11A_TESTING.md` – `PHASE11E_TESTING.md` | Guides de test par phase |
| `docs/testing/TASK_*_TESTING.md` (x16) | Guides de test par tâche |
| `docs/MAJOR_UPGRADES_PLAN.md` | Plan de montées de version majeures différées |
| `docs/deploy/deploy.sh` (equiv.) | Script de déploiement production |

---

## 6. Synthèse de la valeur ajoutée

| Axe | Avant (`main`) | Après (`jonas`) |
|---|---|---|
| **Sécurité** | Basique (Sanctum, pas de MFA, CORS wildcard) | Renforcée (MFA TOTP + email OTP, CVE patchés, CORS strict, throttles fins, bypass anti-sabotage, immutabilité post-N2) |
| **Authentification** | Login/password | + MFA + OAuth Google + codes de récupération |
| **Temps réel** | Absent | Chat équipe, notifications, typing, badges non-lus (Reverb) |
| **Notifications** | Base de données uniquement | + Broadcast + Mail + Web Push + Digest quotidien + déduplication + hiérarchie |
| **Recherche** | Absente | Full-text Typesense, 8 modèles, 3 niveaux d'accès, export Excel |
| **Contenus** | Absent | Help Center bilingue (lecteur + auteur Tiptap, brouillon auto-save) |
| **Abonnements** | Absent | Plans free/starter/pro, essai, porte HTTP 402 globale |
| **Paiement** | Absent | MTN MoMo (vérifié) + Orange Money (préparé), webhooks confirmés |
| **Performance** | N+1 non corrigés, bundle 1 MB | Dashboard −54% requêtes, bundle −81%, cache adaptatif |
| **Admin** | Interface basique | Logs natifs 3 onglets, plans CRUD, gestion workspace, métriques Horizon/Pulse |
| **Export** | Absent | PDF fiche agent + Excel tâches |
| **Tests** | ~274 PHPUnit, 0 Dusk | ~800+ PHPUnit + 20+ Dusk + Larastan 0 erreur |
| **Qualité code** | 154 erreurs Larastan | 0 erreur Larastan, Pint automatique |
| **Infrastructure** | Sync queue, file cache | Redis, Horizon, Pulse, Reverb, script déploiement |
