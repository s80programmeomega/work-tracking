# Bug Batch Progression — branch `fix/bug-batch`

> Update this file at the end of every group. One row per group in the summary table; full checklist below.

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Group Summary

| # | Group | Bugs closed | Branch commit | Status | Completed | PHPUnit | Notes |
|---|---|---|---|---|---|---|---|
| G1 | `TacheController::show` returns raw model | #1, #8 | `c9d1bda` | ✅ | 2026-05-24 | +8 tests (156 total) | `TacheResource` wrap + `TacheControllerShowResourceTest` |
| G2 | Central self-notification guard + delete 3 self-confirm classes | #1, #9, #11 | `2304798` | ✅ | 2026-05-24 | +8 tests (156 total) | `sendUnlessSelf()` + `NoSelfNotificationTest` |
| G3 | Route 7 N1/N2 notifications through `channelsFor()` + add event types to push/email matchers | #4, #10 (partial) | `af0484a` | ✅ | 2026-05-24 | +15 tests (171 total) | `NotificationServiceChannelsForTest` |
| G4 | Verify N1→N2 pending counter + Dusk coverage | #5 | `c2e6f48` | ✅ | 2026-05-24 | +2 tests (173 total) | `ValidationStatutTransitionTest` extended + `PendingValidationsTest` Dusk case |
| G5 | Split "Validations" pages by audience (assignee vs validator) | #2 | `a8fb52f` | ✅ | 2026-05-24 | +9 tests (182 total) | `/mes-validations` + `/validations/a-traiter` + `MesValidationsEnAttente.vue` |
| G6 | Subtask badge on all Kanban variants | #7 | `73a8ef4` | ✅ | 2026-05-24 | no new tests | All main cards already had badge; only `dashboard/KanbanTaskCard.vue` was missing it |
| G7 | Consolidate duplicate modals (3× ResultatDetail, 2× ValidationModal) | #12 | `bff4ac3` | ✅ | 2026-05-24 | no new tests | 3 legacy files deleted; 3 callers rewired to `taches/resultats/` |
| G8 | Gate every sidebar entry via `useWorkspacePermissions` | #13 | `dbae446` | ✅ | 2026-05-24 | no new tests | `canViewAllTasks` + `canSubmitResult` helpers + `requiresPermission` in nav data |
| G9 | Finish subtasks: `removeIntervenant` endpoint + intervenant UI + responsable field | #3 | `ae90e8e` | ✅ | 2026-05-24 | +4 tests (186 total) | Inline chip row in `SousTacheList`; assign/remove dropdown; `SousTacheForm` responsable picker |
| G10 | Profile gaps (avatar, language preference, account deletion) | #6 | `8fc1cdb` | ✅ | 2026-05-25 | +12 tests (198 total) | Avatar upload fixed, language/timezone persisted to API, self-delete with password confirm |
| G11 | Web Push end-to-end verify | #4 | — | ⚠️ skipped | 2026-05-26 | — | Manual gate — skipped by Jonas; G3 routing fix is in place; OS-level toast confirmation deferred |
| G12 | Cross-cutting reactivity (Reverb events + Vue refetch tightening) | UX polish | `a9b259d` | ✅ | 2026-05-25 | +6 tests (204 total) | 4 broadcast events, workspace channel auth, useRealtimeRefresh composable, 5 pages wired |

---

## Detailed Checklists

### G1 — `TacheController::show` raw-model fix ✅

- [x] `TacheController::show` returns `new TacheResource($tache)` instead of raw model
- [x] All sibling endpoints (`update`, `move`, `archive`, etc.) already wrapped (Task 9 cleanup)
- [x] `tests/Feature/TacheControllerShowResourceTest.php` — asserts `data.my_result`, `data.validation_status`, `data.permissions`, `data.bypass.*` are present
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — all passing
- [x] PROGRESSION.md "Bugs Encountered" row #1 closed

---

### G2 — Central self-notification guard ✅

- [x] `NotificationService::sendUnlessSelf(notifiable, ?actor, notification)` added
- [x] `ValidationN1ConfirmeeNotification` deleted
- [x] `ValidationN2ConfirmeeNotification` deleted
- [x] `RejetConfirmeNotification` deleted
- [x] All `notify()` call sites in validation flow routed through `sendUnlessSelf` or `app(NotificationService::class)`:
  - [x] `TacheResultat::validateByN1`, `validateByN2`, `reject`, `notifyValidators`
  - [x] `TacheResultatService::soumettre`, `approuverN0`, `renvoyerN0`, `activerBypass`
  - [x] `TacheService::handleFileUploads`, `deleteAttachment`, `addExternalLinks`, `deleteExternalLink`
  - [x] `Tache::notifyResponsableOfCompletion`
  - [x] `SousTacheService::assignIntervenant`
  - [x] `EvaluationScoreService::calculerImpactN1`
- [x] `tests/Feature/NoSelfNotificationTest.php` — 8 tests covering the guard directly + integration tests on every validation path
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — 156 passing

---

### G3 — Route N1/N2 notifications through `channelsFor()` ✅

- [x] `NotificationService::wantsWebPush()` extended: `soumis_n1`, `en_validation_n2`, `valide_n1`, `valide_n2`, `rejete_n1`, `rejete_n2`, `validation_complete` added to high-signal set
- [x] `NotificationService::wantsEmail()` extended with the same 7 event types
- [x] `ResultatSoumisNotification::via()` → `channelsFor($notifiable, 'soumis_n1')`
- [x] `ResultatEnAttenteN2Notification::via()` → `channelsFor($notifiable, 'en_validation_n2')`
- [x] `ResultatValideN1Notification::via()` → `channelsFor($notifiable, 'valide_n1')`
- [x] `ResultatValideN2Notification::via()` → `channelsFor($notifiable, 'valide_n2')`
- [x] `ResultatRejeteNotification::via()` → `channelsFor($notifiable, 'rejete_n1' | 'rejete_n2')` (level-aware)
- [x] `ResultatRejeteN2InfoNotification::via()` → `channelsFor($notifiable, 'rejete_n2')` + `toMail()` added
- [x] `ResultatValidationCompleteNotification::via()` → `channelsFor($notifiable, 'validation_complete')` + `toMail()` added
- [x] `tests/Feature/Services/NotificationServiceChannelsForTest.php` — 15 tests (7 channelsFor signal coverage + 8 via() delegation checks)
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — 171 passing

---

### G4 — N1→N2 transition verification ✅

- [x] Confirm `ValidationStatutTransitionTest` still covers the pending-N2 counter query after `validateByN1`
- [x] Extend `ValidationStatutTransitionTest`: assert pending-N2 row has correct `user_id` (author) and `validateur_n1_id`
- [x] Extend `ValidationStatutTransitionTest`: two-context scope-leak test confirms both rows appear with distinct `user_id`s
- [x] Dusk: extend `tests/Browser/Evaluation/PendingValidationsTest.php` — pending-N2 section visible when result is seeded in `en_validation_n2`
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact --filter=ValidationStatutTransitionTest` — 6 passing

---

### G5 — Split validation pages by audience ✅

- [x] New route `/mes-validations` → `MesValidationsEnAttente.vue` (assignee: results I submitted, waiting on others)
- [x] New API endpoint `GET /api/evaluations/mes-resultats/en-attente` → `EvaluationController::mesResultatsEnAttente()` scoped to `user_id = auth()->id()`
- [x] Old validator route `/taches/resultats/en-attente` → redirect to `/validations/a-traiter`
- [x] Router: `validations.a-traiter` name + `ValidationResultats.vue` component
- [x] Sidebar Tâches section: "Tâches en attente de validation" → "Mes validations en attente" at `/mes-validations`
- [x] Sidebar Évaluations section: "Validations en attente" → "Validations à traiter" at `/validations/a-traiter`
- [x] `tests/Feature/Validation/AssigneePendingListTest.php` — 5 tests
- [x] `tests/Feature/Validation/ValidatorPendingListTest.php` — 3 tests (scope isolation)
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — 182 passing

---

### G6 — Subtask badge on all Kanban variants ✅

- [x] All main Kanban boards (`KanbanBoard`, `KanbanBoardSimple`, `KanbanBoardPremium`) delegate to `TacheCard`/`TacheCardPersonal`/`TacheCardResponsable` which already had the badge — no change needed there
- [x] `dashboard/KanbanTaskCard.vue` was the only missing variant — badge block added inline
- [x] `vendor/bin/pint --dirty` clean

---

### G7 — Consolidate duplicate modals ✅

- [x] Grep confirmed all callers
- [x] `NotificationMenu.vue` → `taches/resultats/ResultatDetailModal.vue`
- [x] `FichesEvaluation.vue` → `taches/resultats/ResultatDetailModal.vue`
- [x] `TachesParUtilisateur.vue` → `taches/resultats/ValidationModal.vue`
- [x] `resources/js/components/modals/ResultatDetailModal.vue` deleted (868 lines)
- [x] `resources/js/components/taches/ResultatDetailModal.vue` deleted (362 lines)
- [x] `resources/js/components/taches/ValidationModal.vue` deleted (260 lines, identical to resultats/ copy)
- [x] `vendor/bin/pint --dirty` clean

---

### G8 — Gate sidebar entries via permissions ✅

- [x] `useWorkspacePermissions.js` extended: `canViewAllTasks` + `canSubmitResult` helpers added
- [x] `AppSidebar.vue` — `getFilteredSubItems` extended with `requiresPermission` string key lookup via `permissionMap` computed
- [x] Nav entries gated: "Toutes les tâches", "Mes validations", "Tableau de bord évaluations", "Validations à traiter", "Fiches d'évaluation"
- [x] `vendor/bin/pint --dirty` clean

---

### G9 — Finish subtasks: removeIntervenant + UI ✅

- [x] `SousTacheController::removeIntervenant` added (`DELETE /sous-taches/{sousTache}/intervenants/{user}`)
- [x] Route registered in `routes/api.php`
- [x] `SousTachePolicy` updated with `removeIntervenant` gate
- [x] `responsable_id` accepted on `POST /taches/{id}/sous-taches` + persisted
- [x] `SousTacheForm.vue` — responsable picker added
- [x] `SousTacheList.vue` — inline intervenant chip row per item (add dropdown + search + remove ×); no separate panel component needed
- [x] `useSousTaches.js` composable — `removeIntervenant` method added
- [x] 4 new API tests in `SousTacheApiTest.php` (assign, remove, unauthorized-remove, responsable_id stored)
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact tests/Feature/SousTacheApiTest.php` — 14 passing

---

### G10 — Profile gaps ✅

- [x] `EditProfileModal.vue`: avatar file picker added; `handleSave` uses `FormData` + `POST /api/users/profile`; self-contained API call (emits `updated` with refreshed user); fixed broken `Modal` import path
- [x] `UserService::handleAvatarUpload` fixed to use `Storage::disk('public')->put()` instead of broken `storeAs('public', …)`
- [x] `PreferencesSettings.vue`: `savePreferences` now calls `PUT /api/users/profile` with `language`+`timezone` (was localStorage-only)
- [x] `UserProfile.vue`: Security tab danger zone + password-confirmed DELETE modal
- [x] `UserController::deleteAccount` + `UserService::deleteAccount` (token revoke + soft-delete + avatar cleanup)
- [x] Route `DELETE /users/profile` added
- [x] `tests/Feature/Profile/AvatarUploadTest.php` — 4 tests
- [x] `tests/Feature/Profile/PreferredLocaleTest.php` — 3 tests
- [x] `tests/Feature/Profile/AccountDeletionTest.php` — 5 tests
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — 198 passing
- [x] **Out of scope:** 2FA, email change with verification → deferred to `feature/profile-security`

---

### G11 — Web Push end-to-end verify ⚠️ skipped

> Skipped by Jonas on 2026-05-26. G3 routing fix is in place (7 N1/N2 event types added to `wantsWebPush`/`wantsEmail`). OS-level push toast confirmation deferred — no blocker for Task 10.

- [x] G3 routing fix landed — `channelsFor()` now covers all N1/N2 event types
- [ ] OS-level toast confirm — **deferred**

---

### G12 — Cross-cutting reactivity ✅

- [x] `app/Events/Realtime/TacheStatutChanged.php` (`ShouldBroadcastNow`, channel `workspace.{id}`)
- [x] `app/Events/Realtime/ResultatStatutChanged.php`
- [x] `app/Events/Realtime/SousTacheChanged.php`
- [x] `app/Events/Realtime/PendingValidationCountChanged.php`
- [x] All four events route via `Tache → activite → projet → workspace_id` (chain corrected from wrong `activite.workspace_id`)
- [x] `TacheService::moveTache` fires `TacheStatutChanged` after update
- [x] `TacheResultatService`: soumettre/approuverN0/validerN1/rejeterN1 fire `ResultatStatutChanged` + `PendingValidationCountChanged` via `broadcastResultatChanged()`
- [x] `SousTacheService`: create/update/delete fire `SousTacheChanged`
- [x] `routes/channels.php` — `workspace.{workspaceId}` private channel auth (member check)
- [x] `resources/js/composables/useRealtimeRefresh.js` — debounced Echo listeners, `onMounted`/`onUnmounted` lifecycle
- [x] `MesTaches.vue`, `ValidationResultats.vue`, `MesValidationsEnAttente.vue` wired
- [x] `NotificationMenu.vue`: 30 s poll → 60 s fallback + broadcast-triggered `safeFetchUnread`
- [x] `tests/Feature/Realtime/TacheStatutChangedEventTest.php` — 3 tests
- [x] `tests/Feature/Realtime/PendingValidationCountChangedEventTest.php` — 3 tests
- [x] `vendor/bin/pint --dirty` clean
- [x] `php artisan test --compact` — 204 passing

---

## Test Count Targets

| Milestone | PHPUnit | Dusk |
|---|---|---|
| Start of batch (after Task 9 merge) | 148 | 12 |
| After G1–G3 | **171** | 12 |
| After G4–G6 | **182** | 12 |
| After G7–G9 | **186** | 12 |
| After G10–G12 | **204** | 12 |

---

## Commit Log

| Commit | Group | Message summary |
|---|---|---|
| `c9d1bda` | G1 | fix(G1): wrap TacheController::show response in TacheResource |
| `2304798` | G2 | fix(G2): central self-notification guard + drop 3 self-confirm notifications |
| `af0484a` | G3 | fix(G3): route 7 N1/N2 notifications through NotificationService::channelsFor() |
| `c2e6f48` | G4 | fix(G4): N1→N2 pending query assertions + Dusk pending-N2 section test |
| `a8fb52f` | G5 | fix(G5): split validation pages — assignee view + validator queue + sidebar rewire |
| `73a8ef4` | G6 | fix(G6): add subtask badge to dashboard KanbanTaskCard |
| `bff4ac3` | G7 | fix(G7): consolidate duplicate ResultatDetail/ValidationModal components |
| `dbae446` | G8 | fix(G8): gate sidebar entries via useWorkspacePermissions |
| `ae90e8e` | G9 | fix(G9): subtask intervenants — remove endpoint + inline assign/remove UI |
| `8fc1cdb` | G10 | fix(G10): profile gaps — avatar upload, language preference API persist, account deletion |
| `a9b259d` | G12 | fix(G12): cross-cutting reactivity — Reverb broadcast events + Vue refetch |
