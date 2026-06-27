# Step D Testing — Push Notifications Fix

## What was changed

| # | Change | Detail |
|---|--------|--------|
| D1 | `NotificationSettings.vue` loads from real API | `GET /api/notification-preferences` on mount |
| D2 | Save writes to real API | `PATCH /api/notification-preferences` |
| D3 | Push toggle wired to `useWebPush` | `subscribe()` / `unsubscribe()` called on toggle |
| D4 | Initial push state from `checkSubscription()` | Shows real browser subscription state, not localStorage |
| D5 | Browser-support warning shown | Displayed if `isSupported === false` |
| D6 | Permission-denied warning shown | Displayed if `permission === 'denied'` |
| D7 | `alert()` replaced with `vue-toastification` | `toast.success` / `toast.error` |
| D8 | `dusk` attributes added | `push-notifications-toggle`, `email-notifications-toggle`, `save-notifications-btn` |

---

## Manual Test Cases

### TC-D-01 — Push toggle starts OFF if no browser subscription exists

**Steps:**
1. Open a fresh browser profile (no push subscription).
2. Sign in and open Profile → Notifications.

**Expected:** The "Notifications push" toggle is OFF (unchecked). It does NOT default to ON.

---

### TC-D-02 — Toggling push ON requests browser permission

**Steps:**
1. Ensure no existing push subscription.
2. Open Profile → Notifications.
3. Click the push toggle to ON.

**Expected:** Browser shows a permission prompt "Autoriser les notifications ?". After granting, the toggle stays ON.

---

### TC-D-03 — Push subscription is created server-side after granting

**Steps:**
1. Grant push permission (TC-D-02).
2. Check `push_subscriptions` table in the database.

**Expected:** A new row exists for the current user with the endpoint and keys.

---

### TC-D-04 — Toggling push OFF unsubscribes the browser

**Steps:**
1. With push enabled, toggle the push switch to OFF.
2. Click "Enregistrer".

**Expected:** Browser subscription is revoked. `push_subscriptions` row is deleted. Reloading the page shows the toggle as OFF.

---

### TC-D-05 — Email toggle save persists across reload

**Steps:**
1. Toggle "Notifications par email" OFF.
2. Click "Enregistrer" → toast "Paramètres enregistrés avec succès" appears.
3. Reload the page and reopen Profile → Notifications.

**Expected:** Email toggle is still OFF.

---

### TC-D-06 — Browser-not-supported warning shows on incompatible browser

**Steps:**
1. Open the app in a browser that lacks Web Push (e.g. old Safari, or disable ServiceWorker in DevTools).
2. Open Profile → Notifications.

**Expected:** A warning "Votre navigateur ne supporte pas les notifications push" appears below the push toggle. The toggle is disabled (greyed out).

---

### TC-D-07 — Permission-denied warning shows when blocked

**Steps:**
1. Block notifications for the site in browser settings.
2. Open Profile → Notifications.

**Expected:** Warning "Permission refusée dans le navigateur. Activez-la dans les paramètres du navigateur." appears. The toggle is disabled.

---

### TC-D-08 — Saving shows a toast, not a native alert()

**Steps:**
1. Make any change to notification settings.
2. Click "Enregistrer".

**Expected:** A toast notification appears in the corner of the screen. No `alert()` native dialog.

---

### TC-D-09 — In-app notifications preference persists

**Steps:**
1. Toggle "Notifications dans l'app" OFF.
2. Save.
3. Reload.

**Expected:** In-app toggle is still OFF after reload.
