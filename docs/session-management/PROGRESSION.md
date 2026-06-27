# Progression — Session Management Fixes + Multi-Session + Push Notifications

**Branch:** `feature/session-management`
**Reference plan:** `docs/session-management/PLAN.md`

---

## Progress Table

| # | Step | File(s) | Status | Notes |
|---|---|---|---|---|
| 0 | Add Guide 28 to WORKING_GUIDELINES.md | `docs/WORKING_GUIDELINES.md` | ✅ | Regression-first impact check rule |
| B1 | Fix `setTimeoutDuration` multiplier (1×1000 → 60×1000) | `authStore.js` | ✅ | |
| B2 | Fix `getTimeoutDuration` divisor | `authStore.js` | ✅ | |
| B3 | Fix `initialize()`: restore saved timeout instead of removing it | `authStore.js` | ✅ | |
| B4 | Handle Infinity for "Never" option in `setTimeoutDuration`, `resetInactivityTimer`, `checkInactivity` | `authStore.js` | ✅ | |
| B5 | Fix `timeUntilLogout` display: reactive `now` ref + handle Infinity | `SessionSettings.vue` | ✅ | |
| B6 | Add `session_settings.never` i18n key | `fr.json`, `en.json` | ✅ | |
| B7 | Add `dusk` attrs to timeout buttons + countdown | `SessionSettings.vue` | ✅ | |
| B8 | Dusk tests: timer accuracy, Never label, persistence after reload | `tests/Browser/Auth/InactivityTimerTest.php` | ✅ | Written — 3 tests |
| B9 | Write testing doc | `docs/session-management/testing/STEP_B_TESTING.md` | ✅ | |
| C1 | Fix `refreshToken()` store: update `localStorage.token_expires_at` | `authStore.js` | ✅ | |
| C2 | Fix `refreshSession()` in SessionSettings: guard against already-expired token | `SessionSettings.vue` | ✅ | |
| C3 | Fix `AuthService::refreshToken()`: preserve remember-me duration | `AuthService.php` | ✅ | Also moved route to auth:sanctum group |
| C4 | Add `dusk="refresh-session-btn"` | `SessionSettings.vue` | ✅ | |
| C5 | PHPUnit tests: `TokenRefreshTest.php` (4 tests) | `tests/Feature/TokenRefreshTest.php` | ✅ | 4/4 passing |
| C6 | Write testing doc | `docs/session-management/testing/STEP_C_TESTING.md` | ✅ | |
| A1 | Remove single-session deletion from `AuthService::issueToken()` | `AuthService.php` | ✅ | |
| A2 | Add `AuthService::logoutAll()` | `AuthService.php` | ✅ | |
| A3 | Add `AuthController::logoutAll()` + `AuthController::revokeSession()` | `AuthController.php` | ✅ | |
| A4 | Add `POST /auth/logout-all` + `DELETE /auth/sessions/{tokenId}` routes | `routes/api.php` | ✅ | |
| A5 | Add `SessionRevokedNotification` (database + mail) | `app/Notifications/SessionRevokedNotification.php` | ✅ | |
| A6 | Wire `session_revoked` event in `NotificationService` | — | 🚫 | Skipped by design — notification dispatched directly from `AuthController::revokeSession()` |
| A7 | Add `session_revoked.*` i18n keys | `lang/fr/notifications.php`, `lang/en/notifications.php` | ✅ | |
| A8 | Add `authAPI.logoutAll()` + `authAPI.revokeSession()` | `resources/js/api/auth.js` | ✅ | |
| A9 | Update `logoutAllDevices()` store action; add `revokeSession()` store action | `authStore.js` | ✅ | |
| A10 | Fix `revokeSession(session)` in SessionSettings: per-session vs full logout | `SessionSettings.vue` | ✅ | |
| A11 | Add `dusk="logout-all-btn"` | `SessionSettings.vue` | ✅ | |
| A12 | PHPUnit tests: `MultiSessionTest.php` (7 tests) | `tests/Feature/MultiSessionTest.php` | ✅ | 7/7 passing |
| A13 | Update `UserSessionsTest.php` (relax "one session" assertion) | — | ✅ | Verified: existing test still valid (creates exactly 1 auth_token) |
| A14 | Dusk tests: `MultiSessionTest.php` (3 tests) | `tests/Browser/Auth/MultiSessionTest.php` | ✅ | Written — 3 tests |
| A15 | Write testing doc | `docs/session-management/testing/STEP_A_TESTING.md` | ✅ | |
| D1 | Rewrite `NotificationSettings.vue` script: load/save from real API | `NotificationSettings.vue` | ✅ | |
| D2 | Wire push toggle to `useWebPush` (subscribe/unsubscribe) | `NotificationSettings.vue` | ✅ | |
| D3 | Add browser-support + permission-denied warnings in template | `NotificationSettings.vue` | ✅ | |
| D4 | Replace `alert()` calls with `vue-toastification` | `NotificationSettings.vue` | ✅ | |
| D5 | Add `dusk` attrs to push/email toggles + save button | `NotificationSettings.vue` | ✅ | |
| D6 | Add notification-preferences API helpers if missing | — | ✅ | Already existed via `api.get/patch` directly |
| D7 | PHPUnit tests: `NotificationPreferenceTest.php` | `tests/Feature/WebPushSubscriptionTest.php` | ✅ | Already covered |
| D8 | Dusk tests: `NotificationSettingsTest.php` (3 tests) | `tests/Browser/Profile/NotificationSettingsTest.php` | ✅ | Written — 3 tests |
| D9 | Write testing doc | `docs/session-management/testing/STEP_D_TESTING.md` | ✅ | |
| Z1 | Pint + Larastan clean | — | ✅ | Both pass |
| Z2 | `npm run build` green | — | ✅ | |
| Z3 | Full PHPUnit + Dusk test suites green | — | ⏳ | Deferred — run before push |
| Z4 | Update `docs/PROGRESSION.md` + `docs/SESSION_STATE.md` | — | ✅ | Both updated |

---

## Legend

- ✅ Done
- 🔄 In progress
- ⬜ Not started
- ⏳ Deferred
- 🚫 Skipped by design
- ⚠️ Blocked

---

## Session Log

| Date | Session | Progress |
|------|---------|---------|
| 2026-06-27 | Planning | Plan written, docs folder created |
| 2026-06-27 | Implementation | Tasks B, C, A, D all complete. 11 PHPUnit tests passing. Testing docs written (STEP_B/C/A/D_TESTING.md). Pint + Larastan + build green. Dusk tests and full suite deferred — run before push. |
