# Plan — Session Management Fixes + Multi-Session + Push Notifications

**Branch:** `feature/session-management`
**Reference:** `docs/session-management/PROGRESSION.md`

---

## Context

Four things are broken in the User Profile Security/Notifications area:

1. **Single-session lock**: `AuthService::issueToken()` deletes all `auth_token` rows on every login — logging in on phone kills the desktop session.
2. **Auto-disconnect timer broken**: wrong multiplier (`1 * 1000` instead of `60 * 1000`) + saved setting wiped on every boot (`initialize()` removes `inactivity_timeout` from localStorage).
3. **Session refresh logs user out**: `refreshToken()` store action doesn't update `localStorage.token_expires_at`, and if the token is already expired when the user clicks refresh, the API returns 401 → the store catch block calls `logout()`.
4. **Push notifications always appear enabled but never fire**: `NotificationSettings.vue` is a stub — it uses `localStorage` instead of the real `NotificationPreferenceController` API, the push toggle has no connection to `useWebPush.js`, and the browser subscription is never actually requested.

Additionally: add **Guide 28** (regression-first impact check) to `WORKING_GUIDELINES.md`.

---

## New Rule — Guide 28

Add after Guide 27 in `docs/WORKING_GUIDELINES.md`:

**Guide 28 — Regression-First Impact Check (mandatory)**

Before touching any existing method, column, route, composable, store action, or translation key:
1. Search the full codebase for all callers and usages (PHP + JS/Vue + tests + translations).
2. Report the complete impact surface.
3. Only then make the change.

This applies even to single-line edits. "It looks isolated" is not sufficient — in this project, most breakages have come from edits that appeared isolated.

---

## Task A — Multi-Session Support

### What to change

**`app/Services/AuthService.php` — `issueToken()`**

Remove line 89:
```php
$user->tokens()->where('name', 'auth_token')->delete();
```
Multiple concurrent tokens are supported naturally by Sanctum once this deletion is removed.

**Impact before touching:**
- `issueToken()` is called only from `AuthController` (login + MFA completion). No other callers.
- `logout()` already deletes only `currentAccessToken()` — unaffected.
- `refreshToken()` already deletes only `currentAccessToken()` — unaffected.
- `UserSessionsTest.php` assertion "only one session" needs updating.

**Add `logoutAll()` to `AuthService`:**
```php
public function logoutAll(): void
{
    $user = Auth::user();
    if ($user) {
        $user->tokens()->where('name', 'auth_token')->delete();
        activity()->performedOn($user)->causedBy($user)->log('Toutes les sessions révoquées');
    }
    Auth::guard('web')->logout();
}
```

**`app/Http/Controllers/Api/AuthController.php`** — add two methods:
- `logoutAll()` → calls `AuthService::logoutAll()`, clears session, returns 200.
- `revokeSession(Request $request, int $tokenId)` → finds token by ID, verifies it belongs to `$request->user()` (never allow cross-user revocation), deletes it, returns 200. Dispatches `SessionRevokedNotification` when the revoked token is NOT the current one.

**`routes/api.php`** (inside `auth:sanctum` group):
```php
Route::post('/auth/logout-all', [AuthController::class, 'logoutAll']);
Route::delete('/auth/sessions/{tokenId}', [AuthController::class, 'revokeSession']);
```

**`resources/js/api/auth.js`** — add (follow existing shape):
```js
logoutAll: () => api.post('/auth/logout-all'),
revokeSession: (tokenId) => api.delete(`/auth/sessions/${tokenId}`),
```

**`resources/js/stores/authStore.js`**:
- `logoutAllDevices()`: replace stub with real call to `authAPI.logoutAll()`, then `clearAuth()` + redirect to `/signin`.
- Add `revokeSession(tokenId, isCurrent)` action: calls `authAPI.revokeSession(tokenId)`. If `isCurrent`, call `clearAuth()` + redirect. Otherwise just refresh the sessions list.

**`resources/js/components/settings/SessionSettings.vue` — `revokeSession(session)`:**
- If `session.is_current`: call `authStore.logout()`.
- Otherwise: call `authStore.revokeSession(session.id, false)` + reload sessions list.

### Notifications
Add `app/Notifications/SessionRevokedNotification.php` (database + inline mail, ShouldQueue).
- Sent to the user when a non-current session is revoked (security awareness).
- Add `session_revoked` to `NotificationService::wantsEmail()` + `wantsWebPush()`.
- i18n keys: `notifications.session_revoked.*` in `lang/fr/notifications.php` + `lang/en/notifications.php`.

### Logs
- In `AuthController::revokeSession()`: `Log::info('Session révoquée', ['user_id' => $user->id, 'token_id' => $tokenId])`.

### Tests (PHPUnit) — `tests/Feature/Auth/MultiSessionTest.php`

| Test | Scenario | Expected |
|------|----------|----------|
| `login_on_two_devices_keeps_both_tokens` | Login twice | Both tokens valid (200 on `/api/user`) |
| `logout_revokes_only_current_token` | Two tokens; logout with token A | Token B still works (200) |
| `logout_all_revokes_all_tokens` | Two tokens; `POST /auth/logout-all` | Both return 401 |
| `revoke_other_session_works` | Two tokens; revoke token B's ID | Token B returns 401; token A still works |
| `revoke_current_session_is_logout` | Revoke own token ID | Returns 200; subsequent request 401 |
| `cannot_revoke_another_users_session` | User A revokes user B's token ID | 403 |
| `revoke_other_session_sends_notification` | Revoke non-current session | `SessionRevokedNotification` queued |

### Tests (Dusk) — `tests/Browser/Auth/MultiSessionTest.php`

| Test | Scenario |
|------|----------|
| `test_sessions_list_shows_multiple_sessions` | Sign in twice; Security tab shows 2 rows |
| `test_revoke_other_session_removes_row` | Revoke non-current row; row disappears |
| `test_logout_all_redirects_to_signin` | Click "Déconnecter tous les appareils"; verify redirect |

`dusk` attributes to add: `dusk="sessions-list"` (already present), `dusk="session-revoke-btn"` (already present), `dusk="logout-all-btn"` on the logoutAll button.

---

## Task B — Auto-Disconnect Timer

### What to change

**`resources/js/stores/authStore.js`**

1. `setTimeoutDuration(minutes)` — fix multiplier + handle 0 (Never):
   ```js
   if (minutes === 0) {
       this.inactivityTimeout = Infinity
       localStorage.setItem('inactivity_timeout', '0')
       clearTimeout(this.inactivityTimer); this.inactivityTimer = null
       this.hideTimeoutWarning()
       return
   }
   this.inactivityTimeout = minutes * 60 * 1000   // was 1 * 1000
   localStorage.setItem('inactivity_timeout', String(minutes))
   this.resetInactivityTimer()
   ```

2. `getTimeoutDuration()` — fix divisor:
   ```js
   if (this.inactivityTimeout === Infinity) return 0
   return this.inactivityTimeout / (60 * 1000)   // was / (1 * 1000)
   ```

3. `initialize()` — replace `localStorage.removeItem('inactivity_timeout')` with restoration:
   ```js
   const saved = parseInt(localStorage.getItem('inactivity_timeout') ?? '', 10)
   if (!isNaN(saved)) {
       this.inactivityTimeout = saved === 0 ? Infinity : saved * 60 * 1000
   }
   ```

4. `resetInactivityTimer()` — add guard before `setTimeout`:
   ```js
   if (this.inactivityTimeout === Infinity) return
   ```

5. `checkInactivity()` — add guard at top:
   ```js
   if (!this.isAuthenticated || this.inactivityTimeout === Infinity) return
   ```

**`resources/js/components/settings/SessionSettings.vue`**

6. Replace empty `setInterval(() => {}, 1000)` with a reactive tick:
   ```js
   const now = ref(Date.now())
   onMounted(() => {
       if (!localStorage.getItem('login_time')) localStorage.setItem('login_time', Date.now().toString())
       tickInterval = setInterval(() => { now.value = Date.now() }, 1000)
       loadSessions()
   })
   ```

7. Rewrite `timeUntilLogout` to use `now.value` + handle Infinity:
   ```js
   const timeUntilLogout = computed(() => {
       if (authStore.inactivityTimeout === Infinity) return t('session_settings.never')
       const left = authStore.inactivityTimeout - (now.value - authStore.lastActivity)
       if (left <= 0) return t('session_settings.now')
       if (left < 60000) return `${Math.floor(left / 1000)} sec`
       return `${Math.floor(left / 60000)} min`
   })
   ```

### i18n
Add `session_settings.never` in `resources/js/locales/fr.json` (`"Jamais"`) and `en.json` (`"Never"`).

### Tests (Dusk) — `tests/Browser/Auth/InactivityTimerTest.php`

| Test | Scenario |
|------|----------|
| `test_timer_shows_minutes_not_seconds` | Set 15 min; countdown shows "15 min" not "15 sec" |
| `test_never_option_shows_never_label` | Click "Jamais"; countdown shows "Jamais" |
| `test_timeout_persists_after_reload` | Select 30 min; reload; Security tab shows 30 min selected |

`dusk` attributes to add: `dusk="timeout-countdown"` on the timeUntilLogout `<p>` element; `dusk="timeout-option-{value}"` on each timeout button.

---

## Task C — Session Refresh Fix

### What to change

**`resources/js/stores/authStore.js` — `refreshToken()`**: add after storing the token:
```js
if (expires_at) localStorage.setItem('token_expires_at', expires_at)
```

**`resources/js/components/settings/SessionSettings.vue` — `refreshSession()`**: guard against already-expired token:
```js
const refreshSession = async () => {
    const expiry = localStorage.getItem('token_expires_at')
    if (expiry && Date.now() > new Date(expiry).getTime()) {
        await authStore.logout()
        return
    }
    authStore.resetInactivityTimer()
    try {
        await authStore.refreshToken()
        // show success toast
    } catch {
        // refreshToken already called logout on failure
    }
}
```

**`app/Services/AuthService.php` — `refreshToken()`**: preserve "remember me" duration:
```php
$oldExpiry = $user->currentAccessToken()->expires_at;
$remember  = $oldExpiry && $oldExpiry->diffInHours(now()) > 24;
$user->currentAccessToken()->delete();
$expiresAt = $remember ? now()->addDays(15) : now()->addHours(24);
```

### Tests (PHPUnit) — `tests/Feature/Auth/TokenRefreshTest.php`

| Test | Scenario | Expected |
|------|----------|----------|
| `refresh_returns_new_token_and_200` | `POST /auth/refresh` | 200 + new token |
| `refresh_preserves_remember_me_duration` | Login remember=true; refresh | New token expires > 14 days from now |
| `refresh_gives_24h_for_normal_login` | Login no remember; refresh | New token expires ~24h from now |
| `refresh_with_expired_token_returns_401` | Manually expire token; refresh | 401 |

`dusk` attribute: add `dusk="refresh-session-btn"` to the refresh button in `SessionSettings.vue`.

---

## Task D — Push Notifications Fix

### Root cause
`NotificationSettings.vue` script is a stub: `loadSettings()` uses `localStorage` (ignores `NotificationPreferenceController`), `saveSettings()` writes to `localStorage` only, and the push toggle is disconnected from `useWebPush.js`. The real backend (`NotificationPreferenceController`, `NotificationPreference` model with `push_enabled`, `PushSubscriptionController`, `WebPushChannel`) is complete and working.

### What to change

**`resources/js/components/settings/NotificationSettings.vue`** — rewrite `<script setup>`:

1. `loadSettings()` → `GET /api/notification-preferences` (use existing `api` instance). Map response to `settings`.
2. `saveSettings()` → `PATCH /api/notification-preferences` with current `settings`. Replace `alert()` with `$toast`.
3. Import `useWebPush`. On mount: call `checkSubscription()` and sync `isSubscribed` → `settings.pushNotifications`.
4. Push toggle `@change` handler: if toggled ON → `subscribe()` (requests browser permission + browser subscription + `POST /api/webpush/subscribe`); if toggled OFF → `unsubscribe()`. Then update preference via API.
5. Show static warning if `isSupported === false`.
6. Show static warning if `permission === 'denied'`.

**Check if `resources/js/api/` has a notification preferences helper.** If not, add:
```js
getNotificationPreferences: () => api.get('/notification-preferences'),
updateNotificationPreferences: (data) => api.patch('/notification-preferences', data),
```

### Tests (PHPUnit) — verify existing tests in `tests/Feature/`
Check whether `NotificationPreferenceController` tests already exist. If not, add `tests/Feature/NotificationPreferences/NotificationPreferenceTest.php`:

| Test | Scenario | Expected |
|------|----------|----------|
| `get_preferences_returns_all_fields` | `GET /api/notification-preferences` | 200 + `push_enabled`, `email_enabled`, `in_app_enabled` |
| `update_push_enabled_persists` | `PATCH` `{push_enabled: false}`; re-fetch | `push_enabled` = false |
| `preferences_are_per_user` | User A sets push=false; user B unaffected | |
| `unauthenticated_cannot_access_preferences` | No token | 401 |

### Tests (Dusk) — `tests/Browser/Profile/NotificationSettingsTest.php`

| Test | Scenario |
|------|----------|
| `test_push_toggle_reflects_real_subscription_state` | No subscription in browser → push toggle starts OFF |
| `test_email_toggle_saves_to_backend` | Toggle email off; save; reload; assert still OFF |
| `test_save_button_calls_api_not_localstorage` | Verify no `notificationSettings` key written to localStorage after save |

`dusk` attributes: `dusk="push-notifications-toggle"`, `dusk="email-notifications-toggle"`, `dusk="save-notifications-btn"`.

---

## Files to Touch (complete list)

| File | Changes |
|------|---------|
| `docs/WORKING_GUIDELINES.md` | Add Guide 28 |
| `app/Services/AuthService.php` | Remove single-session deletion; add `logoutAll()`; fix `refreshToken()` remember-me |
| `app/Http/Controllers/Api/AuthController.php` | Add `logoutAll()` + `revokeSession()` |
| `routes/api.php` | Add `POST /auth/logout-all` + `DELETE /auth/sessions/{tokenId}` |
| `app/Notifications/SessionRevokedNotification.php` | New (database + mail, ShouldQueue) |
| `app/Services/NotificationService.php` | Add `session_revoked` to `wantsEmail()` + `wantsWebPush()` |
| `lang/fr/notifications.php` + `lang/en/notifications.php` | Add `session_revoked.*` keys |
| `resources/js/api/auth.js` | Add `logoutAll()` + `revokeSession(tokenId)` |
| `resources/js/stores/authStore.js` | Fix timer multiplier/divisor/init; handle Infinity; update `logoutAllDevices()`; add `revokeSession()`; fix `refreshToken()` localStorage sync |
| `resources/js/components/settings/SessionSettings.vue` | Reactive tick; per-session revoke; refresh guard; Infinity display; `dusk` attrs |
| `resources/js/components/settings/NotificationSettings.vue` | Full script rewrite: real API + `useWebPush` integration + toast |
| `resources/js/locales/fr.json` + `en.json` | Add `session_settings.never` |
| `tests/Feature/Auth/MultiSessionTest.php` | New — 7 tests |
| `tests/Feature/Auth/TokenRefreshTest.php` | New — 4 tests |
| `tests/Feature/NotificationPreferences/NotificationPreferenceTest.php` | New (if not existing) — 4 tests |
| `tests/Browser/Auth/MultiSessionTest.php` | New — 3 Dusk tests |
| `tests/Browser/Auth/InactivityTimerTest.php` | New — 3 Dusk tests |
| `tests/Browser/Profile/NotificationSettingsTest.php` | New — 3 Dusk tests |

---

## Execution Order

1. Add Guide 28 to `WORKING_GUIDELINES.md`
2. Task B — timer fixes (purely frontend, lowest risk)
3. Task C — refresh fix (small backend + frontend)
4. Task A — multi-session (backend + frontend)
5. Task D — push notifications (NotificationSettings.vue rewrite)
6. PHPUnit → Pint → Larastan → `npm run build`
7. Dusk tests
8. Testing docs per task → update SESSION_STATE + PROGRESSION

---

## Verification Checklist

- [ ] Set 15 min timer → reload → countdown shows "15 min" not "15 sec"
- [ ] Set "Never" → shows "Jamais"; no warning overlay ever appears
- [ ] Click "Actualiser la session" → user stays logged in; `localStorage.auth_token` changes; `token_expires_at` updated
- [ ] Login on two browser profiles → both sessions work simultaneously; sessions list shows 2 rows
- [ ] Revoke non-current session → that browser gets 401; current session unaffected
- [ ] "Tous les appareils" → all sessions killed
- [ ] Push toggle starts OFF when not subscribed; toggle ON → browser permission prompt; after grant → stays ON after reload
- [ ] Email toggle OFF → save → reload → still OFF (not localStorage, real API)
- [ ] `php artisan test --compact` → all green
- [ ] Pint + Larastan → 0 errors
- [ ] `npm run build` → green
