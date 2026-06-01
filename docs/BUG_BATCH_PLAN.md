# Bug Batch — Fix Plan (branch `fix/bug-batch`)

## Context

After merging Task 9 into `jonas`, the user surfaced 13 bugs spanning notifications, the validation flow, the sidebar/IA, subtasks, the profile, and a few UI regressions. Several share root causes (e.g. the missing self-notification guard explains #1, #9, #11; the `TacheController::show` raw-model bug explains #8 and parts of #3). The goal of this batch is to land all 13 fixes on a single `fix/bug-batch` branch with focused commits per bug-group, regression tests, and a sidebar/views consolidation aligned with the user's clarifications.

Branch is already cut: `fix/bug-batch` from `jonas` (Task 9 merged, 12 commits ahead).

---

## Root-cause map

Many of the reported bugs collapse to a smaller set of root causes. Fixing each root cause closes multiple reported bugs.

| Root cause | Closes |
|---|---|
| **R1.** `TacheController::show` returns raw `$tache` instead of `new TacheResource($tache)` (was logged in PROGRESSION.md as Bug #1 — still open) | #8 (no submit button), bypass UI invisibility, validation badge gaps |
| **R2.** 5 validation notification classes hardcode `via() = ['mail','database']` instead of calling `NotificationService::channelsFor()`, AND `wantsWebPush()` match doesn't list any N1/N2 event types | #4 (push not working), #1 + #9 + #11 also amplified |
| **R3.** No central self-notification guard. Validator-confirm notifications are sent to the validator regardless of identity (`$validator->notify(new XConfirmeeNotification(...))`). Same pattern at 3 sites | #1, #9, #11 |
| **R4.** N1 validation flow `TacheResultat::validateByN1()` was fixed in Task 9 cleanup (statut transitions now persist), but the upstream notification path still doesn't expose the new `en_validation_n2` row to the dashboard counter — *partially* because of R2 (push) + the dashboard query filters that count it | #5 — likely already half-fixed by `451aa4d`, verify with manual test |
| **R5.** `KanbanBoard*.vue` doesn't render `tache.sous_taches_count` badge (TacheCard / TacheCardPersonal / TacheCardResponsable / ValidationTaskCard all do) | #7 |
| **R6.** Sidebar links have no `useWorkspacePermissions` gates | #13, parts of #12 |
| **R7.** Three `ResultatDetailModal.vue` copies + duplicate `ValidationModal.vue` | #12 |
| **R8.** Two "Validations" sidebar entries with overlapping content but different audiences (user clarified: one is the assignee's pending list, the other is the validator's queue) | #2 — re-label + scope each correctly |
| **R9.** Subtasks: `removeIntervenant` API endpoint missing, frontend has no UI for assign/remove intervenant, SousTacheForm lacks responsable field | #3 |
| **R10.** Profile: avatar upload UI, language preference, 2FA, account deletion not implemented | #6 — scope this carefully, may be a partial fix in this batch |
| **R11.** Web Push reported as still not functional even after Task 8b: combination of R2 + missing event-type keys + needs end-to-end verify | #4, #10 |
| **R12.** N0 validation status check requested (#10) — exploration shows it's actually functional; just needs verification | #10 |

---

## Prioritised fix order

Severity = user-visible impact × frequency × blast radius. Each commit is independently revertable.

| Order | Group | Bugs closed | Severity | Why this order |
|---|---|---|---|---|
| **1** | R1 — `TacheController::show` returns raw model | #8 + Task 9 bypass UI | Critical | One-line fix, unblocks the submit button + everything else in the task detail page that depends on resource fields |
| **2** | R3 — central self-notification guard | #11, #9, #1 (partial) | Critical | Trivial bug, immediate user-trust impact ("why does the app notify me about my own action?"). Foundation other notification fixes build on |
| **3** | R2 — wire 5 validation notifications to `channelsFor()` + add N1/N2 event types to `wantsWebPush`/`wantsEmail` | #4, #1, #9 | Critical | Closes the duplicate-notification per-role bug AND the push-not-working bug in one pass |
| **4** | R4 — verify N1→N2 transition end-to-end and Dusk-cover the pending counter | #5, #10 | High | Already half-fixed by `451aa4d`. Need to verify counter updates AND that N0 page renders correctly (no missing buttons) |
| **5** | R8 — split the two "Validations" pages by audience (assignee vs validator) and rename | #2 | High | Per user clarification: (a) becomes "Mes validations à fournir" (assignee POV — results they submitted that are awaiting N1/N2), (b) stays "Validations en attente" (validator POV — pending action for the current user as a validator) |
| **6** | R5 — add subtask badge to Kanban variants | #7 | Medium | Quick win; consistent with the other card variants |
| **7** | R7 — consolidate duplicate modals (3× ResultatDetailModal, 2× ValidationModal) | #12 | Medium | Keep the newest (`taches/resultats/`), grep/rewire imports, delete legacy. Dusk re-run after |
| **8** | R6 — gate every sidebar link via `useWorkspacePermissions` | #13 | Medium | Touches `AppSidebar.vue` only; needs a `canX` for every link, some may need new helpers in `useWorkspacePermissions.js` |
| **9** | R9 — finish subtasks integration | #3 | Medium | Two backend endpoints (`removeIntervenant`, `assignResponsable` if missing) + a small UI block in `SousTacheList.vue` for intervenant management + add `responsable_id` to SousTacheForm |
| **10** | R10 — fill profile gaps | #6 | Low–Medium | Scope this batch to: avatar upload wiring, language preference selector, "Delete my account" with confirm. Defer 2FA to a Task 16 of its own (security work needs more design) |
| **11** | R11 — Web Push manual verify | #4 | High | After R2 + restart queue worker, follow `docs/testing/TASK_8B_TESTING.md` Cases 1–6 with one demo user. Only marked done once Jonas sees a real toast |
| **12** | R13 — cross-cutting reactivity (Reverb broadcast + local Vue refetch tightening) | UX polish across pages | Medium | Layered last, once all functional bugs are stable. Makes the app feel real-time end-to-end: no F5 after a mutation in another tab; pending-N2 counter, kanban statut moves, sidebar badges, notification bell all live-update |

---

## Per-group implementation notes

### Group 1 — `TacheController::show` raw-model fix

**File:** `app/Http/Controllers/Api/TacheController.php:799` (line was already updated by Task 9 — verify the fix is in `show()` AND in `update`/`move`/`archive`/etc. which were patched in commit `bf0ee02`).
**Test:** Existing `tests/Browser/Validation/N0ValidationTest.php` already covers the dependent UI; add 1 assertion that `tache.my_result` is present in the API response.
**Commit:** `fix: close PROGRESSION bug #1 — TacheController::show + sibling endpoints all wrap with TacheResource`

### Group 2 — Self-notification guard

**File:** `app/Services/NotificationService.php` — add:

```php
public function sendUnlessSelf(User $notifiable, ?User $actor, Notification $notification): void
{
    if ($actor && $actor->id === $notifiable->id) {
        Log::debug('Self-notification suppressed', [...]);
        return;
    }
    $notifiable->notify($notification);
}
```

**Callers to refactor** (file:line from the explore report):
- `app/Models/TacheResultat.php:336` — `$validator->notify(new ValidationN1ConfirmeeNotification(...))` — actor is `$validator`, notifiable is `$validator` → always self. **Should be deleted, not just guarded.** The "confirmation" UX is the API's success response, not a notification.
- `app/Models/TacheResultat.php:388` — same problem, `ValidationN2ConfirmeeNotification` → delete.
- `app/Models/TacheResultat.php:490` — `RejetConfirmeNotification` → delete.
- All other notify() sites: route through `sendUnlessSelf()` (or keep direct when actor is genuinely a third party).

**Test:** `tests/Feature/NoSelfNotificationTest.php` (new) — assert that running each of N0/N1/N2 actions does not create a row in `notifications` where `notifiable_id === actor_id`.
**Commits:**
- `feat: NotificationService::sendUnlessSelf central guard`
- `fix: drop validator-self confirmation notifications + route remaining notify() through guard`

### Group 3 — Channels + push event types

**File:** `app/Services/NotificationService.php` — extend `wantsWebPush()` and `wantsEmail()`:

```php
return match ($eventType) {
    'renvoye_n0', 'transmis_auto', 'bypass', 'escalades_abusives',
    'unjustified_return_alert',
    'valide_n1', 'valide_n2', 'rejete_n1', 'rejete_n2',
    'en_validation_n2', 'validation_complete' => true,
    // ...
};
```

**Files to refactor** — each `via()` becomes `app(NotificationService::class)->channelsFor($notifiable, '<event_type>')`:
- `app/Notifications/ResultatValideN1Notification.php:29` → event `valide_n1`
- `app/Notifications/ResultatEnAttenteN2Notification.php:23` → event `en_validation_n2`
- `app/Notifications/ResultatValideN2Notification.php:27` → event `valide_n2`
- (`ValidationN1ConfirmeeNotification`, `ValidationN2ConfirmeeNotification` → deleted in Group 2)
- `app/Notifications/RejetConfirmeNotification.php` → deleted in Group 2
- `app/Notifications/ResultatRejeteNotification.php` → event `rejete_n1` (or `rejete_n2` via constructor arg)
- `app/Notifications/ResultatRejeteN2InfoNotification.php` → event `rejete_n2`

**Test:** extend `tests/Feature/WebPushSubscriptionTest.php` with: "after N1 validation, push channel is included for subscribed users with push_enabled".
**Commits:**
- `feat: add N1/N2 event types to NotificationService channel matchers`
- `fix: route 5 validation notifications through channelsFor() for push support`

### Group 4 — N1→N2 verification

Commit `451aa4d` (already in jonas via the Task 9 merge) added the statut transitions and the `ValidationStatutTransitionTest`. What remains:
- A Dusk test asserting the pending-N2 counter in the validator's sidebar / dashboard updates after N1 click. The view itself is `PendingValidations.vue` (Task 7) — add a Dusk case to `tests/Browser/Evaluation/PendingValidationsTest.php`.
- Manual verification per `docs/testing/TASK_9_TESTING.md` Test Case 2/3.

**Commit:** `test: dusk regression — pending N2 counter updates after N1 validation`

### Group 5 — Split the two "Validations" pages

Per user clarification, the split is by **audience**, not by feature. Rename routes + sidebar labels:

| Old route | New route | New sidebar label | Page (Vue) | Filter |
|---|---|---|---|---|
| `/taches/resultats/en-attente` | `/mes-validations` | "Mes validations en attente" (FR) / "My pending validations" | `ValidationResultats.vue` (keep) | `where user_id = auth()->id() and statut in ('en_validation_n1','en_validation_n2')` — results I submitted, awaiting someone else's action |
| `/validations/en-attente` | `/validations/a-traiter` | "Validations à traiter" (FR) / "Validations to action" | `evaluations/PendingValidations.vue` (keep) | existing query — results awaiting MY action as N1 or N2 |

- Add backend scoping helper on `EvaluationController::pendingValidationsDashboard` if not already present (a `?as=assignee|validator` query param, or two separate methods).
- Sidebar: two entries, gated by `useWorkspacePermissions.canViewPendingValidations` for the validator page; the assignee page shows for any authenticated user with `submit_result` perm.

**Commits:**
- `refactor: rename validation routes and sidebar labels by audience`
- `feat: scope each validation page to its audience query`

### Group 6 — Kanban subtask badge

**Files:**
- `resources/js/components/taches/KanbanBoard.vue`
- `resources/js/components/taches/KanbanBoardSimple.vue`
- `resources/js/components/taches/KanbanBoardPremium.vue`

Each renders task cards differently from the four card components that already have the badge. Add the same `<span v-if="tache.sous_taches_count > 0">` block (extract to a `SubtaskCountBadge.vue` small component while we're at it, since 4 + 3 = 7 callers will share it).

**Commits:**
- `refactor: extract SubtaskCountBadge component`
- `fix: render subtask count badge in all Kanban variants`

### Group 7 — Modal consolidation

**Keep:** `resources/js/components/taches/resultats/ResultatDetailModal.vue` (newest, 52 KB).
**Delete:**
- `resources/js/components/modals/ResultatDetailModal.vue`
- `resources/js/components/taches/ResultatDetailModal.vue`
- `resources/js/components/taches/ValidationModal.vue` (duplicate of `resultats/ValidationModal.vue`)

**Steps:**
1. `grep -rn "components/modals/ResultatDetailModal\|components/taches/ResultatDetailModal\b\|components/taches/ValidationModal\b" resources/js`
2. Rewrite imports to point at the canonical paths.
3. `git rm` the legacy files.
4. Run Dusk filter on the touched flows.

**Commit:** `chore: consolidate ResultatDetailModal + ValidationModal — single source under taches/resultats`

### Group 8 — Sidebar permission gating

**File:** `resources/js/components/layout/AppSidebar.vue`.

For every menu entry, add a `v-if`. Audit table (links to add):

| Entry | Helper | New helper needed? |
|---|---|---|
| Dashboard | always shown | no |
| Workspaces | `canViewWorkspaces` | check if exists |
| Projets > Tableau de bord | `superAdminOnly` (already there) | no |
| Projets > Mes projets | `isMember` | no |
| Activités > Toutes | `superAdminOnly` (add) | no |
| Tâches > Toutes | `canViewAllTasks` | yes (new) |
| Tâches > En tant que responsable | `isTaskResponsableSomewhere` | yes (new, query-backed) |
| Tâches > En attente de validation | `canSubmitResult` | yes (already in Permission.php as TACHES_SUBMIT_RESULT) |
| Évaluations > Tableau de bord | `canViewEvaluationScore` | exists |
| Évaluations > Validations à traiter | `canViewPendingValidations` | exists |
| Évaluations > Fiches | `canViewFicheEvaluation` | exists (Task 9) |
| Notifications | always | no |
| Mon Profil | always | no |

**Commits:**
- `feat: extend useWorkspacePermissions with canViewAllTasks + isTaskResponsableSomewhere`
- `fix: gate every sidebar entry via useWorkspacePermissions per role`

### Group 9 — Subtasks completeness

**Backend (`app/Http/Controllers/Api/SousTacheController.php`):**
- Add `removeIntervenant(SousTache $sousTache, User $user)` → DELETE `/sous-taches/{sousTache}/intervenants/{user}`.

**Frontend:**
- Add a "Responsable" select to `SousTacheForm.vue` (currently missing per the explore).
- New small component `SousTacheIntervenantsPanel.vue` rendered inside `SousTacheList.vue` per row, showing chips for each intervenant with an X to remove, and a search-as-you-type add button.
- Wire to `useSousTaches` composable; add `assignIntervenant` + `removeIntervenant` methods if not present.

**Test:**
- 1 feature test for `removeIntervenant` (auth, permission gate, unassignment effect).
- 1 Dusk test driving the add/remove flow.

**Commits:**
- `feat: SousTache.removeIntervenant endpoint + policy`
- `feat: SousTacheIntervenantsPanel UI for assign/remove intervenants`
- `feat: add responsable_id picker to SousTacheForm`

### Group 10 — Profile gaps (scoped)

**In scope for this batch:**
- Avatar upload — wire `EditProfileModal.vue` to POST a multipart `avatar` field; `UserController::updateProfile` already supports POST per the route audit, just confirm it accepts the file and stores under `storage/app/public/avatars/`.
- Language preference — small dropdown in `PreferencesSettings.vue` setting `users.preferred_locale` (column likely exists since notifications read it; if not, migration).
- "Delete my account" — confirm-dialog inside Security tab. Soft-delete via `User::delete()`, return 204, redirect to `/signin`.

**Out of scope (defer):** 2FA, session management, email change with verification. These deserve their own design + security review.

**Commits:**
- `feat: avatar upload in profile editor`
- `feat: preferred locale selector + persistence`
- `feat: account deletion (soft) with confirm dialog`

### Group 11 — Web Push verify

Not a code change unless Groups 2+3 don't fully restore it. After those land:

1. `php artisan migrate:fresh --seed` (gets the demo cadre subscribed via `NotificationDemoSeeder`).
2. `php artisan queue:work` in one terminal.
3. Open Chrome, sign in as cadre, allow push prompt (per `TASK_8B_TESTING.md` Case 1).
4. In another window as collaborateur, submit a result then have cadre return it.
5. Cadre should see the system notification.

If push still fails, debug `WebPushChannel::send` with a `Log::info` of the SDK response.

**Commit (if needed):** `fix: WebPushChannel — <root cause>`

### Group 12 — Cross-cutting reactivity (both server broadcasts AND client refetch tightening)

Two layers, applied as a final pass once Groups 1–11 are green so the underlying data flow is stable.

**Layer A — Server broadcasting (Reverb)**

Reuse the Reverb stack from Task 8 — `BROADCAST_DRIVER=reverb` and the private channel `App.Models.User.{id}` are already wired. We add **lightweight broadcast events** (separate from the existing notification broadcasts) on the workspace channel so anyone watching the list sees the update.

New events under `app/Events/Realtime/`:

| Event | Fires when | Channel | Payload (minimal) |
|---|---|---|---|
| `TacheStatutChanged` | `Tache::statut` changes (move, complete, archive) | `private-workspace.{id}` | `{tache_id, activite_id, old_statut, new_statut}` |
| `ResultatStatutChanged` | `TacheResultat::statut` transitions (submit, approve/return N0, validate/reject N1/N2) | `private-workspace.{id}` + `private-App.Models.User.{validator_id}` | `{resultat_id, tache_id, old_statut, new_statut, actor_id}` |
| `SousTacheChanged` | sous-tache create/update/delete/intervenant change | `private-task.{id}` | `{sous_tache_id, tache_id, action: 'created'\|'updated'\|'deleted'}` |
| `PendingValidationCountChanged` | any state change that affects the per-user N1/N2 pending count | `private-App.Models.User.{validator_id}` | `{n1_count, n2_count, urgent_count}` |

All events `implements ShouldBroadcastNow` (low payload, no queue). Each is fired from the **service layer** (TacheService, TacheResultatService, SousTacheService) immediately after the DB write, inside the same transaction so a failed write doesn't broadcast a phantom update.

Backend authorize callbacks added to `routes/channels.php` for `workspace.{id}` and `task.{id}` so only members can listen.

**Layer B — Client reactivity (Echo + composable refetch)**

New composable `resources/js/composables/useRealtimeRefresh.js` — thin wrapper that subscribes to a list of events and runs a refetch callback when any fires, with built-in debounce (100ms) to coalesce bursts.

Usage pattern (Vue page):
```js
const { fetchTaches } = useTaches()
useRealtimeRefresh({
  channels: [`workspace.${currentWorkspaceId}`],
  events: ['TacheStatutChanged', 'ResultatStatutChanged'],
  onFire: fetchTaches,
})
```

Pages that get the listener wired (the explicit list):
- `MesTaches.vue`, `TachesAssignees.vue`, `Activites.vue` → listen for `TacheStatutChanged` on workspace channel.
- `KanbanBoard*.vue` (all 3 variants) → same as above + `SousTacheChanged` on task channel.
- `TacheDetail.vue` → all 4 events scoped to the open task.
- `evaluations/PendingValidations.vue` (validator page) → `PendingValidationCountChanged` on user channel.
- `ValidationResultats.vue` (renamed `mes-validations`, assignee page) → `ResultatStatutChanged` on workspace channel filtered to own `user_id`.
- `Dashboard.vue` → stats badges refresh on `TacheStatutChanged` + `ResultatStatutChanged`.
- `AppSidebar.vue` → counters in submenus refresh on the relevant events (notification bell already does this via Task 8).

**Local-only reactivity tightening (no server change):**
- Audit every page that re-fetches on mount only. Add explicit refetch after own mutations (e.g., after `submitResult()`, after `moveTache()` — currently some pages relied on a manual F5).
- Replace ad-hoc `setInterval` polling (notification bell polls every 30s today per `NotificationMenu.vue`) with the broadcast listener. Keep a 60s fallback poll as safety net for users with broadcast disconnects.

**Tests:**
- `tests/Feature/Realtime/TacheStatutChangedEventTest.php` — assert event is dispatched after moveTache + payload is correct (use `Event::fake`).
- `tests/Feature/Realtime/PendingValidationCountChangedEventTest.php` — count delta is correct after validateByN1/N2.
- `tests/Browser/Realtime/CrossTabRealtimeTest.php` — Dusk with two browsers: window A submits a result, assert window B's pending-validations counter increments within 3s without F5.

**Commits:**
- `feat: realtime event classes (TacheStatutChanged, ResultatStatutChanged, SousTacheChanged, PendingValidationCountChanged)`
- `feat: broadcast realtime events from TacheService + TacheResultatService + SousTacheService`
- `feat: useRealtimeRefresh composable + Echo channel authorize callbacks`
- `feat: wire realtime listeners on every list / kanban / counter page`
- `refactor: replace NotificationMenu setInterval polling with broadcast listener (fallback 60s)`
- `test: feature + Dusk coverage for realtime events`

---

## Test & Dusk coverage (consolidated)

Per Guide 18 (Dusk mandatory per user-facing flow) every group ships **both** PHPUnit (backend behaviour) **and** Dusk (frontend wiring). Inline mentions in each group are restated here so the coverage commitment is auditable in one place.

### Backend (PHPUnit)

| File | New / extend | Asserts |
|---|---|---|
| `tests/Feature/TacheControllerShowResourceTest.php` | new (G1) | `GET /api/taches/{id}` body contains `data.my_result`, `data.validation_status`, `data.permissions`, `data.bypass.*` — all the resource-only fields that were absent when raw model was returned |
| `tests/Feature/NoSelfNotificationTest.php` | new (G2) | After each of: N0 approve/reject, N1 validate/reject, N2 validate/reject — no `notifications` row exists where `notifiable_id = actor_id`. Covers all 3 deleted self-confirm notifications + the guard helper |
| `tests/Unit/Services/NotificationServiceChannelsForTest.php` | new (G3) | `channelsFor()` returns `WebPushChannel::class` for the new event keys (`valide_n1`, `valide_n2`, `en_validation_n2`, `rejete_n1`, `rejete_n2`, `validation_complete`) when subscription + push_enabled are true; excludes it when they aren't. Table-driven (~12 cases) |
| `tests/Feature/WebPushSubscriptionTest.php` | extend (G3) | "after N1 validation, the cadre with a push subscription receives a row mentioning webpush channel"; "after self-validation (cadre is also intervenant), no notification is created at all" |
| `tests/Feature/ValidationStatutTransitionTest.php` | extend (G4) | Adds: pending-N2 dashboard query returns exactly 1 row after validateByN1 (already exists for the count; tighten by asserting the row's user_id) |
| `tests/Feature/Validation/AssigneePendingListTest.php` | new (G5) | New endpoint scoping `?as=assignee` returns only results where `user_id = auth()->id()` and statut in (`en_validation_n1`, `en_validation_n2`). 403 for users with no submitted results |
| `tests/Feature/Validation/ValidatorPendingListTest.php` | new (G5) | Existing `pendingValidationsDashboard` with `?as=validator` (or default) returns only results where the auth user is N1 (activite.responsable_id) or N2 (projet.responsable_id). Scope leak test: validator A doesn't see B's pending list |
| `tests/Feature/SousTacheRemoveIntervenantTest.php` | new (G9) | `DELETE /sous-taches/{id}/intervenants/{user}` — auth required, permission gate (only responsable or super_admin), 204 on success, intervenant gone from pivot, idempotent on already-removed |
| `tests/Feature/SousTacheResponsableFieldTest.php` | new (G9) | `POST /taches/{id}/sous-taches` accepts and persists `responsable_id`; validates that the user exists and is a member of the parent task's workspace |
| `tests/Feature/Profile/AvatarUploadTest.php` | new (G10) | Multipart POST `/api/profile` with `avatar` field stores file under `storage/app/public/avatars/`, returns new URL in response, rejects > 2 MB and non-image mimes |
| `tests/Feature/Profile/PreferredLocaleTest.php` | new (G10) | PUT `/api/profile` with `preferred_locale=en` persists; subsequent notifications use the new locale for Blade template selection |
| `tests/Feature/Profile/AccountDeletionTest.php` | new (G10) | POST `/api/profile/delete` (with `password` confirm) soft-deletes the user, revokes all Sanctum tokens, returns 204. Cannot be invoked by anyone else. |
| `tests/Feature/Realtime/TacheStatutChangedEventTest.php` | new (G12) | `Event::fake`, call `TacheService::moveTache`, assert `TacheStatutChanged` was dispatched on `private-workspace.{id}` with the expected payload |
| `tests/Feature/Realtime/PendingValidationCountChangedEventTest.php` | new (G12) | Count delta correctness: before/after `validateByN1` and `validateByN2`, the event payload's `n1_count`/`n2_count` matches a fresh COUNT(*) query |

**Coverage delta:** +11 new test files, +2 extended. ~60 new assertions. Suite expected to grow from 146 → ~205.

### Frontend (Dusk)

| File | New / extend | Covers |
|---|---|---|
| `tests/Browser/Validation/N0ValidationTest.php` | extend (G1, G4) | After my_result is exposed, the submit-result button is visible to the intervenant on the task detail page — was failing earlier because `tache.my_result` was null. Also: N1 click triggers a counter increment on the N2 validator's sidebar (covers G4 + R12) |
| `tests/Browser/Notifications/SelfNotificationGuardTest.php` | new (G2) | Sign in as cadre who is both N1 and the result author. Submit + self-validate. Assert the notification bell badge does NOT increment |
| `tests/Browser/Notifications/PushNotificationFlowTest.php` | new (G3, G11) | Sign in as cadre (with `NotificationDemoSeeder` push subscription). Collaborateur submits a result via the API (test helper). Assert that within 5s the `notifications` table has a row whose `data.dedup_key` contains `valide_n1` (push payload itself can't be observed in headless Chrome, but the channels presence proves the routing) |
| `tests/Browser/Validation/AssigneeMyValidationsTest.php` | new (G5) | Sign in as collaborateur with a submitted result awaiting N1. Visit `/mes-validations`. Assert: page lists their own result, no inline approve/reject button (read-only), correct page title |
| `tests/Browser/Validation/ValidatorPendingTest.php` | extend (G5) | Existing `PendingValidationsTest` extended: assert the renamed sidebar entry (`validations.a-traiter`) lands on the correct page; assert page does NOT list results the validator doesn't own |
| `tests/Browser/Kanban/SubtaskBadgeOnKanbanTest.php` | new (G6) | Sign in, visit kanban view, assert the `SubtaskCountBadge` is rendered on a card with subtasks AND absent on a card without — covers all 3 Kanban variants (KanbanBoard/Simple/Premium) via @dusk selector |
| `tests/Browser/Sidebar/SidebarPermissionGateTest.php` | new (G8) | Per role (super_admin, manager, cadre, collaborateur, stagiaire, observateur): sign in, assert which sidebar entries are visible vs absent. Table-driven via dataProvider; ~7 sign-in cycles. This is the canonical regression for the permission-gating refactor |
| `tests/Browser/Subtasks/SousTacheIntervenantsTest.php` | new (G9) | Open task detail, add intervenant to a subtask via the new `SousTacheIntervenantsPanel`, assert chip appears; click X, assert chip disappears |
| `tests/Browser/Subtasks/SousTacheFormResponsableTest.php` | new (G9) | Create new subtask via form, pick responsable from dropdown, assert it's persisted and shown on the row |
| `tests/Browser/Profile/AvatarUploadDuskTest.php` | new (G10) | Open EditProfileModal, click avatar zone, upload via Dusk `attach()` helper, assert the new URL appears in the header avatar |
| `tests/Browser/Profile/LanguagePreferenceTest.php` | new (G10) | Toggle FR→EN in PreferencesSettings, refresh, assert sidebar labels switched |
| `tests/Browser/Realtime/CrossTabRealtimeTest.php` | new (G12) | Two browsers via `$this->browse(function ($a, $b) {...})`. A submits a result; B (the N1 validator on another tab) sees the pending counter increment within 3s, no F5. Mirror test for kanban statut change |

**Coverage delta:** +10 new Dusk classes, +2 extended. ~30 new browser-driven assertions. Total Dusk suite expected to grow from 12 → ~24.

### Per-group Dusk run command (during dev)

Each group's Dusk class runs on its own before commit:
```bash
# Backend servers (started + stopped per memory feedback_stop_servers)
php artisan serve --host=127.0.0.1 --port=8000 &  SERVE_PID=$!
npm run dev &                                      VITE_PID=$!
php artisan queue:work --once &                    # Q3, Q11 only

php artisan dusk --filter=<GroupName>

kill $SERVE_PID $VITE_PID
```

### Final suite expectations

- PHPUnit: `php artisan test --compact` → ~205 passing (was 146).
- Dusk: `php artisan dusk` → ~24 classes passing (was 12). The 2 pre-existing `BypassTest` failures (logged before this batch) remain out of scope.
- pint: `vendor/bin/pint --test --format agent` → no diff.

---

## Files I expect to touch

(Citing what each group hits, for reviewer scan)

```
app/Http/Controllers/Api/TacheController.php       (G1)
app/Http/Controllers/Api/SousTacheController.php   (G9)
app/Http/Controllers/UserController.php            (G10)
app/Models/TacheResultat.php                       (G2, G3)
app/Services/NotificationService.php               (G2, G3)
app/Notifications/{Resultat,Validation,Rejet}*    (G2, G3) — delete 3, refactor ~5
app/Policies/SousTachePolicy.php                  (G9)
routes/api.php                                    (G5, G9)
resources/js/components/layout/AppSidebar.vue     (G5, G8)
resources/js/components/taches/{Kanban*.vue}      (G6)
resources/js/components/taches/resultats/         (G7 — keep)
resources/js/components/{modals,taches}/{ResultatDetailModal,ValidationModal}.vue  (G7 — delete)
resources/js/components/taches/SousTacheList.vue  (G9)
resources/js/components/taches/SousTacheForm.vue  (G9)
resources/js/components/taches/SousTacheIntervenantsPanel.vue (G9 — new)
resources/js/components/taches/SubtaskCountBadge.vue (G6 — new)
resources/js/components/profile/EditProfileModal.vue (G10)
resources/js/components/settings/PreferencesSettings.vue (G10)
resources/js/composables/useWorkspacePermissions.js (G8)
resources/js/router/index.ts                       (G5)
tests/Feature/TacheControllerShowResourceTest.php   (G1 — new)
tests/Feature/NoSelfNotificationTest.php            (G2 — new)
tests/Unit/Services/NotificationServiceChannelsForTest.php (G3 — new)
tests/Feature/WebPushSubscriptionTest.php           (G3 — extend)
tests/Feature/ValidationStatutTransitionTest.php    (G4 — extend)
tests/Feature/Validation/AssigneePendingListTest.php (G5 — new)
tests/Feature/Validation/ValidatorPendingListTest.php (G5 — new)
tests/Feature/SousTacheRemoveIntervenantTest.php    (G9 — new)
tests/Feature/SousTacheResponsableFieldTest.php     (G9 — new)
tests/Feature/Profile/AvatarUploadTest.php          (G10 — new)
tests/Feature/Profile/PreferredLocaleTest.php       (G10 — new)
tests/Feature/Profile/AccountDeletionTest.php       (G10 — new)
tests/Browser/Validation/N0ValidationTest.php       (G1, G4 — extend)
tests/Browser/Notifications/SelfNotificationGuardTest.php (G2 — new)
tests/Browser/Notifications/PushNotificationFlowTest.php  (G3, G11 — new)
tests/Browser/Validation/AssigneeMyValidationsTest.php (G5 — new)
tests/Browser/Evaluation/PendingValidationsTest.php (G5 — extend)
tests/Browser/Kanban/SubtaskBadgeOnKanbanTest.php   (G6 — new)
tests/Browser/Sidebar/SidebarPermissionGateTest.php (G8 — new)
tests/Browser/Subtasks/SousTacheIntervenantsTest.php (G9 — new)
tests/Browser/Subtasks/SousTacheFormResponsableTest.php (G9 — new)
tests/Browser/Profile/AvatarUploadDuskTest.php      (G10 — new)
tests/Browser/Profile/LanguagePreferenceTest.php    (G10 — new)
app/Events/Realtime/TacheStatutChanged.php          (G12 — new)
app/Events/Realtime/ResultatStatutChanged.php       (G12 — new)
app/Events/Realtime/SousTacheChanged.php            (G12 — new)
app/Events/Realtime/PendingValidationCountChanged.php (G12 — new)
app/Services/TacheService.php                       (G12 — broadcast on mutations)
app/Services/TacheResultatService.php               (G12 — broadcast on mutations)
app/Services/SousTacheService.php (or controller)   (G12 — broadcast on mutations)
routes/channels.php                                 (G12 — authorize workspace.{id} + task.{id})
resources/js/composables/useRealtimeRefresh.js      (G12 — new)
resources/js/pages/{MesTaches,Activites,Dashboard}.vue,
  KanbanBoard*.vue, TacheDetail.vue,
  evaluations/PendingValidations.vue,
  ValidationResultats.vue                          (G12 — wire listeners)
resources/js/components/layout/header/NotificationMenu.vue (G12 — replace setInterval)
tests/Feature/Realtime/TacheStatutChangedEventTest.php   (G12 — new)
tests/Feature/Realtime/PendingValidationCountChangedEventTest.php (G12 — new)
tests/Browser/Realtime/CrossTabRealtimeTest.php     (G12 — new)
docs/PROGRESSION.md                                (close PROGRESSION bug #1, append batch log)
docs/PERMISSIONS_MATRIX.md                         (G8 changelog row)
docs/testing/BUG_BATCH_TESTING.md                  (new — manual QA for the batch)
```

## Verification

Per-group on commit:
- `vendor/bin/pint --dirty --format agent` (no style violations).
- `php artisan test --compact --filter="<relevant>"` per group.
- `php artisan dusk --filter=<Class>` for the touched Dusk classes (run with `php artisan serve` + `npm run dev` running, killed after).

End-of-batch:
- Full `php artisan test --compact` — expect 146 → ~160 (+~15 new).
- Full `php artisan dusk` — expect green except the 2 pre-existing `BypassTest` failures already flagged.
- Manual walkthrough of `docs/testing/BUG_BATCH_TESTING.md` (to be written in the last commit).
- Update `docs/PROGRESSION.md` "Bugs Encountered" table closing #1 and any other bugs that get resolved.

## Risk + sequencing notes

- Groups 1, 2, 3 are foundational — every other group's test depends on `my_result` being present and notifications behaving sanely. **Do them first, in order.**
- Group 5 (rename) touches the router — Dusk tests using literal paths will break. Update Dusk filters in the same commit.
- Group 7 (modal consolidation) requires Vite hot-reload to pick up deleted files — restart `npm run dev` if needed.
- Group 10 is the most "feature-y" and least bug-y; if time runs short, ship Groups 1–9 first and split Group 10 into a `feature/profile-gaps` branch.

## Out of scope

- 2FA, email change with verification (Group 10 deferred).
- The 2 pre-existing `BypassTest` Dusk failures (separate issue, needs dev-server env alignment).
- Help Center implementation (deferred to `feature/v2-help-center` per `docs/extended-features/HELP_CENTER_PLAN.md`).

## Estimated effort

Per group, including its PHPUnit + Dusk coverage (tests are not a separate phase):

| Group | Fix | Tests (PHPUnit + Dusk) | Total |
|---|---|---|---|
| G1 | 15 min | 30 min | 45 min |
| G2 | 1.5 h | 1 h | 2.5 h |
| G3 | 1.5 h | 1.5 h | 3 h |
| G4 | 45 min | 30 min | 1.25 h |
| G5 | 1.5 h | 1.5 h | 3 h |
| G6 | 30 min | 30 min | 1 h |
| G7 | 45 min | 15 min (re-run only) | 1 h |
| G8 | 2 h | 1.5 h | 3.5 h |
| G9 | 3 h | 1.5 h | 4.5 h |
| G10 | 2 h | 1.5 h | 3.5 h |
| G11 | 30 min (manual verify only) | covered in G3 push test | 30 min |
| G12 | 3 h (events + listeners + composable) | 1.5 h (2 feature + 1 Dusk cross-tab) | 4.5 h |
| Docs + BUG_BATCH_TESTING.md | 1 h | — | 1 h |
| **Total** | **~18 h** | **~11.5 h** | **~29.5 h** |

Split across ~5–6 working sessions with a commit per group + one commit per its test addition (~35 commits total on `fix/bug-batch`).
