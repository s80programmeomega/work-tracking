# Caching — Manual Testing Guide

Adaptive-TTL caching for `dashboard/index`, `admin/stats`, and `search`. See
[`docs/caching/CACHING_STRATEGY.md`](../caching/CACHING_STRATEGY.md) for the design.

---

## Prerequisites

- `CACHE_DRIVER=redis` in `.env`, Redis running and reachable
- `php artisan config:clear` after any `.env` cache-driver change
- At least two user accounts in **different workspaces** (for tenant-isolation checks) and one
  super-admin account
- `php artisan serve` running
- A way to inspect Redis keys — either `redis-cli` or `php artisan tinker`

```bash
redis-cli -n 1 keys '*'        # CACHE DB is index 1 by default (REDIS_CACHE_DB)
redis-cli -n 1 ttl "<key>"      # remaining TTL in seconds
```

> Cache keys in Redis are prefixed (`REDIS_PREFIX`, e.g. `work_tracking_database_`). If
> `redis-cli keys '*'` returns prefixed names, use the full prefixed key for `get`/`ttl`.

---

## TC-1 — Dashboard response is cached (hit reduces queries to ~0)

**Steps:**
1. `php artisan tinker` → `DB::flushQueryLog(); DB::enableQueryLog();`
2. As a normal user, `GET /api/dashboard` (first request — cache miss)
3. In tinker: `count(DB::getQueryLog())` → note the number (~34 minus whatever Phase 10 already
   eliminated)
4. `DB::flushQueryLog();`
5. `GET /api/dashboard` again immediately (same user, same filters — cache hit)
6. In tinker: `count(DB::getQueryLog())`

**Expected:**
- Step 3: a substantial query count (dozens)
- Step 6: **0** queries related to dashboard computation (the cache key lookup itself goes through
  Redis, not MySQL)

---

## TC-2 — Dashboard cache key is per-user (tenant isolation)

**Steps:**
1. Log in as **User A** (workspace W1), `GET /api/dashboard`
2. Log in as **User B** (workspace W2), `GET /api/dashboard`
3. Inspect Redis: `redis-cli -n 1 keys '*dashboard:*'`

**Expected:**
- Two distinct keys: `dashboard:{A.id}:accessible:m=-:s=-:p=-` and
  `dashboard:{B.id}:accessible:m=-:s=-:p=-`
- User B's response never contains data scoped to workspace W1 (project names, task titles,
  team members)

---

## TC-3 — Dashboard cache key changes with filters

**Steps:**
1. As User A, `GET /api/dashboard` (no filters)
2. `GET /api/dashboard?member_id=5`
3. `GET /api/dashboard?project_status=en_cours&priority=elevee`
4. Inspect Redis keys

**Expected:**
- Three distinct keys:
  - `dashboard:{A.id}:accessible:m=-:s=-:p=-`
  - `dashboard:{A.id}:accessible:m=5:s=-:p=-`
  - `dashboard:{A.id}:accessible:m=-:s=en_cours:p=elevee`
- Each returns data filtered accordingly (no cross-contamination between filter sets)

---

## TC-4 — Dashboard cache expires (~60s base TTL)

**Steps:**
1. As User A, `GET /api/dashboard`
2. Immediately check `redis-cli -n 1 ttl "<dashboard key>"`
3. Wait past the TTL (60s × current load multiplier — see TC-7 for how to check the multiplier;
   at idle this is 60s)
4. `GET /api/dashboard` again, check the dashboard reflects any data changes made during the wait
   (e.g. create a new task in step 3, confirm it now appears)

**Expected:**
- TTL starts at ≤60s (idle) and counts down
- After expiry, the next request recomputes (fresh data visible) and resets the TTL

---

## TC-5 — Admin stats cached globally, ~5min TTL

**Steps:**
1. Log in as super-admin, `GET /api/admin/stats`
2. Inspect `redis-cli -n 1 keys '*admin:stats*'` and `ttl` on it
3. Log in as a **different** super-admin, `GET /api/admin/stats`
4. Confirm both requests serve the same cached payload (check timestamps/counts match exactly)

**Expected:**
- Single key `admin:stats` shared across all super-admins (not per-user)
- TTL starts at ≤300s (idle)
- Second super-admin gets the same cached numbers as the first (same key)

---

## TC-6 — Search cache: authorization runs before caching

**Steps:**
1. As a user **without** search access (e.g. `observateur`/`utilisateur` role — `search` tier
   "none"), `GET /api/search?q=test`
2. Confirm response is **403**
3. Inspect Redis: `redis-cli -n 1 keys '*search:*'`

**Expected:**
- 403 response
- **No** `search:{user_id}:none:...` key created — unauthorized requests must never populate the
  cache

---

## TC-7 — Search cache key isolation across users/tiers/workspaces

**Steps:**
1. As User A (search.scoped, workspace W1), `GET /api/search?q=rapport`
2. As User B (search.global, workspace W1), `GET /api/search?q=rapport`
3. As super-admin (global tier, no workspace param), `GET /api/search?q=rapport`
4. Inspect Redis keys

**Expected:**
- Three distinct keys (different `user_id`/`tier`/`workspace_id` segments), e.g.:
  - `search:{A.id}:scoped:w={W1.id}:q={md5('rapport')}:t=...:p=1:pp=...`
  - `search:{B.id}:global:w={W1.id}:q={md5('rapport')}:t=...:p=1:pp=...`
  - `search:{superadmin.id}:global:w=global:q={md5('rapport')}:t=...:p=1:pp=...`
- Each user's results respect their own scope (User A sees only their assigned-resource matches;
  User B/super-admin see workspace-wide / global matches)

---

## TC-8 — Search cache key is order-independent for `types`

**Steps:**
1. `GET /api/search?q=test&types[]=taches&types[]=documents`
2. `GET /api/search?q=test&types[]=documents&types[]=taches`
3. Inspect Redis keys

**Expected:**
- Both requests produce the **same** cache key (types are sorted before hashing) — only one
  `search:...:t=documents,taches:...` key exists, second request is a cache hit

---

## TC-9 — Adaptive TTL grows under load

**Steps:**
1. At idle, `GET /api/dashboard`, check TTL on `dashboard:{user.id}:...` (~60s)
2. Generate load: fire ~600+ requests/minute against any `api/*` endpoint (e.g. a quick loop
   hitting `GET /api/user` 10×/sec for a minute)
3. `GET /api/dashboard` again, check the new TTL

**Expected:**
- Step 1: TTL ≈ 60s (multiplier ≈ 1.0)
- Step 3: TTL > 60s, approaching 180s (60 × 3.0) as load approaches/exceeds 600 req/min
- `redis-cli -n 1 get "<prefix>adaptive_cache:req_rate"` shows a non-zero counter that decays
  after load stops (60s sliding window)

---

## TC-10 — `RecordRequestRate` never breaks a request

**Steps:**
1. Temporarily stop Redis (`systemctl stop redis` or equivalent) — **only on a local/dev
   environment**
2. `GET /api/dashboard`

**Expected:**
- Request still completes (200 or whatever the endpoint normally returns when its own
  `Cache::remember` also fails — check `storage/logs/laravel.log` for cache-connection errors,
  but the HTTP response itself must not be a 500 caused by `RecordRequestRate`)
- Restart Redis afterwards and confirm caching resumes normally

---

## Automated Tests

| Suite | File | Coverage |
|---|---|---|
| PHPUnit Unit | `tests/Unit/AdaptiveCacheTest.php` | TTL scaling (idle/moderate/capped), `remember()` single-execution, `recordHit()` counter |
| PHPUnit Feature | `tests/Feature/Cache/DashboardCacheTest.php` | Per-user cache key creation, cross-user key isolation |

Run with:
```bash
php artisan test --compact tests/Unit/AdaptiveCacheTest.php tests/Feature/Cache/DashboardCacheTest.php
```

---

## Cleanup

After testing, flush the cache to remove test-generated keys:

```bash
php artisan cache:clear
```

Or selectively, via `redis-cli -n 1`:
```bash
redis-cli -n 1 --scan --pattern '*dashboard:*' | xargs redis-cli -n 1 del
redis-cli -n 1 --scan --pattern '*search:*' | xargs redis-cli -n 1 del
redis-cli -n 1 del admin:stats
```
