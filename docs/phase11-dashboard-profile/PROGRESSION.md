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
| 11A | Dashboard & statistics accuracy (A1-A4) | `feature/phase11a-dashboard-accuracy` | ✅ | 2026-06-10 | 2026-06-10 | All A1-A4 done; 4 new dashboard tests + 1 admin overdue test; 3 Dusk tests (stat cards, hardcoded-value check, Kanban columns); Pint/Larastan/build green |
| 11B | Profile quick wins (B2 sound, B3 preferences cleanup, B7 dead link) | `feature/phase11b-profile-quickwins` | ✅ | 2026-06-10 | 2026-06-10 | Notification sound composable + asset; Preferences tab reduced to Timezone only; dead navbar link removed; 3 Dusk tests (sound toggle, timezone section present, removed sections absent); Pint/Larastan/build green |
| 11C | Session management (B1) | `feature/phase11c-session-management` | ✅ | 2026-06-10 | 2026-06-10 | Real sessions list (GET /api/users/sessions); logoutAllDevices() wired; SessionSettings.vue rewritten with stagger; 5 PHPUnit + 3 Dusk tests; Pint/Larastan/build green |
| 11D | Help Center draft auto-save (B4) | `feature/phase11d-help-draft-autosave` | ✅ | 2026-06-10 | 2026-06-10 | Migration + model + PATCH /draft endpoint + debounced auto-save + restore banner; 7 PHPUnit + 3 Dusk tests (banner display, discard, auto-save indicator); Pint/Larastan/build green |
| 11E | Activity tabs + Users management (B5 + B6) | `feature/phase11e-activity-users` | ✅ | 2026-06-10 | 2026-06-10 | ActivityLog.vue wired to real API + stagger; ActivityLogTab fixedCauserId prop; AdminUsers Activity button; WORKSPACES_VIEW_MEMBERS permission; GET /workspaces/{id}/users endpoint; WorkspaceUsers.vue + route + sidebar; 8 PHPUnit + 3 Dusk tests; Pint/Larastan/build green |

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
- [x] Dusk tests: `tests/Browser/Phase11/Phase11ADashboardTest.php` — 3 tests
      (stat cards visible + change badges, no hardcoded +12%/+5%, Kanban active/completed cols)
- [x] `dusk` attrs added: `stats-cards-grid`, `stat-card-{n}`, `stat-card-{n}-change`,
      `kanban-board`, `kanban-col-{status}` in `Dashboard.vue`
- [x] Pint + Larastan clean
- [x] `npm run build` green
- [x] `docs/testing/PHASE11A_TESTING.md` written
- [x] Main `docs/PROGRESSION.md` Phase 11 row updated
- [x] `docs/SESSION_STATE.md` updated (Current Task → 11B)

### 11B — Profile Quick Wins ✅

- [x] B2: `useNotificationSound.js` composable created; sound asset added under
      `public/sounds/notification.ogg` (short two-tone chime, generated with `sox`)
- [x] B2: `App.vue`'s `onNotification(...)` handler (registered via `useLiveNotifications`) calls
      `playIfEnabled()` on each incoming live notification, gated on
      `localStorage.notificationSettings.notificationSounds`
- [x] B3: Theme/Language/Date Format/Display Density/Auto-Save removed from
      `PreferencesSettings.vue`; Timezone kept (confirmed not duplicated by navbar)
- [x] B3: Guide 14 impact check done — `userPreferences` localStorage key only read/written by
      `PreferencesSettings.vue` itself; navbar theme/language switchers are independent
      composables; removed unused `pref_settings.theme_*`/`lang_*`/`date_*`/`density_*`/
      `autosave_*` i18n keys (fr/en) after confirming zero remaining usages
- [x] B7: Dead `/settings` navbar link removed from `UserMenu.vue` (`/profile` "Editer le profil"
      already covers profile editing); unused `SettingsIcon` import removed
- [x] B7: Unused `user_menu.account_settings` i18n key removed (fr/en) after confirming single
      usage
- [x] Tests: no JS test runner configured — manual steps in `docs/testing/PHASE11B_TESTING.md`
- [x] Dusk tests: `tests/Browser/Phase11/Phase11BProfileTest.php` — 3 tests
      (sound toggle visible, timezone section present, theme/language/density sections absent)
- [x] `dusk` attrs added: `notification-sounds-toggle` in `NotificationSettings.vue`;
      `preferences-settings-panel`, `pref-timezone-section` in `PreferencesSettings.vue`;
      `profile-tab-{id}` in `UserProfile.vue`
- [x] Pint + Larastan clean
- [x] `npm run build` green
- [x] `docs/testing/PHASE11B_TESTING.md` written
- [x] Main `docs/PROGRESSION.md` Phase 11 row updated
- [x] `docs/SESSION_STATE.md` updated

### 11C — Session Management ✅

- [x] No migration needed — `personal_access_tokens` already has `last_used_at`/`expires_at`;
      no `user_agent`/`ip_address` required (single-session model makes per-device labelling
      unnecessary)
- [x] `GET /api/users/sessions` endpoint added to `UserController` — returns current user's
      active `auth_token` tokens with `id`, `name`, `created_at`, `last_used_at`, `expires_at`,
      `is_current` fields; scoped to authenticated user only (cross-user isolation confirmed)
- [x] Route `GET /api/users/sessions` wired in `routes/api.php` (inside Sanctum-guarded `/users`
      group)
- [x] `authAPI.getSessions()` added to `resources/js/api/auth.js`
- [x] `authStore.fetchSessions()` + `authStore.logoutAllDevices()` added; the latter is an alias
      for `logout()` (single-session by design — no separate revoke-all needed)
- [x] `SessionSettings.vue` rewritten — Tailwind-based (no more scoped CSS), real sessions list
      with stagger (`useStagger`), "Cet appareil" + green "Actif" badge, created/last-used dates,
      Révoquer button per row (triggers logout), inactivity-timeout picker preserved
- [x] New i18n keys added: `active_sessions_title`, `active_sessions_desc`, `this_device`,
      `session_created`, `session_last_used`, `session_never_used`, `session_revoke`,
      `sessions_loading`, `sessions_empty` (fr/en)
- [x] PHPUnit tests: `tests/Feature/UserSessionsTest.php` — 5 tests (401 unauth; 200 structure;
      `is_current` flag; cross-user isolation; only `auth_token` tokens returned)
- [x] Dusk tests: `tests/Browser/Phase11/Phase11CSessionTest.php` — 3 tests
      (active-sessions section visible, sessions list renders, revoke button visible)
- [x] `dusk` attrs added: `active-sessions-section`, `sessions-list`, `session-item-{id}`,
      `session-revoke-btn` in `SessionSettings.vue`
- [x] Pint + Larastan clean (Pint auto-promoted inline FQCNs to `use` statements)
- [x] `npm run build` green
- [x] `docs/testing/PHASE11C_TESTING.md` written
- [x] Main `docs/PROGRESSION.md` Phase 11 row updated
- [x] `docs/SESSION_STATE.md` updated

### 11D — Help Center Draft Auto-Save ✅

- [x] Migration: `draft_body_fr` (longText nullable), `draft_body_en` (longText nullable),
      `draft_saved_at` (timestamp nullable) added to `help_articles`
- [x] `HelpArticle` model — `draft_*` columns added to `$fillable`; `draft_saved_at` → `datetime`
      in `$casts`; 3 `@property` PHPDoc entries added
- [x] `PATCH /admin/help/articles/{article}/draft` endpoint (`saveDraft()` in `AdminHelpController`)
      — uses `updateQuietly()` so the `booted()` Purifier hook on `body_fr`/`body_en` never fires;
      writes draft columns only; requires `help_articles.edit` permission; returns `draft_saved_at`
- [x] `SaveHelpArticleDraftRequest` form request created
- [x] Route `PATCH /api/admin/help/articles/{article}/draft` wired in `routes/api.php`
- [x] `HelpArticleForm.vue` — debounced 5s auto-save watching `form.body_fr`/`form.body_en`;
      "Draft saved at HH:MM" indicator below editors; "Restore / Discard" banner shown when
      `draft_saved_at > updated_at` on load; `onBeforeUnmount` clears pending timer
- [x] New i18n keys: `help.admin.draft_saved_at`, `draft_restore_banner`, `draft_restore`,
      `draft_discard`, `draft_saving`, `draft_saved` (fr + en)
- [x] PHPUnit: `tests/Feature/Help/HelpArticleDraftTest.php` — 7 tests:
      unauthenticated 401, non-editor 403, super-admin 200, draft columns only written (body_fr
      + published_at unchanged), draft_saved_at set to now, member-with-edit 200,
      booted() hook not triggered on plain-text columns
- [x] Dusk tests: `tests/Browser/Phase11/Phase11DHelpDraftTest.php` — 3 tests
      (restore banner shown when draft > updated_at, discard hides banner, auto-save
      indicator appears after typing in ProseMirror editor within debounce + network delay)
- [x] `dusk` attrs added: `draft-restore-banner`, `draft-restore-btn`,
      `draft-status-indicator` in `HelpArticleForm.vue`
- [x] Pint clean
- [x] Larastan clean (0 errors)
- [x] `npm run build` green
- [x] `docs/testing/PHASE11D_TESTING.md` written
- [x] Main `docs/PROGRESSION.md` Phase 11 row updated
- [x] `docs/SESSION_STATE.md` updated

### 11E — Activity Tabs + Users Management ✅

- [x] B5: `ActivityLog.vue` wired to real `GET /users/{user}/activity` (mock generator removed)
- [x] B5: stagger applied to activity list (`useStagger(50)` + `ref="staggerRef"` + `.stagger-item`)
- [x] B5: error state added with retry button
- [x] B6a: `AdminUsers.vue` — "Activity" button per row, opens modal with `ActivityLogTab`
- [x] B6a: `ActivityLogTab.vue` accepts `fixedCauserId` + `fixedCauserLabel` props — causer
      autocomplete hidden, locked chip shown; `resetFilters()` preserves the lock
- [x] B6a: `adminFeed()` already supported `causer_id` filter — no backend change needed
- [x] B6b: New permission `WORKSPACES_VIEW_MEMBERS = 'workspaces.view_members'` added to
      `Permission.php` (constant + `all()` + `forRole('owner')` auto-included via `array_diff`,
      `forRole('manager')` explicit grant)
- [x] B6b: `Permission.js` mirror updated with `WORKSPACES_VIEW_MEMBERS`
- [x] B6b: `useWorkspacePermissions.js` — `canViewMembers` computed added + exported
- [x] B6b: `WorkspaceController::workspaceUsers()` — `GET /api/workspaces/{workspace}/users`
      endpoint: paginated, searchable, returns `id/nom/email/workspace_role/joined_at/last_login_at`;
      gated by `abort_unless(WORKSPACES_VIEW_MEMBERS)`
- [x] B6b: `WorkspaceController` `user_permissions` payload updated at all 3 locations to include
      `can_view_members`
- [x] B6b: Route `GET /api/workspaces/{workspace}/users` wired in `routes/api.php`
- [x] B6b: `WorkspaceUsers.vue` page created at `resources/js/pages/workspace/WorkspaceUsers.vue`
      (AdminLayout, search, stagger table, pagination, per-row Activity modal re-using
      `ActivityLogTab` with `fixedCauserId`)
- [x] B6b: Route `workspace.users` added to `resources/js/router/index.ts`
- [x] B6b: Sidebar "Utilisateurs" entry added (gated on `canViewMembers`)
- [x] B6b: `canViewMembers` added to sidebar `permissionMap` computed
- [x] PHPUnit: `tests/Feature/Phase11ETest.php` — 8 tests (own activity 200, other user 403,
      super-admin cross-user 200, unauthenticated 401; owner list 200, member fields, workspace
      scoping, non-member 403, collaborateur 403, search filter, unauthenticated 401)
- [x] Dusk: `tests/Browser/Phase11/Phase11EActivityUsersTest.php` — 3 tests (profile activity tab
      renders, admin users activity button present, workspace users page shows members)
- [x] `dusk` attrs added: `workspace-users-title`, `workspace-users-search`, `workspace-user-row`,
      `ws-view-activity-button`, `ws-activity-modal` in `WorkspaceUsers.vue`;
      `view-activity-button`, `activity-modal` in `AdminUsers.vue`
- [x] i18n: `workspace_users.*` section (fr/en), `sidebar.workspace_users` (fr/en),
      `admin.users.btn_activity` + `activity_modal_title` (fr/en),
      `admin_logs.filter_causer_locked` (fr/en), `activity_log.loading_error` (fr/en)
- [x] `docs/PERMISSIONS_MATRIX.md` — new permission `workspaces.view_members` added (Guide 15)
- [x] Pint clean (3 files auto-fixed)
- [x] Larastan clean (0 errors)
- [x] `npm run build` green
- [x] `docs/testing/PHASE11E_TESTING.md` written
