# Progression Tracker — Work Tracking v2.0

> Update this file at the end of every task. One row per task.

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Task Progress

| # | Task | Branch | Status | Started | Completed | Notes |
|---|---|---|---|---|---|---|
| 0 | Permission Architecture Refactor (Policies) | `feature/v2-task-0-policies-refactor` | ✅ | 2026-05-12 | 2026-05-12 | — |
| 1 | Queue (database) + Laravel Reverb | `feature/v2-task-1-queue-reverb` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 2 | Subtask Data Model | `feature/v2-task-2-subtask-model` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 3 | Subtask CRUD API + Auto Progress | `feature/v2-task-3-subtask-api` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 4 | Subtask UI | `feature/v2-task-4-subtask-ui` | ✅ | 2026-05-14 | 2026-05-14 | — |
| 5 | Validation N0 + 48h Timer | `feature/v2-task-5-validation-n0` | ⬜ | — | — | — |
| 6 | Anti-Sabotage Bypass | `feature/v2-task-6-bypass` | ⬜ | — | — | — |
| 7 | N1 Scores + Pending Validations | `feature/v2-task-7-scores-dashboard` | ⬜ | — | — | — |
| 8 | Reverb + Web Push Notifications | `feature/v2-task-8-notifications` | ⬜ | — | — | — |
| 9 | Agent Sheet + Full Scoring | `feature/v2-task-9-agent-sheet` | ⬜ | — | — | — |
| 10 | Evaluation Dashboard + Global Task View | `feature/v2-task-10-dashboard` | ⬜ | — | — | — |
| 11 | Task Creation UX (wizard + intervenant picker) | `feature/v2-task-11-task-creation-ux` | ⬜ | — | — | — |
| 12 | Document Management per Project + Workspace | `feature/v2-task-12-document-management` | ⬜ | — | — | — |
| 13 | Subscription Modes + Trial Duration | `feature/v2-task-13-subscription` | ⬜ | — | — | — |
| 14 | Platform Super Admin Dashboard | `feature/v2-task-14-platform-dashboard` | ⬜ | — | — | — |

---

## Deliverables Checklist per Completed Task

### Task 0
- [x] Contextual roles seeded into Spatie `roles` table with default permissions
- [x] `ProjetPolicy`, `ActivitePolicy`, `TachePolicy`, `DocumentPolicy`, `WorkspacePolicy` created
- [x] `SousTachePolicy` stub created (populated in Task 2)
- [x] `Gate::before()` super_admin bypass registered in `AuthServiceProvider`
- [x] All policies registered in `AuthServiceProvider::$policies`
- [x] `PermissionService` refactored to relationship-helper only
- [x] All controllers updated: `abort_unless` → `$this->authorize()`
- [x] `RolePermissionSeeder` updated to seed contextual roles
- [x] All existing tests still passing
- [ ] PR opened into `jonas`

### Task 1
- [x] `QUEUE_CONNECTION=database` in `.env` and `.env.example`
- [x] Reverb installed and configured
- [x] `laravel-echo` + `pusher-js` installed
- [x] `useEcho.js` composable created
- [x] Tests passing
- [x] PR merged into `jonas`

### Task 2
- [x] Migration `create_sous_taches_table` applied
- [x] Migration `create_sous_tache_user_table` applied
- [x] `parent_tache_id` dropped from `taches` (data migrated to `sous_taches`)
- [x] `EN_RETARD` and `A_REFAIRE` added to `TacheStatut`
- [x] `SousTache` model with R1 + R2 rules
- [x] `SousTacheFactory` and `SousTacheSeeder` created
- [x] `SousTacheResource` created
- [x] `SousTachePolicy` created and registered in `AuthServiceProvider`
- [x] Permissions added (PermissionService + RolePermissionSeeder)
- [x] Translation files `lang/fr/sous_taches.php` and `lang/en/sous_taches.php` created
- [x] Bug fix: `soustaches` → `sousTaches` in ProjetController (2 occurrences)
- [x] `is_responsable` added to `assignees()` withPivot in Tache model
- [x] Tests passing (6 new tests)
- [ ] Frontend composable (`useTachePermissions.js`) — deferred to Task 4
- [x] PR merged into `jonas`

### Task 3
- [x] `SousTacheService` created (create, update, delete, assignIntervenant)
- [x] `SousTacheController` created (5 endpoints: index, store, update, destroy, assignIntervenant)
- [x] `SousTacheObserver` created and registered in `AppServiceProvider`
- [x] Routes registered in `api.php`
- [x] `canAssignSousTacheIntervenant` permission added to `PermissionService` and seeder
- [x] Translation keys added (fr/en): date_exceeds_parent, status_blocked, unauthorized, intervenant_assigned, notifications.assigned
- [x] 3 notifications created: `SousTacheAssigneeNotification`, `SousTacheOverdueNotification`, `TacheStatutAutoChangeNotification`
- [x] Migration: extend `taches.statut` MySQL ENUM with `en_retard` and `a_refaire`
- [x] Manual statut block on parent Tache when sous-taches exist (model `updating` hook)
- [x] Tests passing (10 new tests, 41 total)
- [ ] PR merged into `jonas`

### Task 4
- [x] `useSousTaches.js` composable created
- [x] `SousTacheList.vue` created
- [x] `SousTacheForm.vue` created
- [x] Kanban card indicator added (count badge + mini progress bar on TacheCard)
- [ ] Submit result button disabled when blocking subtasks — deferred to Task 5 (results tab)
- [x] Permissions added to `useActivitePermissions.js` (`canCreateSousTache`, `canAssignSousTacheIntervenant`)
- [x] Translation keys added (`ui.*` section in fr/en sous_taches.php)
- [x] Tests passing (41 total)
- [ ] PR opened into `jonas`

### Task 5
- [ ] N0 columns added to `tache_resultats`
- [ ] `validation_audit_logs` table created
- [ ] `approuverN0`, `renvoyerN0`, `transmettreAuN1` added to `TacheResultatService`
- [ ] `TransmettreResultatAuN1Job` created
- [ ] `ResultatSoumisN0Notification` and `ResultatTransmisAutoNotification` created
- [ ] `validation_timeout_hours` workspace setting added
- [ ] Permissions added (PermissionService + Seeder + `useTachePermissions.js`)
- [ ] Translation files `lang/fr/validation.php` and `lang/en/validation.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 6
- [ ] Bypass columns added to `tache_resultats`
- [ ] `activerBypass` added to `TacheResultatService`
- [ ] `escalades_abusives` flag logic implemented
- [ ] N1 validation context panel added
- [ ] `canActiverBypass` permission added
- [ ] Translation keys added
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 7
- [ ] `evaluation_scores` table created
- [ ] `EvaluationScoreService` created
- [ ] Pending validations dashboard page created
- [ ] Permissions added
- [ ] Translation files `lang/fr/evaluation.php` and `lang/en/evaluation.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 8
- [ ] `ValidationNotificationEvent` created
- [ ] `useNotifications.js` updated for Echo
- [ ] Web Push notifications added
- [ ] Deduplication logic implemented
- [ ] `canManageNotificationPreferences` permission added
- [ ] Email templates created (FR + EN)
- [ ] Translation files `lang/fr/notifications.php` and `lang/en/notifications.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 9
- [ ] `EvaluationService` with 8-criteria scoring created
- [ ] Agent sheet page created (4 sections)
- [ ] Return quality and escalations indicators added
- [ ] Post-N2 immutability enforced
- [ ] Permissions added
- [ ] Translation keys added
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 10
- [ ] Evaluation dashboard page created
- [ ] Workspace-wide task view created
- [ ] `GET /evaluations/dashboard` endpoint created
- [ ] Permissions added
- [ ] Translation keys added
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 11
- [ ] `TacheCreateWizard.vue` step wizard created (4 steps)
- [ ] `IntervenantPicker.vue` searchable multi-select created
- [ ] Resource notification on task creation implemented
- [ ] Translation keys added to `lang/fr/taches.php` and `lang/en/taches.php`
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 12
- [ ] Project-level document endpoints created (list, upload, access, delete, share)
- [ ] `ProjetDocuments.vue` page created
- [ ] Workspace-level document endpoints created
- [ ] `WorkspaceDocuments.vue` page created
- [ ] Permissions added (PermissionService + Seeder + composables)
- [ ] Translation keys added to `lang/fr/documents.php` and `lang/en/documents.php`
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 13
- [ ] `subscription_mode` and trial columns added to `workspaces`
- [ ] `config/subscription.php` created with defaults
- [ ] `SubscriptionService` created
- [ ] `CheckSubscriptionLimits` middleware created
- [ ] Trial expiry banner added to frontend
- [ ] `canManageSubscription` permission added
- [ ] Translation files `lang/fr/subscription.php` and `lang/en/subscription.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

### Task 14
- [ ] `/admin/dashboard` page created
- [ ] `/admin/workspaces` page created
- [ ] `/admin/users` page created
- [ ] `GET /admin/stats` endpoint created
- [ ] All `/admin/*` routes protected by `super_admin` middleware
- [ ] `canAccessPlatformDashboard` permission added
- [ ] Translation files `lang/fr/admin.php` and `lang/en/admin.php` created
- [ ] Tests passing
- [ ] PR opened into `jonas`

---

## Bugs Encountered

> Log non-blocking bugs here so they are not forgotten.

| # | Description | File(s) | Severity | Status |
|---|---|---|---|---|
| — | — | — | — | — |
