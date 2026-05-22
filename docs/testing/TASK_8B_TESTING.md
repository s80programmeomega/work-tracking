# Task 8b — Web Push Notifications — Testing Guide

## Prerequisites

1. Backend: `php artisan serve` (HTTPS not required locally — browsers allow `http://localhost`)
2. Frontend: `npm run dev` (the service worker is served from `/sw-webpush.js`)
3. Queue worker: `php artisan queue:work` (notifications use `ShouldQueue`)
4. Reverb (still required for Task 8 realtime): `php artisan reverb:start`
5. VAPID keys generated: `php artisan webpush:generate-vapid` → copy into `.env`
6. Fresh DB with demo data: `php artisan migrate:fresh --seed`

**Important — VAPID keys:**
- Without `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` in `.env`, the channel is inert (the code detects this and logs a warning, but does not crash).
- The frontend fetches the public key via `GET /api/webpush/vapid-key`; missing config returns 503.

---

## Case 1 — Subscribing a device to push

**Goal:** an authenticated user can authorize and activate push notifications from the preferences page.

**Steps:**
1. Log in (any role is sufficient).
2. Open **Notification Preferences** (`/notifications/preferences`).
3. Turn on the "Push notifications" master switch (if not already on).
4. Under the toggle, the "This device" panel appears with an **Enable** button.
5. Click **Enable**.

**Expected:**
- A native browser prompt asks for notification permission.
- Granting it switches the page state to "Subscribed to push notifications on this browser".
- In the DB: a new row in `push_subscriptions` (verify via tinker):
  ```php
  \App\Models\PushSubscription::latest()->first()
  ```
  → `user_id` matches, `endpoint` starts with `https://fcm.googleapis.com/...` (Chrome) or `https://updates.push.services.mozilla.com/...` (Firefox), `active = true`.

**Troubleshooting:**
- Denying the prompt → the panel shows "Permission denied. Re-enable notifications…"
- If the button does not appear, the browser does not support Web Push.
- F12 → Application → Service Workers should show `/sw-webpush.js` active.

---

## Case 2 — Receiving a real push

**Goal:** trigger a server-side notification and see it appear as a system notification.

**Steps:**
1. Prereq: complete Case 1 (at least one device subscribed for user `cadre@worktracking.com`).
2. In another window, log in as `collaborateur@worktracking.com`.
3. Submit a result on a task where `cadre@` is the N0 responsable.
4. On `cadre@`'s side, with no active window, a system notification should appear within seconds.

**Expected:**
- A system notification with the task title.
- Clicking it opens the browser tab on the task page.

**Why this flow:**
- `submit_result` triggers `ResultatSoumisN0Notification`.
- `via()` calls `channelsFor(notifiable, 'soumis_n0')`. Note: `soumis_n0` is low-signal for push, so it will NOT push by default. To actually test push, trigger a high-signal event:
  - N0 returns a result → `ResultatRenvoyeNotification` → push ✓
  - Bypass activated → `BypassActivatedNotification` → push ✓
  - 48h N0 timeout → `ResultatTransmisAutoNotification` → push ✓

---

## Case 3 — Unsubscribing

**Goal:** remove this device from the push recipients.

**Steps:**
1. Preferences page → "This device" panel → **Disable** button.
2. In the DB, the matching `push_subscriptions` row has `active = false` (preserved for audit, not deleted).
3. The browser no longer receives push notifications.

---

## Case 4 — Automatic cleanup on dead endpoint (410 Gone)

**Goal:** verify that a subscription expired on the push service side is automatically soft-disabled on the server.

**Hard to reproduce manually** — depends on push service behavior.

**Practical method:**
1. Subscribe a browser locally.
2. Unregister the service worker from F12 → Application → Service Workers → Unregister.
3. Server-side, trigger a push (e.g. via tinker: `$user->notify(new ResultatRenvoyeNotification(...))`).
4. Check the logs: `tail -f storage/logs/laravel.log` → you should see `'Souscription Web Push désactivée — endpoint expiré'` with `status_code: 410` or `404`.
5. In the DB: `active = false` on the row.

---

## Case 5 — User permission (push_enabled = false)

**Goal:** if the user turns off the master switch, no push is sent even if an active subscription exists.

**Steps:**
1. Have an active subscription (Case 1).
2. Preferences page → turn off the "Push notifications" toggle.
3. Trigger a high-signal event (renvoye_n0, bypass, …).
4. **No push notification** should appear (the row stays `active = true` in the DB, but `channelsFor()` does not add the `webpush` channel).

**Quick check via tinker:**
```php
$user = \App\Models\User::find(ID);
app(\App\Services\NotificationService::class)->channelsFor($user, 'renvoye_n0');
// If push_enabled = false → ['database', 'broadcast', 'mail'] (no WebPushChannel)
```

---

## Case 6 — Multiple devices

**Goal:** the same user can be subscribed on multiple browsers/devices, and all of them receive the push.

**Steps:**
1. Log in as `cadre@worktracking.com` on Chrome → subscribe (Case 1).
2. Log in on Firefox (or another browser/incognito) → subscribe again.
3. Verify that `GET /api/webpush/subscriptions` returns 2 rows (Chrome + Firefox).
4. Trigger a high-signal event → both browsers should receive the notification.

---

## Automated coverage

- **`tests/Feature/WebPushSubscriptionTest.php`** — 13 tests:
  - `vapid-key` returns / 503 / 401
  - `subscribe` creates / upserts (same endpoint = update, not duplicate)
  - `subscribe` validates required fields
  - `unsubscribe` soft-disables (vs deletes)
  - `unsubscribe` is a no-op on an unknown endpoint
  - `index` only lists the user's active subscriptions
  - `channelsFor()`: excludes webpush without an active subscription
  - `channelsFor()`: includes webpush with a subscription + high-signal event
  - `channelsFor()`: excludes webpush when `push_enabled = false`
  - `channelsFor()`: excludes webpush for low-signal events (score_updated, approuve_n0)

- **No Dusk**: testing a real Web Push subscription automatically requires an external push service, which is out of scope for the test runner. The manual Cases 1–6 cover the full chain.

---

## Security — reminder

- `VAPID_PRIVATE_KEY` must NEVER be committed. Make sure `.env` is in `.gitignore`.
- If leaked: regenerate (`php artisan webpush:generate-vapid --force`), redeploy, and accept that all existing subscriptions are invalidated (browsers will need to re-subscribe).
- The VAPID public key is exposed by design — it is what identifies our server to the push service. No risk in publishing it.
