# Task 8 — Real-Time Notifications + Daily Digest — Testing Guide

## Prerequisites

1. Backend: `php artisan serve`
2. Reverb WebSocket server: `php artisan reverb:start` (port 8080 per `.env`)
3. Queue worker: `php artisan queue:work` (notifications use `ShouldQueue`)
4. Scheduler (for digest tests): `php artisan schedule:work` (or just run the command manually)
5. Frontend: `npm run dev` (or `npm run build`)
6. Fresh DB with demo users: `php artisan migrate:fresh --seed`

---

## Test Case 1 — Real-time bell update via Reverb broadcast

**Goal:** when a TacheResultat is submitted, the responsable sees the notification bell badge increment within ~1 second, without page refresh.

**Setup:**
1. Open two browser windows side by side.
2. Window A: log in as `cadre@worktracking.com` (the task responsable for activity 1).
3. Window B: log in as `collaborateur@worktracking.com` (an intervenant on a task in activity 1).
4. Window A: keep the dashboard open. Note the notification bell badge count.

**Steps:**
1. Window B: open a task, click "Soumettre résultat", fill in the form, submit.
2. Without refreshing Window A, watch the bell badge in the top right.

**Expected:**
- Within 1 second, the badge increments by 1.
- A toast appears in Window A with the task title.
- The notification row exists in the database (`notifications` table) for the responsable.
- The Reverb dashboard (if open at `http://localhost:8080/app/{key}`) shows a `BroadcastNotificationCreated` event on `private-App.Models.User.{responsable_id}`.

**If it doesn't work:**
- Check `php artisan reverb:start` is running.
- Check `BROADCAST_DRIVER=reverb` in `.env`.
- Check the queue worker is running (broadcasts queue through it).
- Check browser console for Echo connection errors.

---

## Test Case 2 — Channel selection honours `channelsFor()`

**Goal:** verify `NotificationService::channelsFor()` adds `'mail'` only for high-signal events.

**Steps in tinker:**
```bash
php artisan tinker
```
```php
$user = \App\Models\User::factory()->create();
$svc = app(\App\Services\NotificationService::class);

// High-signal: mail included
$svc->channelsFor($user, 'renvoye_n0');         // ['database', 'broadcast', 'mail']
$svc->channelsFor($user, 'bypass');             // ['database', 'broadcast', 'mail']
$svc->channelsFor($user, 'transmis_auto');      // ['database', 'broadcast', 'mail']
$svc->channelsFor($user, 'escalades_abusives'); // ['database', 'broadcast', 'mail']

// Low-signal: no mail
$svc->channelsFor($user, 'approuve_n0');        // ['database', 'broadcast']
$svc->channelsFor($user, 'score_updated');      // ['database', 'broadcast']

// Unknown event: safe default
$svc->channelsFor($user, 'unknown_xyz');        // ['database', 'broadcast']
```

---

## Test Case 3 — Deduplication suppresses second event within 5 min

**Goal:** confirm `isDuplicate()` correctly suppresses a re-fire of the same `(user, event_type, tache_resultat_id)` within 5 minutes.

**Setup:** as `cadre@worktracking.com` (a task responsable), simulate a duplicate event via Tinker:

```php
use App\Models\{User, TacheResultat};
use App\Notifications\ResultatSoumisN0Notification;
use App\Services\NotificationService;

$responsable = User::where('email', 'cadre@worktracking.com')->first();
$resultat = TacheResultat::factory()->create();
$author = User::factory()->create();

$svc = app(NotificationService::class);

// First fire
$svc->notifyHierarchy($responsable, $resultat->tache->activite->projet->workspace, new ResultatSoumisN0Notification($resultat, $author), 'soumis_n0', $resultat->id);

// Second fire within 5 min — should be deduplicated
$before = $responsable->notifications()->count();
$svc->notifyHierarchy($responsable, $resultat->tache->activite->projet->workspace, new ResultatSoumisN0Notification($resultat, $author), 'soumis_n0', $resultat->id);
$after = $responsable->notifications()->count();

echo "Notifications added: " . ($after - $before) . " (expect 0)\n";
```

**Expected:** the second call adds 0 new notifications. `laravel.log` shows `'Notification dédupliquée'` entries.

---

## Test Case 4 — Hierarchy propagation reaches directeur + managers

**Goal:** a notification fired on a cadre also reaches the workspace directeur and every manager.

**Steps in tinker:**
```php
use App\Models\{User, Workspace, TacheResultat};
use App\Notifications\BypassActivatedNotification;
use App\Services\NotificationService;

$ws = Workspace::first();
$cadre = User::where('email', 'cadre@worktracking.com')->first();
$manager = User::where('email', 'manager@worktracking.com')->first();
$directeur = $ws->owner;

$resultat = TacheResultat::factory()->create();
$author = User::factory()->create();
$notification = new BypassActivatedNotification($resultat, $author);

app(NotificationService::class)->notifyHierarchy($cadre, $ws, $notification, 'bypass', $resultat->id);

echo "Cadre got: "     . $cadre->notifications()->where('type', BypassActivatedNotification::class)->count()     . "\n";
echo "Manager got: "   . $manager->notifications()->where('type', BypassActivatedNotification::class)->count()   . "\n";
echo "Directeur got: " . $directeur->notifications()->where('type', BypassActivatedNotification::class)->count() . "\n";
```

**Expected:** all three counts are 1.

---

## Test Case 5 — Daily digest sent on schedule

**Goal:** confirm the digest command emails users with unread notifications after their `digest_time`.

**Setup:**
1. As `cadre@worktracking.com`, generate some unread database notifications (e.g., have a collaborateur submit several results).
2. In tinker, set the cadre's `digest_time` to a value already passed today:
```php
$user = User::where('email', 'cadre@worktracking.com')->first();
$user->getOrCreateNotificationPreference()->forceFill([
    'digest_frequency' => 'daily',
    'digest_time' => '07:00:00',
    'last_digest_sent_at' => null,
])->save();
```

**Steps:**
1. Dry run: `php artisan notifications:send-digest --dry-run` → should list the cadre.
2. Real run: `php artisan notifications:send-digest` → should queue the mail.
3. Process queue: `php artisan queue:work --once`.
4. Check `storage/logs/laravel.log` for `'Digest envoyé'` entry with `notifications_count`.
5. Check `MAIL_MAILER` outbox (or mail log) for the digest email.

**Expected:**
- Cadre's `last_digest_sent_at` is now set.
- Second run on the same day reports `skipped: 1` (already sent today).

---

## Test Case 6 — Quiet hours skip the digest

**Goal:** users within their `quiet_hours` window are skipped by the digest scheduler.

**Setup in tinker:**
```php
$user = User::where('email', 'cadre@worktracking.com')->first();
$user->getOrCreateNotificationPreference()->forceFill([
    'digest_frequency' => 'daily',
    'digest_time' => '07:00:00',
    'last_digest_sent_at' => null,
    'quiet_hours_enabled' => true,
    'quiet_hours_start' => '22:00:00',
    'quiet_hours_end' => '08:00:00',
])->save();
```

**Steps:**
1. Set system time inside the quiet window (e.g., 23:00) — for tests `Carbon::setTestNow` handles it.
2. Run `php artisan notifications:send-digest`.

**Expected:** the cadre is skipped. Log shows `'Digest reporté — utilisateur dans ses heures silencieuses'`.

---

## Test Case 7 — Permission gate for workspace notification policy

**Goal:** only `owner` / `directeur` can edit workspace-level notification settings.

**Steps:**
1. Log in as `directeur@worktracking.com` → `useWorkspacePermissions().canManageNotificationPreferences` should be `true`.
2. Log in as `manager@worktracking.com` → it should be `false`.
3. Same for `cadre`, `collaborateur`, `stagiaire`, `observateur`.

**Tinker verification:**
```php
use App\Permissions\{ContextualPermissionGate, Permission};
$gate = app(ContextualPermissionGate::class);
foreach (['directeur', 'manager', 'cadre', 'collaborateur', 'stagiaire', 'observateur'] as $email) {
    $u = User::where('email', $email . '@worktracking.com')->first();
    $ws = $u->currentWorkspace;
    if (!$ws) continue;
    echo $email . ": " . ($gate->userCan($u, Permission::NOTIFICATIONS_MANAGE_PREFERENCES, $ws) ? 'YES' : 'NO') . "\n";
}
```

**Expected:** only `directeur` returns YES (workspace owner → owner contextual role → grants the permission).

---

## Automated coverage

- **`tests/Feature/NotificationServiceTest.php`** — 11 tests: channelsFor variants, dedup window, hierarchy propagation, dedup-skips-already-notified, dedup_key in toArray, via() exposes broadcast channel.
- **`tests/Feature/SendDailyDigestCommandTest.php`** — 8 tests: sends to eligible, skips when no unread, skips frequency=none, skips before digest_time, doesn't resend within day, skips during quiet hours, `--dry-run` is no-op, `--user` filter targets one user.
- **`tests/Browser/Notifications/NotificationBellTest.php`** — 2 Dusk tests: badge visible when unread > 0; badge hidden when zero.

---

## Out of scope — Task 8b (Web Push)

Web Push delivery is deferred to a dedicated follow-up branch `feature/v2-task-8b-web-push` from `jonas`. Reasons:
- requires `minishlink/web-push` composer package
- needs VAPID key generation + secure storage (.env)
- requires a service worker on the frontend with browser permission flow
- has independent failure modes (offline devices, rate limits, browser quirks) that deserve dedicated review

The `push_subscriptions` table from earlier work already exists; the channel implementation and registration UI will land in 8b.
