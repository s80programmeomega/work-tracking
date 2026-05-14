# Task 1 — Testing Guide: Queue (database) + Laravel Reverb

## Prerequisites

- App running locally: `php artisan serve` + `npm run dev`
- Database migrated and seeded: `php artisan migrate:fresh --seed`
- `.env` has `QUEUE_CONNECTION=database` and `BROADCAST_DRIVER=reverb`

---

## Test Cases

### 1. Queue driver is database

**Action:** Open a terminal and run:
```bash
php artisan tinker
```
Then inside Tinker:
```php
DB::table('jobs')->truncate();
Queue::push('test-job');
DB::table('jobs')->count(); // should return 1
```

**Expected result:** `count()` returns `1` — the job row is in the `jobs` table.

**How to verify:** The count is non-zero and the row is visible in the `jobs` table.

---

### 2. Reverb server starts successfully

**Action:** In a separate terminal, run:
```bash
php artisan reverb:start
```

**Expected result:** Output shows `INFO Starting server on 0.0.0.0:8080`. No errors.

---

### 3. WebSocket connection in the browser

**Prerequisites:** Reverb server running (`php artisan reverb:start`), Vite dev server running (`npm run dev`), logged into the app.

**Action:** Open the browser console on any authenticated page and run:
```js
import('/resources/js/composables/useEcho.js').then(m => {
    const { echo, connected } = m.useEcho();
    console.log('Echo connected:', connected.value);
});
```

Or more practically — open browser DevTools → Network tab, filter by WS, and refresh the page after Reverb is running.

**Expected result:** A WebSocket connection to `ws://localhost:8080` is established (visible in Network → WS tab).

---

## Negative Cases

- If Reverb server is **not** running, the app should still load — Echo will fail to connect silently (no crash). The `connected` ref in `useEcho.js` will stay `false`.
- Jobs dispatched with `QUEUE_CONNECTION=sync` (test env) are executed immediately and never hit the `jobs` table. This is expected in tests.

---

## Cleanup

- Clear queued jobs after testing: `php artisan queue:flush` or `DB::table('jobs')->truncate()` in Tinker.
- Stop the Reverb server with `Ctrl+C`.
