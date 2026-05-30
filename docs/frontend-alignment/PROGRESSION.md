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
| `pages/Documents.vue` | ✅ | 2026-05-30 | Clean. `GET /documents/stats` response shape matches `DocumentController::globalStats` exactly. Tab system + browser/list components correctly wired. |
| `pages/documents/WorkspaceDocuments.vue` | ✅ | 2026-05-30 | **Bug fixed:** malformed class attribute (stray `>` + missing `bg-white` / `dark:bg-white/[0.03]`) on the workspace info card. Endpoint + permission gating clean. |
| `pages/documents/ProjetDocuments.vue` | ✅ | 2026-05-30 | **Bug fixed:** `projet.responsable?.name` → `projet.responsable?.nom` (UserResource uses `nom`, never `name`). The previous code silently rendered an empty span. |
| `pages/documents/ActiviteDocuments.vue` | ✅ | 2026-05-30 | Clean. |
| `pages/documents/TacheDocuments.vue` | ✅ | 2026-05-30 | Clean. |
| `pages/documents/Index.vue` | 🗑 | 2026-05-30 | **Deleted:** 285-line half-finished draft, no router or import references. Empty handler stubs, broken DocumentViewerModal import, wrong breadcrumb title ("Gestion des Activités"). Documents.vue covers the same surface. |
| Share-by-email flow | ✅ | 2026-05-30 | **Gap closed:** `POST /documents/{id}/share-by-email` had zero callers in the frontend. Extended DocumentShareModal so that when the search query is a valid email matching no registered user, a "Envoyer une invitation par email" CTA appears and hits the endpoint. Existing PHPUnit coverage in `tests/Feature/Task12/DocumentManagementTest::share_by_email_sends_notification_to_external_email` still applies. |

**Style note (not fixed):** ProjetDocuments / ActiviteDocuments / TacheDocuments each hardcode upload-permission logic. The `useProjet/useActivite/useTachePermissions` composables don't yet expose a `canUploadDocuments` helper — adding one would centralize this. Flagged for a future composable pass.

### Evaluations (Tasks 7 / 9 / 10 / 16)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/evaluations/EvaluationDashboard.vue` | ✅ | 2026-05-30 | **Bugs fixed:** Used raw `axios` instead of the project's `api` wrapper — no automatic Bearer-token attachment, only worked because Sanctum stateful session was present. Also had a dead `useAuthStore` import. Switched to `api`, removed dead import. Response dereferences (`top_performers`, `scores`, `alerts.escalades_abusives`, `alerts.high_inaction_rate`) all match `EvaluationController::evaluationDashboard`. |
| `pages/evaluations/AgentSheet.vue` | ✅ | 2026-05-30 | **Bug fixed:** `statutBadge()` only mapped 3 of 7 `TacheStatut` cases — same pattern as Phase 1.1. Extended to all 7 (`en_attente`, `en_retard`, `a_refaire`, `annule`). Endpoint + response shapes (`sheet.user`, `score_global`, `indicators`, `criteria`, sections) match `EvaluationController::agentSheet` / `agentSheetSections`. |
| `pages/evaluations/PendingValidations.vue` + `PendingRow.vue` | ✅ | 2026-05-30 | Clean. `GET /evaluations/validations/en-attente` reads `pending_n1` / `pending_n2` / `counts` from `EvaluationController::pendingValidationsDashboard`. |
| Agent sheet PDF export | ✅ | 2026-05-30 | Fix landed alongside the Tasks batch (see below). `window.open` → `api.get(..., { responseType: 'blob' })` + synthetic anchor download. |
| Workspace taches Excel export | ✅ | 2026-05-30 | Same blob-download pattern as the PDF export. |

### Tasks (Tasks 11 / 15 / 16)

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Taches.vue` | ✅ | 2026-05-30 | Clean. Uses `api` wrapper, calls match `/activites/mes-activites`, `/taches`, `/taches/:id`, `/taches/:id/unarchive`, `/taches/:id/validate-n1`/`-n2`, `/taches/en-attente`. Console.log debug noise on a few lines — out of scope. |
| `components/taches/TacheTable.vue` | ✅ | 2026-05-30 | Already audited and fixed in Phase 1.1 (`en_attente`/`annule` badges) + Phase 1.3 (`can_inline_edit` gating). No further issues. |
| `pages/workspace/WorkspaceTaches.vue` | ✅ | 2026-05-30 | **3 bugs fixed:** (a) raw `axios` → `api` (Bearer-token interceptor); (b) `projets` ref declared and used by the filter dropdown but never populated — added `loadProjets()` calling `/projets/list/all`; (c) statut helpers missing `en_attente`/`annule` (Phase 1.1 pattern) — added. (d) Excel export switched from `window.open` to blob-download via `api` so the Bearer token is attached. |
| `pages/evaluations/AgentSheet.vue` (export) | ✅ | 2026-05-30 | Cross-cutting fix from the previous batch's flag: PDF export switched from `window.open` to blob-download via `api`. |
| `components/taches/TacheCreateWizard.vue` | ✅ | 2026-05-30 | Clean. Uses `api`, validates each step, FormData submission matches `TacheController::store`. |

### Validation circuit (Tasks 5 – 8)

| Page | Status | Date | Findings |
|---|---|---|---|
| `components/taches/resultats/ResultatDetailModal.vue` | ✅ | 2026-05-30 | Already touched in Phase 1.4 (`taille_fichier` cleanup). No additional issues found. |
| Validation actions (`pages/ValidationResultats.vue` + `ValidationModal.vue`) | ✅ | 2026-05-30 | **Bug fixed:** reject endpoint path was `/evaluations/${id}/reject` (404) — backend route is `/evaluations/resultats/${id}/reject`. The full reject flow was silently failing on the wrong URL. Validate N1/N2 paths + history endpoint confirmed correct. |
| Bypass UI | ✅ | 2026-05-30 | Backend bypass surface exposed in `TacheResultatResource` (`bypass` block, `can_activer_bypass`, `audit_logs`) — handled inline by ResultatCard / DetailModal. No new issues identified beyond the cross-tab cleanup. |
| `components/layout/header/NotificationMenu.vue` + live-toast | ✅ | 2026-05-30 | Clean. `useNotifications` composable for fetch/mark/delete, `useRealtimeRefresh` for echo+poll fallback, `safeFetchUnread()` guards unauthenticated polling. Calls `/tache-resultats/{id}` for the deep-link modal — endpoint verified. |
| `components/taches/ResultatForm copy.vue` | 🗑 | 2026-05-30 | **Deleted:** orphan duplicate of `ResultatForm.vue`, never imported. Same dead-code pattern as Index.vue from the Documents batch. |

**Out-of-scope finding noted, not fixed:** `pages/TachesParUtilisateur.vue` mounts `ValidationModal` with props (`:tache`/`:user`/`@validated`) that don't match the modal's actual contract (`:resultat`/`:action`/`:level`/`@confirmed`). The modal would never receive its required `resultat` prop. Likely an abandoned alternate flow — outside this batch's scope, flagged for a future pass.

### Projects / Activities

| Page | Status | Date | Findings |
|---|---|---|---|
| `pages/Projets.vue` | ✅ | 2026-05-30 | **Bug fixed:** `createActivity` / `viewActivity` were TODO console.log stubs — now route to `Activites` / `activites.show`. Tab system + child components clean. |
| `pages/Activites.vue` | ✅ | 2026-05-30 | Clean (15-line wrapper around `ActiviteList`). |
| `pages/projets/Create.vue` | ✅ | 2026-05-30 | **Rebuilt:** was a stub literally rendering "Projets archivés". Now a thin AdminLayout wrapper around the existing `ProjetForm` component with router-aware close/save handlers. Reaches `POST /projets`. |
| `pages/projets/Edit.vue` | ✅ | 2026-05-30 | **Rebuilt:** same stub problem as Create.vue. Now loads the projet via `useProjets.fetchProjet` and mounts `ProjetForm` with the loaded entity. Reaches `PUT /projets/{id}`. |
| `pages/projets/Show.vue` | ✅ | 2026-05-30 | **Bug fixed:** `createActivity` was a TODO `console.log` stub — now navigates to the Activites list. `viewActivity` was already navigating but had leftover TODO/console — cleaned. |
| `pages/projets/MyProjects.vue` | ✅ | 2026-05-30 | Clean. Removed leftover `console.log(projet)` debug. All other API calls go through composables. |
| `pages/projets/Archived.vue` | ✅ | 2026-05-30 | Clean (uses composables, no direct API calls). |
| `pages/ActiviteDetail.vue` | ✅ | 2026-05-30 | **Critical bug fixed:** `handleValidation` called `POST /taches/{id}/validate-n{1,2}` (404 — these routes were removed in the Phase 5/8 refactor). Now picks pending resultats from `tache.all_results` and validates each via the resultat-level endpoint `POST /evaluations/resultats-individuels/{resultat}/validate-n{1,2}` (in parallel). The `@validate-n1`/`@validate-n2` bindings now capture the commentaire arg from QuickActionsPanel that was previously dropped. |
| `pages/Taches.vue` | ✅ | 2026-05-30 | Same critical bug as ActiviteDetail — `handleValidateN1/N2` were calling the dead `/taches/{id}/validate-n{1,2}` routes. Now uses a shared `validateAllPendingForLevel` helper that hits the resultat-level endpoint. |

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
| 2026-05-30 | Phase 2 — Documents | Audited Documents.vue + 4 hierarchical doc pages + share modal. **WorkspaceDocuments bug:** malformed class attribute on the info card — fixed. **ProjetDocuments bug:** `responsable?.name` (always empty) → `responsable?.nom` — fixed. **Share-by-email gap:** backend endpoint unreachable — extended DocumentShareModal with an email-CTA branch when the search finds no user. **Dead code:** deleted 285-line orphan `pages/documents/Index.vue`. Pint + build clean. |
| 2026-05-30 | Phase 2 — Evaluations | Audited EvaluationDashboard + AgentSheet + PendingValidations. **EvaluationDashboard:** used raw `axios` (no Bearer-token interceptor) + dead `useAuthStore` import — switched to `api` wrapper and removed dead import. **AgentSheet:** `statutBadge()` only covered 3 of 7 `TacheStatut` cases — extended (Phase 1.1 pattern). **Flagged not fixed:** AgentSheet PDF export uses `window.open` which can't attach Sanctum Bearer tokens; same pattern likely in Excel export — to revisit together. PendingValidations + PendingRow clean. Build clean. |
| 2026-05-30 | Phase 2 — Tasks | Audited Taches.vue + TacheTable + WorkspaceTaches + TacheCreateWizard. **WorkspaceTaches bugs:** raw `axios` → `api` wrapper; `projets` filter dropdown was never populated (dead `v-for`) — added `loadProjets()` calling `/projets/list/all`; statut helpers missing `en_attente`/`annule` — extended. **Cross-cutting export fix (also landed here):** AgentSheet PDF + Workspace taches Excel exports switched from `window.open` to blob-download via `api` so the Bearer token is attached. Other pages clean. Build clean. |
| 2026-05-30 | Phase 2 — Validation circuit | Audited ValidationResultats + ValidationModal + NotificationMenu + ResultatDetailModal. **Critical bug fixed:** reject endpoint path was `/evaluations/${id}/reject` (404) — backend route is `/evaluations/resultats/${id}/reject`; the whole reject flow was silently failing. **Dead code:** deleted orphan `ResultatForm copy.vue`. NotificationMenu + ResultatDetailModal clean. **Flagged not fixed:** `TachesParUtilisateur.vue` uses ValidationModal with the wrong props/events shape — outside this batch's scope. Build clean. |
| 2026-05-30 | Phase 2 — Projects / Activities | Audited Projets, Activites, ActiviteDetail + 5 projets/* pages. **Critical bug fixed:** ActiviteDetail + Taches were calling the dead `POST /taches/{id}/validate-n{1,2}` routes (removed in Phase 5/8 refactor) — now pick pending resultats from `tache.all_results` and validate via the resultat-level endpoint in parallel. **Rebuilt:** Create.vue + Edit.vue placeholder stubs (literally rendered "Projets archivés") replaced with thin wrappers around the existing `ProjetForm` modal, reaching POST /projets and PUT /projets/{id}. **Bugs fixed:** TODO console.log stubs for `createActivity` / `viewActivity` in Projets.vue + Show.vue — now navigate to Activites / activites.show. Cleaned `console.log(projet)` debug in MyProjects. Build clean. |
