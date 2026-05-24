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
| G5 | Split "Validations" pages by audience (assignee vs validator) | #2 | — | ⬜ | — | — | Rename routes + sidebar labels + scope queries |
| G6 | Subtask badge on all Kanban variants | #7 | — | ⬜ | — | — | Extract `SubtaskCountBadge.vue` + wire 3 Kanban files |
| G7 | Consolidate duplicate modals (3× ResultatDetail, 2× ValidationModal) | #12 | — | ⬜ | — | — | Keep `taches/resultats/`; delete legacy; rewire imports |
| G8 | Gate every sidebar entry via `useWorkspacePermissions` | #13 | — | ⬜ | — | — | `AppSidebar.vue` + new composable helpers |
| G9 | Finish subtasks: `removeIntervenant` endpoint + intervenant UI + responsable field | #3 | — | ⬜ | — | — | Backend endpoint + `SousTacheIntervenantsPanel.vue` |
| G10 | Profile gaps (avatar, language preference, account deletion) | #6 | — | ⬜ | — | — | 2FA deferred to separate branch |
| G11 | Web Push end-to-end verify | #4 | — | ⬜ | — | — | Manual only; code done in G3 — needs real toast confirmation |
| G12 | Cross-cutting reactivity (Reverb events + Vue refetch tightening) | UX polish | — | ⬜ | — | — | Layered last once G1–G11 are green |

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

### G5 — Split validation pages by audience ⬜

- [ ] New route `/mes-validations` → `ValidationResultats.vue` (assignee: results I submitted, waiting on others)
- [ ] Existing route renamed `/validations/a-traiter` → `evaluations/PendingValidations.vue` (validator: results awaiting MY action)
- [ ] `EvaluationController::pendingValidationsDashboard` scoped by `?as=assignee|validator` (or two methods)
- [ ] Sidebar updated: two entries with correct labels + `v-if` guards
- [ ] `routes/api.php` and `resources/js/router/index.ts` updated
- [ ] Dusk path references in existing tests updated to new routes
- [ ] `tests/Feature/Validation/AssigneePendingListTest.php` (new)
- [ ] `tests/Feature/Validation/ValidatorPendingListTest.php` (new)
- [ ] Dusk: `tests/Browser/Validation/AssigneeMyValidationsTest.php` (new)
- [ ] Dusk: extend `tests/Browser/Evaluation/PendingValidationsTest.php` for renamed entry
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

### G6 — Subtask badge on all Kanban variants ⬜

- [ ] `resources/js/components/taches/SubtaskCountBadge.vue` extracted (used by existing 4 card components + 3 Kanban files)
- [ ] Existing card components import `SubtaskCountBadge` (no logic change)
- [ ] `KanbanBoard.vue` renders badge on each card
- [ ] `KanbanBoardSimple.vue` renders badge on each card
- [ ] `KanbanBoardPremium.vue` renders badge on each card
- [ ] Dusk: `tests/Browser/Kanban/SubtaskBadgeOnKanbanTest.php` — badge present/absent based on `sous_taches_count`
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

### G7 — Consolidate duplicate modals ⬜

- [ ] Grep confirms all callers of the two legacy paths
- [ ] Imports in every caller repointed to `resources/js/components/taches/resultats/ResultatDetailModal.vue`
- [ ] Imports in every caller of `taches/ValidationModal.vue` repointed to `taches/resultats/ValidationModal.vue`
- [ ] `resources/js/components/modals/ResultatDetailModal.vue` deleted
- [ ] `resources/js/components/taches/ResultatDetailModal.vue` deleted
- [ ] `resources/js/components/taches/ValidationModal.vue` deleted
- [ ] `npm run build` succeeds (no missing import errors)
- [ ] Dusk: re-run affected validation flows to confirm no regressions
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

### G8 — Gate sidebar entries via permissions ⬜

- [ ] `useWorkspacePermissions.js` extended: `canViewAllTasks`, `isTaskResponsableSomewhere` helpers added
- [ ] `AppSidebar.vue` — every entry has a `v-if` (see BUG_BATCH_PLAN.md §Group 8 audit table)
- [ ] Dusk: `tests/Browser/Sidebar/SidebarPermissionGateTest.php` — per-role visibility assertions (super_admin, manager, cadre, stagiaire, observateur)
- [ ] PERMISSIONS_MATRIX.md changelog row added
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

### G9 — Finish subtasks: removeIntervenant + UI ⬜

- [ ] `SousTacheController::removeIntervenant` added (`DELETE /sous-taches/{sousTache}/intervenants/{user}`)
- [ ] Route registered in `routes/api.php`
- [ ] `SousTachePolicy` updated with `removeIntervenant` gate
- [ ] `responsable_id` accepted on `POST /taches/{id}/sous-taches` + persisted
- [ ] `SousTacheForm.vue` — responsable picker added
- [ ] `resources/js/components/taches/SousTacheIntervenantsPanel.vue` created (assign/remove chips UI)
- [ ] `SousTacheList.vue` renders `SousTacheIntervenantsPanel` per row
- [ ] `useSousTaches.js` composable — `removeIntervenant` method added if missing
- [ ] `tests/Feature/SousTacheRemoveIntervenantTest.php` (new)
- [ ] `tests/Feature/SousTacheResponsableFieldTest.php` (new)
- [ ] Dusk: `tests/Browser/Subtasks/SousTacheIntervenantsTest.php` (new)
- [ ] Dusk: `tests/Browser/Subtasks/SousTacheFormResponsableTest.php` (new)
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

### G10 — Profile gaps ⬜

- [ ] Avatar upload: `EditProfileModal.vue` → multipart POST `avatar` field; `UserController::updateProfile` accepts file, stores under `storage/app/public/avatars/`
- [ ] Language preference: `PreferencesSettings.vue` dropdown → persists `users.preferred_locale`; migration if column missing
- [ ] Account deletion: confirm dialog in Security tab → POST `/api/profile/delete` (password confirm) → soft-delete + revoke Sanctum tokens + 204
- [ ] `tests/Feature/Profile/AvatarUploadTest.php` (new)
- [ ] `tests/Feature/Profile/PreferredLocaleTest.php` (new)
- [ ] `tests/Feature/Profile/AccountDeletionTest.php` (new)
- [ ] Dusk: `tests/Browser/Profile/AvatarUploadDuskTest.php` (new)
- [ ] Dusk: `tests/Browser/Profile/LanguagePreferenceTest.php` (new)
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing
- [ ] **Out of scope:** 2FA, email change with verification → deferred to `feature/profile-security`

---

### G11 — Web Push end-to-end verify ⬜

> No code change expected. G3 should have restored the routing. This group is a manual verification gate.

- [ ] `php artisan migrate:fresh --seed` with `NotificationDemoSeeder` active
- [ ] Queue worker running: `php artisan queue:work`
- [ ] Sign in as cadre in Chrome, grant push permission (follow `docs/testing/TASK_8B_TESTING.md` Case 1)
- [ ] Collaborateur submits a result targeting cadre as N1 validator
- [ ] Cadre receives a system (OS-level) browser notification within ~10s
- [ ] If push still fails: debug `WebPushChannel::send` with `Log::info` of SDK response and fix root cause
- [ ] Marked complete only after Jonas confirms receiving a real toast

---

### G12 — Cross-cutting reactivity ⬜

- [ ] `app/Events/Realtime/TacheStatutChanged.php` created (`ShouldBroadcastNow`)
- [ ] `app/Events/Realtime/ResultatStatutChanged.php` created
- [ ] `app/Events/Realtime/SousTacheChanged.php` created
- [ ] `app/Events/Realtime/PendingValidationCountChanged.php` created
- [ ] Events fired from `TacheService` after mutations
- [ ] Events fired from `TacheResultatService` after transitions
- [ ] Events fired from `SousTacheService` after mutations
- [ ] `routes/channels.php` — `workspace.{id}` + `task.{id}` authorize callbacks added
- [ ] `resources/js/composables/useRealtimeRefresh.js` created (debounced Echo listener)
- [ ] Pages wired: `MesTaches.vue`, `Activites.vue`, `Dashboard.vue`, `KanbanBoard*.vue`, `TacheDetail.vue`, `evaluations/PendingValidations.vue`, `ValidationResultats.vue`, `AppSidebar.vue`
- [ ] `NotificationMenu.vue` — `setInterval` replaced with broadcast listener (60s fallback kept)
- [ ] `tests/Feature/Realtime/TacheStatutChangedEventTest.php` (new)
- [ ] `tests/Feature/Realtime/PendingValidationCountChangedEventTest.php` (new)
- [ ] Dusk: `tests/Browser/Realtime/CrossTabRealtimeTest.php` — two-browser cross-tab test
- [ ] `vendor/bin/pint --dirty` clean
- [ ] `php artisan test --compact` passing

---

## Test Count Targets

| Milestone | PHPUnit | Dusk |
|---|---|---|
| Start of batch (after Task 9 merge) | 148 | 12 |
| After G1–G3 (done) | **171** | 12 |
| After G4–G6 | ~183 | ~16 |
| After G7–G9 | ~200 | ~21 |
| After G10–G12 | **~210** | **~24** |

---

## Commit Log

| Commit | Group | Message summary |
|---|---|---|
| `c9d1bda` | G1 | fix(G1): wrap TacheController::show response in TacheResource |
| `2304798` | G2 | fix(G2): central self-notification guard + drop 3 self-confirm notifications |
| `af0484a` | G3 | fix(G3): route 7 N1/N2 notifications through NotificationService::channelsFor() |
