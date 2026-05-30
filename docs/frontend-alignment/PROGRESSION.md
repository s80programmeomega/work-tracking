# Frontend Alignment — Progression

Tracks progress of the work laid out in [PLAN.md](PLAN.md). One row per fix or audited page. Update **at the end of every working pass** — not just at the end of the project.

**Status legend:** ☐ todo · ◐ in progress · ✅ done · ⏭ deferred · ❌ blocked

---

## Phase 1 — Concrete fixes

| # | Fix | Files | Status | Date | Notes |
|---|---|---|---|---|---|
| 1.1.a | Add `en_attente` + `annule` to `statutLabel` and `statutClasses` | [TacheTable.vue:242-256](../../resources/js/components/taches/TacheTable.vue#L242-L256) | ✅ | 2026-05-29 | Amber + slate badges. |
| 1.1.b | Verify + complete statut helpers in SousTacheList | [SousTacheList.vue:566-590](../../resources/js/components/taches/SousTacheList.vue#L566-L590) | ✅ | 2026-05-29 | Added `en_attente`. `annule` already present. |
| 1.1.c | Verify + complete statut helpers in ActiviteDetail | [ActiviteDetail.vue:914-935](../../resources/js/pages/ActiviteDetail.vue#L914-L935) | ✅ | 2026-05-29 | Added `en_retard`, `a_refaire`, `annule`. |
| 1.1.d | Grep for other statut renderers missing the two states | `resources/js/**/*.vue` | ✅ | 2026-05-29 | StatusBadge, KanbanBoardSimple, TacheCardResponsable, tacheStore — all intentionally scoped to 3 statuses (pivot/kanban). No fixes needed. |
| 1.2.a | Add `document_deleted` + `document_shared` to icon map | [useNotifications.js:200-228](../../resources/js/composables/useNotifications.js#L200-L228) | ✅ | 2026-05-29 | `fa-trash` + `fa-share-alt`. |
| 1.2.b | Add `document_deleted` + `document_shared` to color map | [useNotifications.js:230-282](../../resources/js/composables/useNotifications.js#L230-L282) | ✅ | 2026-05-29 | `red` + `cyan`. |
| 1.2.c | Add 3 explicit `v-else-if` branches for document notifications | [NotificationDetailModal.vue:455-545](../../resources/js/components/layout/header/NotificationDetailModal.vue#L455-L545) | ✅ | 2026-05-29 | Cards for uploaded / deleted / shared with "Voir le document" CTA. |
| 1.3.a | Verify `can_inline_edit` is exposed on TacheResource (add if missing) | [TacheResource.php:421](../../app/Http/Resources/TacheResource.php#L421) + [TacheController.php:779](../../app/Http/Controllers/Api/TacheController.php#L779) | ✅ | 2026-05-29 | Added to both `data.permissions` and `additional_info.permissions`. |
| 1.3.b | Wire `can_inline_edit` into TacheTable click guards | [TacheTable.vue:74-129](../../resources/js/components/taches/TacheTable.vue#L74-L129) | ✅ | 2026-05-29 | 3 click guards: statut, priorité, échéance. |
| 1.3.c | Wire `can_inline_edit` into TacheDetail header + SousTacheList prop | [TacheDetail.vue:64-159, 209](../../resources/js/pages/taches/TacheDetail.vue#L64-L209) + [TacheDetailModal.vue:271, 454](../../resources/js/components/taches/TacheDetailModal.vue#L271) | ✅ | 2026-05-29 | All inline-edit guards swapped + startHeaderEdit gate + sub-task list bindings (hardcoded `true` replaced). Also expanded TacheDetail statut dropdown to 5 options. |
| 1.3.d | Wire `can_inline_edit` into DetailedTaskView | [DetailedTaskView.vue](../../resources/js/components/taches/DetailedTaskView.vue) | ✅ | 2026-05-29 | All 14 inline-edit guards swapped via `replace_all`. startEdit guard already covered. |
| 1.3.e | ActiviteDetail — no inline-edit affordances (full-modal edit only) | [ActiviteDetail.vue:366, 1044](../../resources/js/pages/ActiviteDetail.vue#L366) | ✅ | 2026-05-29 | The `can_edit` references here open the full edit modal — correctly kept. |
| 1.4.a | Drop dead `taille_fichier` fallback (list view) | [ResultatDetailModal.vue:297](../../resources/js/components/taches/resultats/ResultatDetailModal.vue#L297) | ✅ | 2026-05-29 | |
| 1.4.b | Drop dead `taille_fichier` fallback (preview pane) | [ResultatDetailModal.vue:670](../../resources/js/components/taches/resultats/ResultatDetailModal.vue#L670) | ✅ | 2026-05-29 | |

---

## Phase 2 — Page-by-page functional audit

For each page: every axios call hits an existing route with matching verb; payload keys match the FormRequest; response dereferences exist in the Resource; visible UI is gated on the right permission; error and loading states render.

### Admin section (Task 14)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/admin/AdminDashboard.vue` | ✅ | 2026-05-30 | Clean. All response dereferences match `AdminController::stats`; `ws.subscription.*` in recent-workspaces matches `SubscriptionService::summary()`. |
| `pages/admin/AdminWorkspaces.vue` | ✅ | 2026-05-30 | **Bug fixed:** Extend-Trial modal pre-filled `days` with `remaining_trial_days` but the endpoint overwrites total `trial_duration_days` — saving without editing would shrink the trial. Now pre-fills with `trial_duration_days`. |
| `pages/admin/AdminUsers.vue` | ✅ | 2026-05-30 | **Gap fixed:** Backend `PATCH /admin/users/{user}/role` had no UI. Added Change-role modal (role dropdown + is_super_admin toggle) matching the extend/suspend pattern. |
| `pages/admin/AdminRoles.vue` | ✅ | 2026-05-30 | Clean. GET /admin/roles + PATCH /admin/roles/{role}/permissions both correctly wired with matching payload + response shapes. |

**Cross-cutting note (not fixed in this pass):** All four admin pages hardcode English text despite `lang/{fr,en}/admin.php` existing with the right keys. Inconsistent with the rest of the app's i18n pattern — to be revisited in a dedicated translation pass.

### Subscription / trial (Task 13)

| Page | Status | Date | Findings |
|---|---|---|---|
| `components/common/TrialBanner.vue` | ✅ | 2026-05-30 | **Bug fixed:** dismiss button used `subscription.trial.expiring_soon` (the warning sentence) as its label — confusing CTA. Replaced with a proper × icon + `subscription.trial.dismiss` aria-label/title. New `dismiss` key in `lang/{fr,en}/subscription.php`. |
| `pages/workspaces/Subscription.vue` | ✅ | 2026-05-30 | Clean. All `summary.*` dereferences match `SubscriptionService::summary()`. **Gap noted (not fixed):** `PATCH /workspaces/{id}/subscription` (subscription_mode trial→paid) has no UI — likely intentional pending payment integration. |

### Documents (Task 12)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Documents.vue` | ☐ | | |
| `pages/workspace/WorkspaceDocuments.vue` | ☐ | | |
| `pages/documents/*` | ☐ | | |
| Share-by-email modal | ☐ | | |

### Evaluations (Tasks 7 / 9 / 10 / 16)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/evaluations/EvaluationDashboard.vue` | ☐ | | |
| `pages/evaluations/AgentSheet.vue` | ☐ | | |
| `pages/evaluations/PendingValidations.vue` | ☐ | | |
| Agent sheet PDF export | ☐ | | |
| Workspace taches Excel export | ☐ | | |

### Tasks (Tasks 11 / 15 / 16)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Taches.vue` | ☐ | | |
| `components/taches/TacheTable.vue` | ☐ | | |
| `pages/workspace/WorkspaceTaches.vue` | ☐ | | |
| Task creation wizard | ☐ | | |

### Validation circuit (Tasks 5 – 8)

| Page | Status | Date | Findings |
|---|---|---|---|
| `components/taches/resultats/ResultatDetailModal.vue` | ☐ | | |
| Validation actions (approve N0 / renvoyer N0 / N1 / N2) | ☐ | | |
| Bypass UI | ☐ | | |
| `components/layout/header/NotificationMenu.vue` + live-toast | ☐ | | |

### Projects / Activities

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Projets.vue` | ☐ | | |
| `pages/projets/*` | ☐ | | |
| `pages/Activites.vue` | ☐ | | |
| `pages/ActiviteDetail.vue` | ☐ | | |

### Auth

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Auth/Login.vue` | ☐ | | |
| `pages/Auth/Register.vue` | ☐ | | |
| `pages/AcceptInvitation.vue` | ☐ | | |
| `pages/AcceptProjetInvitation.vue` | ☐ | | |
| Language switcher | ☐ | | |

### Profile / Settings

| Page | Status | Date | Findings |
|---|---|---|---|
| Profile edit | ☐ | | |
| `components/profile/ChangePasswordModal.vue` | ☐ | | |
| `pages/NotificationPreferences.vue` | ☐ | | |

---

## Phase 3 — Responsiveness sweep

For each priority-page, walk Chrome DevTools at 375 (sm) / 768 (md) / 1024 (lg). Capture screenshot under `docs/frontend-alignment/responsive/<page-name>.png`.

| Page | 375px | 768px | 1024px | Date | Notes |
|---|---|---|---|---|---|
| AdminDashboard | ☐ | ☐ | ☐ | | |
| AdminWorkspaces | ☐ | ☐ | ☐ | | |
| AdminUsers | ☐ | ☐ | ☐ | | |
| TrialBanner + workspace settings | ☐ | ☐ | ☐ | | |
| Documents | ☐ | ☐ | ☐ | | |
| WorkspaceDocuments | ☐ | ☐ | ☐ | | |
| EvaluationDashboard | ☐ | ☐ | ☐ | | |
| AgentSheet | ☐ | ☐ | ☐ | | |
| PendingValidations | ☐ | ☐ | ☐ | | |
| Taches (table + kanban + liste) | ☐ | ☐ | ☐ | | |
| WorkspaceTaches | ☐ | ☐ | ☐ | | |
| Task creation wizard | ☐ | ☐ | ☐ | | |
| ResultatDetailModal | ☐ | ☐ | ☐ | | |
| Projets / ProjetDetail | ☐ | ☐ | ☐ | | |
| Activites / ActiviteDetail | ☐ | ☐ | ☐ | | |
| Auth (login / register) | ☐ | ☐ | ☐ | | |
| AcceptInvitation flows | ☐ | ☐ | ☐ | | |
| Profile edit | ☐ | ☐ | ☐ | | |
| NotificationPreferences | ☐ | ☐ | ☐ | | |
| Sidebar drawer at sm | ☐ | — | — | | |

---

## Phase 4 — Test coverage

| # | Test | Type | Status | Date | Notes |
|---|---|---|---|---|---|
| 4.1 | `document_uploaded` data shape | PHPUnit Feature | ✅ | 2026-05-29 | [DocumentNotificationShapeTest.php](../../tests/Feature/FrontendAlignment/DocumentNotificationShapeTest.php) |
| 4.2 | `document_deleted` data shape | PHPUnit Feature | ✅ | 2026-05-29 | idem |
| 4.3 | `document_shared` data shape (both notifiers) | PHPUnit Feature | ✅ | 2026-05-29 | Covers both `DocumentSharedNotification` and `DocumentPermissionGrantedNotification` — both emit `type: 'document_shared'`. |
| 4.4 | `TacheResource` + `TacheController@show` expose `can_inline_edit` | PHPUnit Feature | ✅ | 2026-05-29 | [TacheResourceCanInlineEditTest.php](../../tests/Feature/FrontendAlignment/TacheResourceCanInlineEditTest.php) — 2 tests (owner true / stranger false-or-403). |
| 4.5 | Statut badge renders `en_attente`/`annule` correctly | Dusk | ⏭ | | Deferred to Phase 2 page sweep. |
| 4.6 | Notification bell shows `document_shared` toast | Dusk | ⏭ | | Deferred to Phase 2 page sweep. |
| 4.7 | Inline edit dropdown gated on `can_inline_edit` (stagiaire = no, cadre = yes) | Dusk | ⏭ | | Deferred to Phase 2 page sweep. Contract is covered by PHPUnit 4.4. |
| 4.8 | `updateUserRole` endpoint: 403 regular / 200 super_admin / 422 invalid role | PHPUnit Feature | ✅ | 2026-05-30 | 3 tests appended to `tests/Feature/Admin/PlatformDashboardTest.php`. |
| 4.9+ | Phase 2 page Dusk happy paths (one row per page that got a fix) | Dusk | ☐ | | Pending — AdminWorkspaces extend-trial regression + AdminUsers role-change happy path. |

---

## Session log

| Date | Phase / item | Outcome |
|---|---|---|
| 2026-05-29 | Planning | Plan + progression docs created under `docs/frontend-alignment/`. Backend route inventory + frontend audit complete. Awaiting approval to start Phase 1. |
| 2026-05-29 | Phase 1 complete | Branch `feature/frontend-alignment-phase-1` cut from `jonas`. All 14 Phase 1 fixes shipped + 6 new PHPUnit feature tests in `tests/Feature/FrontendAlignment/`. Suite: 651 tests passing (was 627 + 6 new + recent additions). Pint clean. PHPStan level-5 clean. `npm run build` clean. Dusk happy-paths deferred to Phase 2 per-page sweep (the underlying contracts are covered by PHPUnit; UI surfaces will get Dusk as each page is audited). |
| 2026-05-30 | Phase 2 — Admin section | Audited 4 admin pages. AdminDashboard + AdminRoles clean. **AdminWorkspaces bug:** extend-trial modal pre-filled `remaining_trial_days` instead of `trial_duration_days` — could silently shrink the trial. Fixed. **AdminUsers gap:** Change-role modal added to reach `PATCH /admin/users/{user}/role` (was unreachable from the UI). 3 new PHPUnit tests for the role endpoint (403 / 200 / 422). All gates green: pint, 14/14 PlatformDashboardTest, PHPStan level-5, `npm run build`. Dusk happy-paths still deferred. |
| 2026-05-30 | Phase 2 — Subscription / trial | Audited TrialBanner + Subscription page. **TrialBanner bug:** dismiss button used the warning sentence as its label — fixed (× icon + new `dismiss` i18n key). Subscription page clean. Gap noted: `PATCH /workspaces/{id}/subscription` (trial→paid) has no UI, likely intentional. Pint + build clean. |
