# Session Management Testing Guide

**Feature:** Session management fixes — multi-session support, inactivity timer, token refresh, push notification settings, admin audit log frontend  
**Branch:** `feature/superadmin-scoping`  
**Tasks:** A (multi-session), B (inactivity timer), C (token refresh), D (push notifications), Step 13b (admin audit log page)

---

## PHPUnit Tests

### Multi-session (Task A)
```bash
php artisan test --compact tests/Feature/MultiSessionTest.php
```
7 tests:
- `test_user_can_have_multiple_sessions` — multiple tokens coexist
- `test_logout_all_revokes_all_tokens` — `POST /logout-all` removes all tokens
- `test_revoke_specific_session_removes_token` — `DELETE /sessions/{id}` removes one token
- `test_cannot_revoke_another_users_session` — 403 for cross-user revoke
- `test_revoked_session_token_no_longer_works` — 401 after revoke
- `test_revoke_notification_sent_on_revoke` — `SessionRevokedNotification` fires
- `test_sessions_list_returns_active_tokens` — `GET /sessions` returns auth_token-named tokens only

### Token refresh (Task C)
```bash
php artisan test --compact tests/Feature/TokenRefreshTest.php
```
4 tests:
- `test_refresh_returns_new_token` — `POST /auth/refresh` returns new token
- `test_refresh_with_expired_token_returns_401` — expired token is rejected
- `test_refresh_requires_auth` — unauthenticated refresh → 401
- `test_refresh_invalidates_old_token` — old token no longer works after refresh

---

## Dusk Browser Tests

Run each test individually (sequential run within one PHP process causes timing issues):

```bash
php artisan serve &

# Inactivity timer (Task B)
php artisan dusk tests/Browser/Auth/InactivityTimerTest.php --filter=test_timer_display_shows_minutes_not_seconds
php artisan dusk tests/Browser/Auth/InactivityTimerTest.php --filter=test_selecting_never_shows_jamais
php artisan dusk tests/Browser/Auth/InactivityTimerTest.php --filter=test_timeout_setting_persists_after_reload

# Multi-session (Task A)
php artisan dusk tests/Browser/Auth/MultiSessionTest.php --filter=test_sessions_list_shows_multiple_sessions
php artisan dusk tests/Browser/Auth/MultiSessionTest.php --filter=test_revoke_other_session_removes_row
php artisan dusk tests/Browser/Auth/MultiSessionTest.php --filter=test_logout_all_redirects_to_signin

# Notification settings (Task D)
php artisan dusk tests/Browser/Profile/NotificationSettingsTest.php --filter=test_push_toggle_reflects_real_subscription_state
php artisan dusk tests/Browser/Profile/NotificationSettingsTest.php --filter=test_email_toggle_saves_to_backend_and_persists
php artisan dusk tests/Browser/Profile/NotificationSettingsTest.php --filter=test_save_shows_toast_not_alert
```

**Screenshots:** `tests/Browser/screenshots/session-management/`

---

## Manual Testing

### Task A — Multi-session

1. Sign in on device 1, sign in again on device 2 (different browser or incognito)
2. Go to Profile → Security tab → "Sessions actives" section
3. Both sessions should appear as distinct rows with timestamps
4. Click "Révoquer" on device 2's session → confirm dialog → row disappears
5. On device 2: any API call should now return 401 and redirect to `/signin`
6. Click "Déconnecter tous les appareils" → all sessions removed → redirect to `/signin`

### Task B — Inactivity timer

1. Profile → Security → "Délai d'inactivité" section
2. Select "15 min" — countdown shows `14 min XX sec`, NOT `899 sec`
3. Select "Jamais" — countdown shows "Jamais" (no countdown)
4. Select "30 min", reload page → still shows "30 min" selected (localStorage persists)
5. Wait for selected timeout to expire → app redirects to `/signin` automatically

### Task C — Token refresh

1. Sign in with "Se souvenir de moi" checked → token valid 30 days
2. Sign in without "Se souvenir de moi" → token valid 24h
3. The app silently refreshes the token on each API call via the `Authorization` header rotation
4. Manually expire a token in DB → next API call returns 401 → redirect to signin (no loop)

### Task D — Push notifications

1. Profile → Notifications tab
2. Push toggle is OFF by default (no browser subscription active)
3. Enable push toggle → browser permission prompt appears → allow → toggle shows ON
4. Disable email toggle → save → reload → email toggle still OFF (persisted to backend)
5. Save button shows a toast, not a native `alert()`

### Step 13b — Admin audit log page

1. Login as permanent superadmin → sidebar shows "Journal d'audit plateforme" under Administration
2. Navigate to `/admin/audit-log` → table loads with paginated entries (action, actor, IP, date)
3. Filter by action (e.g. "workspace.suspend") → table updates
4. Filter by date range → results scoped correctly
5. As directeur: navigate to `/admin/my-audit-log` → shows only temp SA actions for your accounts
6. As non-superadmin workspace user: `/admin/audit-log` redirects to home (403 guard)

---

## Key Implementation Notes

- **Single-session lock removed:** `issueToken()` no longer calls `tokens()->delete()` before creating. Users can have multiple `auth_token` sessions.
- **Inactivity multiplier:** Timer was stored in minutes but multiplied by 1000ms. Fixed to multiply by `60 * 1000` (milliseconds per minute). `Infinity` stored as `0` in localStorage; display shows "Jamais".
- **Token refresh route:** Moved from unauthenticated to `auth:sanctum` middleware. Expired tokens still rejected (401) — no refresh-loop risk.
- **SessionRevokedNotification:** Database + mail notification sent when a session is remotely revoked.
- **AdminAuditLog page:** Standalone page at `/admin/audit-log` (permanent SA only); `DirecteurAuditLog.vue` at `/admin/my-audit-log` (directeur-scoped). Both use the `AdminAuditLogTab` reusable component.
