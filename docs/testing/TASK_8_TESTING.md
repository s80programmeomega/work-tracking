# Task 8 — Real-Time Notifications — Testing Guide

This guide covers manual verification of Task 8 (real-time broadcast + channel resolution + hierarchy propagation + deduplication) before merge.

## Prerequisites

1. **Reverb running**: `php artisan reverb:start` (separate terminal)
2. **Queue worker**: `php artisan queue:work --once` (or daemon)
3. **Vite dev server**: `npm run dev`
4. **Backend**: `php artisan serve`
5. Fresh DB: `php artisan migrate:fresh --seed`

---

## Test Case 1 — Live notification badge updates without page refresh

**Goal:** When a notification is dispatched on the server, the bell badge increments in real time.

**Steps:**
1. Open two browser windows. In window A, log in as `collaborateur@worktracking.com`. In window B, log in as `cadre@worktracking.com` (or another user who will trigger notifications for `collaborateur`).
2. Note the current bell badge count in window A.
3. In window B, perform an action that notifies `collaborateur` — for example, return a result submitted by `collaborateur` at the N0 step.
4. Switch back to window A **without refreshing**.

**Expected:**
- The bell badge in window A shows the new count within ~1 second.
- A toast notification appears in window A with the task title.
- `php artisan reverb:start` logs show the broadcast event firing on `App.Models.User.{collaborateur_id}`.

---

## Test Case 2 — `channelsFor()` honours event type

**Goal:** High-signal events (`renvoye_n0`, `bypass`, etc.) include `mail`. Low-signal events (`approuve_n0`, `score_updated`) don't.

**Steps via tinker:**
```php
$user = User::first();
$svc = app(\App\Services\NotificationService::class);

dump($svc->channelsFor($user, 'renvoye_n0'));     // ['database', 'broadcast', 'mail']
dump($svc->channelsFor($user, 'approuve_n0'));    // ['database', 'broadcast']
dump($svc->channelsFor($user, 'score_updated')); // ['database', 'broadcast']
dump($svc->channelsFor($user, 'bypass'));         // ['database', 'broadcast', 'mail']
```

**Expected:** Output matches the comments above.

---

## Test Case 3 — Hierarchy propagation

**Goal:** Notifying a `cadre` also notifies the workspace `directeur` and any workspace `manager`s, without duplicates.

**Steps via tinker:**
```php
$ws = Workspace::first();
$cadre = User::role('utilisateur')->whereHas('workspaceMembers', fn($q) => $q->where('workspace_id', $ws->id))->first();
$svc = app(\App\Services\NotificationService::class);
$resultat = TacheResultat::first();
$author = User::first();

\Illuminate\Support\Facades\Notification::fake();
$svc->notifyHierarchy($cadre, $ws, new \App\Notifications\ResultatSoumisN0Notification($resultat, $author), 'soumis_n0', $resultat->id);

\Illuminate\Support\Facades\Notification::assertSentTo($cadre, \App\Notifications\ResultatSoumisN0Notification::class);
\Illuminate\Support\Facades\Notification::assertSentTo($ws->owner, \App\Notifications\ResultatSoumisN0Notification::class);
```

**Expected:** Cadre, directeur, and every workspace manager all receive the notification. A user holding multiple roles (e.g., manager who is also directeur) receives it once.

---

## Test Case 4 — Deduplication within the 5-minute window

**Goal:** Re-sending the same notification within 5 minutes is suppressed.

**Steps:**
1. Log in as `directeur@worktracking.com`.
2. In tinker:
   ```php
   $u = User::where('email', 'directeur@worktracking.com')->first();
   $r = TacheResultat::first();
   $author = User::first();
   $u->notify(new \App\Notifications\ResultatSoumisN0Notification($r, $author));
   ```
3. Check the bell — there's one new notification.
4. Run the same command again immediately.
5. Refresh the bell.

**Expected:**
- After step 4, `Log::info('Notification dédupliquée', ...)` appears in `storage/logs/laravel.log`.
- The bell shows **one** new notification, not two.
- After waiting 5+ minutes and retrying, a second row would land — verifying the time window resets.

---

## Test Case 5 — Permission gate

**Goal:** Only the workspace `owner`/`directeur` can manage workspace-level notification preferences.

**Steps:**
1. As `directeur@worktracking.com`, hit `GET /api/workspaces/{id}` — the response's `user_permissions.can_manage_notification_preferences` should be `true`.
2. As `collaborateur@worktracking.com`, same call — `false`.

**Expected:** Matches above.

---

## Cleanup

```bash
php artisan migrate:fresh --seed
```

---

## Out of scope for this PR (deferred to a follow-up)

- **Web Push** — requires `minishlink/web-push` Composer package, VAPID key generation, and service-worker registration on the frontend. Substantial enough to deserve a dedicated PR.
- **Daily email digest** — Blade templates + scheduled command. Separate concern from real-time notifications.

Both are documented in the IMPLEMENTATION_PLAN Task 8 Current Section as deferred items.

---

## Automated Coverage

- `tests/Feature/NotificationServiceTest.php` — 11 PHPUnit tests covering `channelsFor()` (3 cases), `dedupKey()`, `isDuplicate()` (3 cases), `notifyHierarchy()` (2 cases), `dedup_key` field presence, and `via()` returning `broadcast`.
- `tests/Browser/Notifications/NotificationBellTest.php` — 2 Dusk tests: bell renders the unread badge, hides it when there are no unread notifications.

Total project tests: **101 passing**.
