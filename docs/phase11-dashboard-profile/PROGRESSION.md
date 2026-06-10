# Phase 11 Progression — Dashboard Accuracy + Profile Page Fixes

> Local progress tracker for Phase 11 sub-branches. See `PLAN.md` in this folder for the full
> spec. Each sub-branch also gets a one-line summary row in the main `docs/PROGRESSION.md`.

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Sub-branch Progress

| # | Sub-task | Branch | Status | Started | Completed | Notes |
|---|---|---|---|---|---|---|
| 11A | Dashboard & statistics accuracy (A1-A4) | `feature/phase11a-dashboard-accuracy` | ✅ | 2026-06-10 | 2026-06-10 | All A1-A4 done; 4 new dashboard tests + 1 admin overdue test; Pint/Larastan/build green |
| 11B | Profile quick wins (B2 sound, B3 preferences cleanup, B7 dead link) | `feature/phase11b-profile-quickwins` | ⬜ | — | — | — |
| 11C | Session management (B1) | `feature/phase11c-session-management` | ⬜ | — | — | — |
| 11D | Help Center draft auto-save (B4) | `feature/phase11d-help-draft-autosave` | ⬜ | — | — | — |
| 11E | Activity tabs + Users management (B5 + B6) | `feature/phase11e-activity-users` | ⬜ | — | — | — |

---

## Deliverables Checklist per Sub-branch

### 11A — Dashboard & Statistics Accuracy ✅

- [x] A1: `calculateChange()` wired into `calculateStats()` for `projets_actifs`,
      `taux_completion`, `taches_en_retard` — real period-over-period `change`/`trend`
      (vs. `now()->subMonth()` snapshot)
- [x] A2: `getRecentProjects()` fetches across `active` + `completed` `ProjetStatus` values (not
      just `active`); `archived` excluded from "recent activity"
- [x] A2: `kanbanColumns` in `Dashboard.vue` aligned to real `ProjetStatus` enum values
      (`active` / `completed`, 2 columns — old unused `pending` column removed)
- [x] A2: Project-status i18n labels reviewed — added `common.completed` (fr/en), reused existing
      `common.active`/`common.archived`; no longer reusing task-status `statuts.*` keys
- [x] A3: Debug `console.log` lines removed from `Dashboard.vue::updateStatsCards()`
- [x] A4: `AdminController::computeStats()` overdue-count switched to `Tache::overdue()->count()`
      (matches `isOverdue()` semantics — counts tasks overdue by échéance regardless of stored
      `statut`, not just `statut = en_retard`)
- [x] PHPUnit tests added/updated for dashboard stats + admin stats
      (`tests/Feature/Dashboard/DashboardStatsTest.php` — 4 tests;
      `PlatformDashboardTest::test_stats_overdue_uses_isoverdue_semantics_not_just_en_retard_status`)
- [x] Pint + Larastan clean
- [x] `npm run build` green
- [x] `docs/testing/PHASE11A_TESTING.md` written
- [x] Main `docs/PROGRESSION.md` Phase 11 row updated
- [x] `docs/SESSION_STATE.md` updated (Current Task → 11B)

### 11B — Profile Quick Wins

- [ ] B2: `useNotificationSound.js` composable created; sound asset added under `public/sounds/`
- [ ] B2: `useLiveNotifications.js` plays sound on incoming notification when enabled
- [ ] B3: Theme/Language/Date Format/Display Density/Auto-Save removed from
      `PreferencesSettings.vue`; Timezone kept (confirmed not duplicated by navbar)
- [ ] B3: Guide 14 impact check done before removing localStorage-backed preference reads
- [ ] B7: Dead `/settings` navbar link fixed (removed or repointed to `/profile`)
- [ ] B7: Unused `user_menu.account_settings` i18n key removed (if applicable, after grep check)
- [ ] Tests added (Vitest if available, else manual steps documented)
- [ ] Pint + Larastan clean
- [ ] `npm run build` green
- [ ] `docs/testing/PHASE11B_TESTING.md` written
- [ ] Main `docs/PROGRESSION.md` Phase 11 row updated
- [ ] `docs/SESSION_STATE.md` updated

### 11C — Session Management

- [ ] Migration: `user_agent`/`ip_address` columns added to `personal_access_tokens` (if needed)
- [ ] `AuthService::login()` captures user-agent/IP on token creation
- [ ] `GET /api/users/sessions` — list active sessions/tokens for current user
- [ ] `DELETE /api/users/sessions/{tokenId}` — revoke own token (403 on others')
- [ ] `POST /api/users/sessions/revoke-all` — revoke all tokens except current
- [ ] `SessionSettings.vue` — real session list with stagger, "this device" badge, selective
      termination, "logout all other devices"
- [ ] `authStore` — `fetchSessions()`, `revokeSession()`, `revokeAllOtherSessions()` replace
      non-existent `logoutAllDevices()`
- [ ] PHPUnit feature tests (list/revoke/revoke-all, ownership checks)
- [ ] 1 Dusk test for revoke flow
- [ ] Pint + Larastan clean
- [ ] `npm run build` green
- [ ] `docs/testing/PHASE11C_TESTING.md` written
- [ ] Main `docs/PROGRESSION.md` Phase 11 row updated
- [ ] `docs/SESSION_STATE.md` updated

### 11D — Help Center Draft Auto-Save

- [ ] Migration: `draft_body_fr`, `draft_body_en`, `draft_saved_at` added to `help_articles`
- [ ] `HelpArticle` model — fillable/casts/`@property` PHPDoc updated
- [ ] `PATCH /admin/help/articles/{article}/draft` endpoint — writes draft columns only
- [ ] Edit form — "Restore / Discard draft" banner when `draft_saved_at > updated_at`
- [ ] Frontend — debounced (~5s) auto-save call + "Draft saved at HH:MM" indicator
- [ ] PHPUnit: draft endpoint persists without touching `body_fr`/`published_at`; auth matches
      `update()`
- [ ] 1 Dusk test: auto-save → reload → restore banner → restore content
- [ ] Pint + Larastan clean
- [ ] `npm run build` green
- [ ] `docs/testing/PHASE11D_TESTING.md` written
- [ ] Main `docs/PROGRESSION.md` Phase 11 row updated
- [ ] `docs/SESSION_STATE.md` updated

### 11E — Activity Tabs + Users Management

- [ ] B5: `ActivityLog.vue` wired to real `GET /users/{user}/activity` (mock generator removed)
- [ ] B5: stagger applied to activity list
- [ ] B6a: `AdminUsers.vue` — "Activity" action per row, pre-filtered to `causer_id`
- [ ] B6a: `ActivityLogTab.vue` accepts `fixedCauserId` prop
- [ ] B6a: `adminFeed()` confirmed/extended to support `causer_id` filter
- [ ] B6b: New workspace-scoped "Users" page + route, gated to workspace owner
- [ ] B6b: New backend endpoint `GET /api/workspaces/{workspace}/users` (workspace-scoped)
- [ ] B6b: Per-user activity drill-down for owner (scoped endpoint or `adminFeed` extension)
- [ ] B6b: New permission added — Guide 4 full checklist (PermissionService, RolePermissionSeeder,
      Permission.php/forRole, Permission.js, useWorkspacePermissions.js, WorkspaceController
      user_permissions ×3)
- [ ] B6b: Sidebar "Users" entry added for workspace owners
- [ ] PHPUnit: workspace users endpoint (200/403 boundaries), activity drill-down (200/403)
- [ ] Dusk: owner navigates Users page → Activity drill-down
- [ ] `docs/PERMISSIONS_MATRIX.md` updated + changelog row (Guide 15 hard gate)
- [ ] Pint + Larastan clean
- [ ] `npm run build` green
- [ ] `docs/testing/PHASE11E_TESTING.md` written
- [ ] Main `docs/PROGRESSION.md` Phase 11 row updated
- [ ] `docs/SESSION_STATE.md` updated
