# Major Upgrades Plan — deferred breaking dependency bumps

> Created 2026-06-08 (Phase 10). These are **breaking** upgrades deliberately deferred
> from the Phase 10 hardening pass. Each is independent; do them in separate branches/PRs,
> not together. Branch from `jonas`.

---

## Why deferred

Phase 10 fixed all safe (non-breaking) security updates. What remains needs **major**
version bumps that change APIs and carry regression risk, so they warrant dedicated,
tested work rather than a quick `--force`.

| # | Upgrade | Driving reason | Risk |
|---|---|---|---|
| A | Laravel 10 → 11 (+ Symfony 7 line) | `laravel/framework` CVE-2026-48019 (CRLF in default `email` rule); only fix is ≥ a patched 11.x | High (framework-wide) |
| B | `vite` 4 → 8 (+ esbuild) | npm moderate advisories (esbuild dev-server request leak) | Medium (build tooling) |
| C | `admin-lte` 3 → 4 (+ summernote) | npm moderate advisories (summernote XSS chain) | Medium-High (UI/CSS) |

---

## A — Laravel 10 → 11

**Pre-work**
- Read the official upgrade guide (laravel.com/docs/11.x/upgrade) for the exact 10→11 deltas.
- Confirm PHP version (11 requires PHP ≥ 8.2 — we're on 8.4 ✓).
- Inventory packages for Laravel 11 compatibility: `laravel/fortify`, `laravel/sanctum` (v4
  for L11), `spatie/laravel-permission`, `spatie/laravel-activitylog`, `maatwebsite/excel`,
  `mews/purifier`, `opcodesio/log-viewer`, `laravel/scout`, `laravel/socialite`,
  `livewire/livewire`. Bump each to its L11-compatible major.

**Structural changes to expect (L11)**
- New slimmed skeleton: `bootstrap/app.php` becomes the central config; `app/Http/Kernel.php`
  and `app/Console/Kernel.php` are **removed** — middleware groups/aliases, exception
  handling, and the schedule move into `bootstrap/app.php` (`->withMiddleware()`,
  `->withExceptions()`, `->withSchedule()`). **This project relies on `app/Http/Kernel.php`
  for the `api` group + `subscription.status`/`subscription.limits` aliases and on
  `app/Console/Kernel.php` for `sanctum:prune-expired` + the digest schedule — all must be
  migrated into `bootstrap/app.php`.** (Optional: L11 can keep the old kernels, but plan to
  migrate.)
- `config/sanctum.php`, `config/cors.php` — re-check defaults vs our hardened values
  (esp. our env-driven `CORS_ALLOWED_ORIGINS` and the per-token 7-day expiry).
- Casts: L11 supports the `casts()` method (our models use `$casts` arrays — still fine).

**Test strategy**
1. Branch `chore/upgrade-laravel-11` from `jonas`.
2. `composer require laravel/framework:^11 laravel/sanctum:^4 …` (all compat majors together).
3. Migrate the two kernels into `bootstrap/app.php`; re-register `subscription.status`,
   `subscription.limits`, the `api` throttle, prune + digest schedule.
4. `php artisan test` — full suite; fix breakages package-by-package.
5. `composer audit` → confirm CVE-2026-48019 gone. Pint + Larastan + `npm run build`.
6. Manual smoke: login/2FA, a payment (fake mode), search, dashboards.

**Rollback:** it's an isolated branch; abandon if the package matrix isn't ready.

---

## B — vite 4 → 8

**Pre-work**
- Check `@vitejs/plugin-vue` + `laravel-vite-plugin` versions support Vite 8.
- Review `vite.config.js` for deprecated options.

**Steps**
1. Branch `chore/upgrade-vite-8`.
2. `npm i -D vite@8 laravel-vite-plugin@latest @vitejs/plugin-vue@latest`.
3. `npm run build` + `npm run dev` — fix config/HMR breakages.
4. Hard-refresh the SPA; smoke-test a few pages + the manifest path.
5. `npm audit` → confirm esbuild advisories cleared.

**Risk:** build-tooling only (no runtime PHP). Low blast radius, but verify the production
build output + asset manifest.

---

## C — admin-lte 3 → 4 (+ summernote)

**Pre-work**
- AdminLTE 4 is a **major redesign** (Bootstrap 5, dropped jQuery dependencies, changed
  markup/CSS classes). This project imports AdminLTE globally in `app.js` — expect visual
  regressions across layouts.
- Check whether summernote (rich-text) is still used, or if the Tiptap editor (Phase 7) has
  replaced it — **if summernote is unused, removing it may clear the advisory without the
  AdminLTE 4 jump.** Verify first: `grep -rn summernote resources/`.

**Steps**
1. Branch `chore/upgrade-admin-lte-4` (or `chore/drop-summernote` if unused).
2. If unused → remove summernote dep, `npm audit`, build, done.
3. If used → bump AdminLTE 4, fix markup/class changes layout-by-layout; full visual QA in
   light + dark mode; check the stagger/animation utilities still apply.
4. `npm run build`; manual UI pass.

**Risk:** highest visual-regression risk of the three. Do last, with screenshots before/after.

---

## Suggested order

1. **B (vite)** — lowest risk, clears esbuild advisories quickly.
2. **C (summernote/admin-lte)** — check-if-unused first; may be a cheap removal.
3. **A (Laravel 11)** — largest; schedule a dedicated window; clears the last composer CVE.

> Tracking: the underlying advisories + deferral are recorded in
> `SECURITY_PERF_BASELINE.md` (Phase 10 "Deferred" section).
