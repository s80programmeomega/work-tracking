# Frontend ↔ Backend Alignment + 100% Functional & Responsive — Plan

## Context

The backend just went through a massive cleanup cycle (Tasks 12–16 + PHPStan level-5 fix-up + Scribe API docs). The frontend was modified in lockstep for most of those tasks, but a parallel audit across `resources/js/` revealed several concrete misalignments and one design-intent gap.

The user wants the whole frontend to be **100% functional** (every page calls the right endpoint, reads the right fields, gates the right action) and **100% responsive** (works at every breakpoint).

This plan delivers that in four phases — Phase 1 is concrete, surgical fixes for issues already discovered; Phases 2–4 are a systematic sweep so nothing falls through the cracks.

Progress for each phase is tracked in [PROGRESSION.md](PROGRESSION.md).

---

## Expert recommendations on the two open design questions

**1. `en_attente` / `annule` — display-only, not inline-editable.**
- `EN_ATTENTE` is a *system-driven* state (set when a result is submitted and waiting on N0); letting a user set it from a dropdown skips the proper submission flow.
- `ANNULE` is a *terminal* state; it deserves a confirmation flow, not an inadvertent dropdown click.
- Backend `PATCH /api/taches/{id}` validation already blocks these two values (see [TacheController.php:853](../../app/Http/Controllers/Api/TacheController.php#L853)) — the frontend dropdown is correctly restricted today. The **bug** is that the badge label/color helpers don't know about them, so a task arriving from the API in `en_attente`/`annule` renders the raw lowercase string with a gray badge. Fix the renderers, leave the dropdown alone.

**2. `taches.inline_edit` permission — wire it in, don't drop it.**
- Task 11 introduced this permission deliberately to separate "can use the modal/full edit form" (`can_edit`) from "can flip statut/priorité from a dropdown" (`inline_edit`). The intent is that a `stagiaire` may submit a result but should not be able to silently bump a task to `termine` from a list view.
- It is currently dead code: defined in `Permission.php`, mirrored in the composables, but every inline-edit interaction checks the generic `can_edit`. That is a silent permission bypass — exactly the kind of regression Guide 0 / Guide 14 are designed to prevent.
- Wiring it in matches the original design and closes the gap. Cost is small: ~6 component touch-ups.

---

## Phase 1 — Concrete fixes found by the audit

### 1.1 Complete the `TacheStatut` enum in frontend helpers
Backend enum has 7 cases (`a_faire`, `en_cours`, `termine`, `en_retard`, `a_refaire`, `en_attente`, `annule`). Status renderers in the frontend only cover 5. Add `en_attente` and `annule` labels + badge classes — keep the dropdowns restricted to the 5 editable ones.

- [resources/js/components/taches/TacheTable.vue:242-253](../../resources/js/components/taches/TacheTable.vue#L242-L253) — add `en_attente: 'En attente'` (amber) + `annule: 'Annulé'` (slate) to `statutLabel` and `statutClasses`.
- [resources/js/components/taches/SousTacheList.vue](../../resources/js/components/taches/SousTacheList.vue) — already maps `annule`; verify `en_attente` and add if missing.
- [resources/js/pages/ActiviteDetail.vue](../../resources/js/pages/ActiviteDetail.vue) — already maps `en_attente`; verify `annule` and add if missing.
- Grep all `.vue` files for `a_faire.*en_cours.*termine` mapping blocks to find any other renderer missing the two states.

### 1.2 Map the 3 new document notification types
Backend emits three distinct `type` strings (verified in `app/Notifications/Document*.php`): `document_uploaded`, `document_deleted`, `document_shared` (the last is used by both `DocumentSharedNotification` and `DocumentPermissionGrantedNotification`).

- [resources/js/composables/useNotifications.js:200-226](../../resources/js/composables/useNotifications.js#L200-L226) — add `document_deleted: 'fa-trash'` and `document_shared: 'fa-share-alt'` to the icon map.
- [resources/js/composables/useNotifications.js:228-277](../../resources/js/composables/useNotifications.js#L228-L277) — add `document_deleted: 'red'` and `document_shared: 'cyan'` to the color map.
- [resources/js/components/layout/header/NotificationDetailModal.vue](../../resources/js/components/layout/header/NotificationDetailModal.vue) — add 3 dedicated `v-else-if` branches for `document_uploaded`, `document_deleted`, `document_shared`. Each should surface `data.document_nom` (and `data.uploaded_by` for uploads/shares) with a "Voir le document" CTA that routes to `/documents` (or the document's parent project when `data.documentable_type === 'projet'`).

### 1.3 Wire `canInlineEditTache` into inline-edit affordances
Replace `tache.permissions?.can_edit` with the composable's `canInlineEditTache` (or pull `permissions.can_inline_edit` if exposed on the resource) at every inline-edit interaction:

- [resources/js/components/taches/TacheTable.vue:74-75, 103-104](../../resources/js/components/taches/TacheTable.vue#L74-L75) — statut, priorité, échéance click guards.
- [resources/js/components/taches/SousTacheList.vue](../../resources/js/components/taches/SousTacheList.vue) — same pattern.
- [resources/js/pages/taches/DetailedTaskView.vue](../../resources/js/pages/taches/DetailedTaskView.vue) — inline edits in the detail view.
- [resources/js/pages/ActiviteDetail.vue](../../resources/js/pages/ActiviteDetail.vue) — any inline-edit cells.

Backend side: confirm `TacheResource` exposes `can_inline_edit` in `permissions` (via `PermissionService::canInlineEditTache`). If missing, add it — that path is already wired into `RolePermissionSeeder` and `useWorkspacePermissions.js`.

### 1.4 Clean up dead `taille_fichier` fallback
- [resources/js/components/taches/resultats/ResultatDetailModal.vue:297](../../resources/js/components/taches/resultats/ResultatDetailModal.vue#L297) — `doc.taille_fichier || doc.taille` → `doc.taille`.
- [resources/js/components/taches/resultats/ResultatDetailModal.vue:670](../../resources/js/components/taches/resultats/ResultatDetailModal.vue#L670) — same in preview pane.

(`uploaded_by_nom` is used by `task_file_added` notifications via [TacheFileAddedNotification.php:49](../../app/Notifications/Taches/TacheFileAddedNotification.php#L49) — that reference is **correct**, leave it.)

---

## Phase 2 — Page-by-page functional audit

For each Vue page under `resources/js/pages/`, verify against the backend route inventory. Work in a checklist; one page per pass.

Per-page checks:
1. Every `axios` call hits a route that exists in `routes/api.php` with the same verb.
2. Payload field names match the FormRequest (or `validate()`) rules.
3. Response dereferences (`.data.foo.bar`) exist in the corresponding `JsonResource::toArray()`.
4. Visible buttons / sidebar entries / route guards are gated on the right permission (cross-checked against `useWorkspacePermissions` / `useProjetPermissions` / `useActivitePermissions` / `useTachePermissions`).
5. Error states render — 401/403/404/422/500 each produce a user-readable message.
6. Loading states render — every async call has a spinner or skeleton.

Priority order (highest-risk pages first):
- **Admin section** (Task 14): `AdminDashboard.vue`, `AdminWorkspaces.vue`, `AdminUsers.vue` + `AdminRoles.vue` (paired with `PATCH /api/admin/roles/{role}/permissions`).
- **Subscription / trial** (Task 13): `TrialBanner.vue`, workspace settings page, `GET/PATCH /api/workspaces/{id}/subscription`.
- **Documents** (Task 12): `Documents.vue`, `WorkspaceDocuments.vue`, `pages/documents/*`, share-by-email modal.
- **Evaluations** (Tasks 7/9/10/16): `EvaluationDashboard.vue`, `AgentSheet.vue`, `PendingValidations.vue`, PDF export buttons.
- **Tasks** (Tasks 11/15/16): `Taches.vue`, `TacheTable.vue`, `WorkspaceTaches.vue` + Excel export, task creation wizard.
- **Validation circuit** (Tasks 5–8): `ResultatDetailModal.vue`, validation actions, bypass UI, notification bell + live-toast.
- **Projects / Activities**: hierarchical pages with nested members & permissions.
- **Auth**: login, register, accept-invitation, language switch.
- **Profile / Settings**: profile update, password change, notification preferences (`NotificationPreferences.vue`).

Capture each finding in [PROGRESSION.md](PROGRESSION.md) — Phase 2 table.

---

## Phase 3 — Responsiveness sweep

Test the same priority-ordered page list at three Tailwind breakpoints in the browser (Chrome DevTools device toolbar):
- `sm` (375px — iPhone SE)
- `md` (768px — iPad portrait)
- `lg` (1024px — small laptop)

For each page, check:
1. No horizontal scroll on the viewport.
2. Tables collapse to cards/stacked rows at `sm` (existing TailwindCSS conventions in `TacheTable`, `AdminWorkspaces` use `overflow-x-auto` — confirm or improve).
3. Sidebar collapses to drawer on `sm` and is accessible from a burger menu.
4. Modals/dialogs are full-screen on `sm`, centered on `md`+.
5. Forms reflow (single column on `sm`, two columns on `md`+).
6. CTAs stay reachable (no off-screen buttons, no overlap with footers).

Use the existing layout primitives (`Layouts/`, `components/common/`, `components/layout/`) — don't introduce new ones. Tailwind v4 `@theme` tokens are already defined; reuse them. Dark mode parity is already a project convention — fixes apply in both light and dark.

---

## Phase 4 — Test coverage for everything fixed

Per Guide 18 (mandatory Dusk per UI-touching task) and Guide 11 (PHPUnit conventions):

- **PHPUnit**: one feature test per new notification type in Phase 1.2 (assert the data shape: `type`, `document_id`, `document_nom`, `uploaded_by`, `dedup_key`). One feature test confirming `TacheResource` exposes `can_inline_edit` in `permissions`.
- **Dusk**: one test for each Phase 1 fix that has a UI surface: status badge renders `en_attente`/`annule` correctly; notification bell shows a `document_shared` toast; statut dropdown only opens when `can_inline_edit` is true.
- **Phase 2 regressions**: as the page-by-page audit uncovers fixes, each fixed page gets a Dusk happy-path test if it didn't already.
- All tests live in their existing `tests/Feature/<Task>/` or `tests/Browser/<FeatureArea>/` folders.

Run before commit (per Guide 11):
```bash
vendor/bin/pint --dirty --format agent
php artisan test --compact
php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
php artisan dusk tests/Browser/<FeatureArea>/
```

---

## Critical files (Phase 1 touchpoints)

**Frontend:**
- [resources/js/components/taches/TacheTable.vue](../../resources/js/components/taches/TacheTable.vue) — statut helpers + inline-edit gating
- [resources/js/components/taches/SousTacheList.vue](../../resources/js/components/taches/SousTacheList.vue) — statut helpers + inline-edit gating
- [resources/js/composables/useNotifications.js](../../resources/js/composables/useNotifications.js) — icon + color maps
- [resources/js/components/layout/header/NotificationDetailModal.vue](../../resources/js/components/layout/header/NotificationDetailModal.vue) — new document_* branches
- [resources/js/components/taches/resultats/ResultatDetailModal.vue](../../resources/js/components/taches/resultats/ResultatDetailModal.vue) — `taille_fichier` cleanup
- [resources/js/pages/taches/DetailedTaskView.vue](../../resources/js/pages/taches/DetailedTaskView.vue) — inline-edit gating
- [resources/js/pages/ActiviteDetail.vue](../../resources/js/pages/ActiviteDetail.vue) — inline-edit gating + statut helpers

**Backend (only if `can_inline_edit` is missing from `TacheResource`):**
- [app/Http/Resources/TacheResource.php](../../app/Http/Resources/TacheResource.php) — add `can_inline_edit` to `permissions` block
- [app/Services/PermissionService.php](../../app/Services/PermissionService.php) — verify `canInlineEditTache()` helper exists

**Docs:**
- [PROGRESSION.md](PROGRESSION.md) — running checklist for all 4 phases
- `docs/PROGRESSION.md` + `docs/SESSION_STATE.md` (end-of-task update per Guide 7 + 13)
- `docs/PERMISSIONS_MATRIX.md` — only if `can_inline_edit` was missing and we add it (Guide 15)

---

## Verification

### Phase 1
- `vendor/bin/pint --dirty --format agent` clean
- `php artisan test --compact --filter=Notification` green (new shape assertions)
- `php artisan test --compact --filter=Permission` green
- `php artisan dusk tests/Browser/Notifications/DocumentNotificationTest.php` green
- `php artisan dusk tests/Browser/Taches/TacheTableInlineEditTest.php` green
- Manual: log in as a `stagiaire`, open a task list — statut/priorité are not click-editable; log in as a `cadre` — they are.
- Manual: trigger a document share via API or share modal — bell shows the correct icon, color, and routes to the document.

### Phase 2
- Every page in the priority list is marked ✅ with the date verified in [PROGRESSION.md](PROGRESSION.md).
- A final `npm run build` produces zero warnings.
- `php artisan test --compact` stays at 627+ passing.

### Phase 3
- Each page in the priority list walked at 375/768/1024px in Chrome DevTools; screenshot captured under `docs/frontend-alignment/responsive/<page-name>.png`.
- No horizontal-scroll regressions on any page.

### Phase 4
- Test count grows by at least N (N = sum of Phase 1 fix points with a UI surface).
- `php artisan dusk` runs clean against the `work-tracking-dusk` database.

---

## Execution

- Branch: `feature/frontend-alignment-phase-1` from `jonas`; PR back into `jonas` per Guide 1.
- Phase 1 in this session (small, surgical, ~half a day of focused work).
- Phases 2–4 are a multi-session sweep — one page batch per session (3–5 pages each), updating [PROGRESSION.md](PROGRESSION.md) as we go so the user can see steady progress without one giant unreviewable PR.
