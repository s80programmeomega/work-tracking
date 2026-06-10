# Caching Strategy

> **Branch:** `feature/data-caching` · **Commit:** `84a68db` · **Date:** 2026-06-10
> **Status:** Implemented, tested, not yet merged into `jonas`.

---

## Why

Phase 10 (`chore/hardening-pass`, 2026-06-09) profiled every endpoint with `DB::listen` and fixed
N+1 queries (dashboard 74→34, admin/workspaces 41→12, admin/stats 44→22). After that pass, three
endpoints remained genuinely expensive **and** frequently hit:

| Endpoint | Queries (post-Phase-10) | Frequency |
|---|---|---|
| `GET /api/dashboard` | ~34 | Highest-traffic page — every user, every load |
| `GET /api/admin/stats` | ~20 | Super-admin dashboard, polled |
| `GET /api/search` | varies (Typesense + per-type queries) | Command palette + search page, frequent during active use |

Everything else profiled was already 1–2 queries — **those were deliberately left uncached**. The
goal was to cache only the real hotspots, not to cache everything reflexively.

This became viable now that **Redis backs the cache** (`CACHE_DRIVER=redis`, `.env.example:28`),
giving fast, shared, TTL-respecting storage across workers.

---

## Design

### 1. `AdaptiveCache` service (`app/Services/AdaptiveCache.php`)

A thin wrapper around `Cache::remember()` where the **effective TTL scales with server load**:

```
effective_ttl = base_ttl × load_multiplier
load_multiplier ∈ [1.0, 3.0]
```

- **Load signal:** a Redis counter (`adaptive_cache:req_rate`) incremented once per HTTP request,
  with a 1-minute sliding TTL — i.e. an approximation of requests/minute. Cheap (`INCR`), unlike
  reading system load (expensive, laggy).
- **Scaling:** linear from `1.0` at 0 req/min to `3.0` at `BUSY_THRESHOLD = 600` req/min (~10
  req/s), capped at `3.0` beyond that.
- **Rationale:** when the server is busy, cache hits matter more (each hit avoided is more
  valuable) and a slightly staler dashboard is an acceptable trade. When idle, TTLs collapse back
  to `base_ttl` — freshness costs nothing because there's spare capacity to recompute.

Public API:

| Method | Purpose |
|---|---|
| `remember(string $key, int $baseSeconds, Closure $callback): mixed` | Cache `$callback()` under `$key` for `ttlFor($baseSeconds)` |
| `ttlFor(int $baseSeconds): int` | Effective TTL for a given base, given current load |
| `loadMultiplier(): float` | Current multiplier `[1.0, 3.0]` |
| `recordHit(): void` | Increment the req/min counter (called by the middleware) |
| `forget(string $key): void` | Manual invalidation (tests / targeted purge) |

### 2. `RecordRequestRate` middleware (`app/Http/Middleware/RecordRequestRate.php`)

Registered last in the `api` middleware group (`app/Http/Kernel.php`). Calls
`AdaptiveCache::recordHit()` **after** the response is built, wrapped in `try/catch` — load
counting must never break or slow down a request.

### 3. Per-endpoint caching

#### `admin/stats` — `AdminController::stats()`

- **Key:** `admin:stats` (single global key — same data for every super-admin).
- **Base TTL:** 300s (~5 min), adaptive up to 15 min under load.
- **Extraction:** the existing query logic was moved verbatim into a private `computeStats(): array`
  method; `stats()` now just wraps it in `$this->cache->remember(...)`.
- **Invalidation:** TTL-only. Acceptable because platform-wide stats (workspace counts, user
  growth, task totals) tolerate a few minutes of staleness.
- **Impact:** 20 queries → 0 on cache hit.

#### `dashboard/index` — `DashboardController::index()`

- **Key:** `dashboard:{user_id}:{workspace_param}:m={member_id}:s={project_status}:p={priority}`
  — `workspace_param` is either the requested `workspace_id` or the literal string `accessible`
  (super-admin "all accessible workspaces" view); filter components default to `-` when absent.
- **Base TTL:** 60s, adaptive up to 3 min under load.
- **Extraction:** same pattern — `computeDashboard($request, $user, $memberId, $projectStatus, $priority): array`.
- **Tenant isolation (critical):** the key is **per-user**, not per-workspace alone, so a
  super-admin viewing workspace A's dashboard and a regular member of workspace A never share a
  cache entry, and two different users never collide. Verified by
  `tests/Feature/Cache/DashboardCacheTest.php::test_dashboard_cache_keys_are_isolated_between_users`.
- **Impact:** 34 queries → 0 on cache hit.

#### `search` — `SearchController::search()`

- **Key:** `search:{user_id}:{tier}:w={workspace_id|global}:q={md5(query)}:t={sorted types}:p={page}:pp={per_page}`
- **Base TTL:** 60s, adaptive up to 3 min under load.
- **Authorization runs first:** the 403 permission gates (role tier resolution, workspace
  membership) execute **before** the cache lookup — only the search computation itself
  (`searchType()` calls across requested `$types`) is memoized. An unauthorized request never
  reaches `remember()`, so it's never cached and can't poison another user's results.
- **Cache-key dimensions:** `user_id` + `tier` (search.global / search.scoped / none) +
  `workspace_id` (or `global` for super-admin cross-workspace search) + the query string (hashed)
  + the sorted list of requested `types` + pagination. Sorting `types` before hashing means
  `?types[]=taches&types[]=documents` and `?types[]=documents&types[]=taches` share a cache entry
  (same result set, different request order) without colliding with a different `types` set.
- **Why this is safe against the Phase 6 cross-workspace leak:** the Phase 6 incident
  (`docs/extended-features/PHASE6_SEARCH_PLAN.md` / PERMISSIONS_MATRIX changelog 2026-06-06) was a
  **scoping** bug — the Typesense `fromRaw` path bypassed the Eloquent `->query()` scope. Caching
  doesn't reintroduce that risk because the cache key itself is scoped per-tenant; even if two
  users hit the same query string, they get different keys (different `user_id`/`tier`/`workspace_id`)
  and therefore different cache entries.

---

## Invalidation Policy

**TTL-only — no bust-on-write hooks.** All three caches expire naturally within 1–5 minutes
(shorter when idle, longer under load, capped at 3×). This was a deliberate choice over wiring
`Cache::forget()` into every model observer that could affect dashboard/stats/search data:

- **Self-healing:** a short TTL means any staleness resolves itself within seconds to minutes,
  with no risk of a missed invalidation hook leaving permanently stale data (a real failure mode
  in this codebase — see Guide 14/15 history of forgotten wiring steps).
- **Low blast radius:** none of the three cached payloads are used for authorization decisions or
  financial state — they're read-only aggregates and search results. A few seconds of staleness
  (e.g. a just-created task not yet appearing in `admin/stats`) is acceptable.
- **Simplicity:** no new model observers, no cache-tag dependencies, nothing to keep in sync as
  new fields are added to the dashboard/stats payloads.

If a future cached endpoint **does** need write-driven invalidation (e.g. a per-entity detail page
where staleness is visibly wrong to the user), use `AdaptiveCache::forget($key)` from the relevant
service/observer rather than `Cache::tags()` (the `redis` cache driver here is configured without
tag support assumptions).

---

## Testing

| File | Coverage |
|---|---|
| `tests/Unit/AdaptiveCacheTest.php` | TTL scaling at idle / moderate / capped load; `remember()` caches on first call only; `recordHit()` increments the rate counter |
| `tests/Feature/Cache/DashboardCacheTest.php` | Dashboard response is cached under a per-user key; two users get distinct, non-colliding cache keys |

Both suites pass alongside the existing 84 admin/search/subscription tests. Pint + Larastan clean.

---

## Operational Notes

- **Requires Redis.** `CACHE_DRIVER=redis` must be set (already in `.env.example`). On the file
  driver, `AdaptiveCache` still works (Laravel's `Cache` facade abstracts the store) but the
  req/min counter and cached payloads will be per-filesystem rather than shared across workers —
  fine for local dev, not representative of production behavior.
- **No new artisan commands or scheduled jobs.** The request-rate counter self-expires (60s TTL);
  there's nothing to clean up.
- **Debugging a "stale data" report:** check `Cache::get('dashboard:{user_id}:...')` /
  `Cache::get('admin:stats')` / `Cache::get('search:...')` via `tinker` — if present, the TTL
  (`Cache::getRedis()->ttl($key)` with the configured prefix) tells you how much longer it lives.
  Worst case is `base_ttl × 3` (5/15/3 minutes respectively).

---

## Future Candidates (not yet cached, deliberately)

These were profiled in Phase 10 and found to be 1–2 queries already — caching them would add
complexity (key design, isolation guarantees, tests) for negligible DB savings:

- `GET /api/admin/workspaces` (12 queries, paginated — caching paginated+filtered lists is
  higher-risk for staleness vs. payoff)
- `GET /api/admin/users` (similar — paginated, filterable)
- Per-entity `show` endpoints (`projets/{id}`, `taches/{id}`, etc.) — already cheap with eager
  loading, and caching these would need write-invalidation (they're frequently mutated), reopening
  the bust-on-write question this design avoided.

If a future profiling pass finds one of these has become expensive (e.g. a new eager-load
regression), follow the same pattern: extract the computation into a private `computeX()` method,
wrap with `AdaptiveCache::remember()`, and write an isolation test before merging.
