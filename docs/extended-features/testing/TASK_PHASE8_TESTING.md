# Phase 8 Testing Guide — Subscription Plans + global gate

> Branch: `feature/phase8-subscription-plans`
> Prerequisites: `php artisan migrate` + `php artisan db:seed --class=PlanSeeder` + `php artisan serve` + `npm run dev`

---

## Scope

Subscription **plans** + a **per-request subscription-status gate** (shared with Phase 9).
Manual activation only — real MTN MoMo / Orange Money payment processing is Phase 9.

- `plans` table (free / starter / pro) + `Plan` model/seeder.
- `workspace.plan_id`, `subscription_status` (trial/active/lapsed/locked/free/pending), `subscription_ends_at`.
- Plan-aware `SubscriptionService` (limits from the effective plan; `-1` = unlimited).
- **Global `CheckSubscriptionStatus` middleware** on every authenticated API request.
- `SubscriptionController`: plans, current, select, activate, lock, unlock.
- Frontend `/subscription/plans` page + 402 → plans redirect.

---

## The global gate — how it behaves

`subscription.status` is on the `auth:sanctum` route group, so it runs on **every authenticated request**:

| Workspace state | Result |
|---|---|
| `locked` | **HTTP 402** `{subscription_status: 'locked'}` on any non-exempt route |
| `lapsed` / `trial-expired` | downgraded to **free-tier limits** (enforced by the plan-aware service + per-action `subscription.limits`); request itself passes |
| `active` (paid) | passes; limits come from the subscribed plan |
| super_admin | always bypasses |

**Exempt route prefixes** (reachable even when locked, so a workspace can pay/log out):
`auth`, `subscription`, `admin/subscription`, `payment`, `webhooks`, `user`.

---

## Automated tests

```bash
php artisan test --compact tests/Feature/Subscription/
```

Expected: **43 passed** (21 pre-existing + 13 in `SubscriptionStatusTest` + 9 in `AdminPlanTest`).

`tests/Feature/Subscription/AdminPlanTest.php` covers the super-admin **plan CRUD**:
list / create / update / delete, validation, 403 for non-super-admin (checked in the
FormRequest `authorize()` so it precedes validation), and delete guards (free fallback +
in-use plan both → 422).

`tests/Feature/Subscription/SubscriptionStatusTest.php` covers:
- locked workspace → 402 on a protected route (`/api/dashboard`);
- locked workspace can still GET `/api/subscription/plans` (exempt);
- super_admin bypasses the lock;
- active workspace passes;
- plans listing; owner selects free (immediate) vs paid (pending); non-owner 403;
- super_admin activate (mode=paid, status=active, ends_at set); non-super-admin 403; lock→unlock;
- plan-aware limits (Pro = unlimited members; lapsed → free plan fallback).

---

## Pre-commit gates

```bash
vendor/bin/pint --dirty --format agent
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
```

Both clean (Pint `passed`, Larastan `[OK] No errors`).

---

## Manual scenarios

### TC-01 — Plans page + selection (owner)

1. As a workspace owner, open **Plans & subscription** (sidebar) → `/subscription/plans`.
2. Three plans render (Gratuit / Starter / Pro) with prices + features; current plan highlighted.
3. Click **Choisir** on Free → status becomes `free` immediately.
4. Click **Choisir** on Pro → status becomes `pending` (awaiting payment; Phase 9 processes it).

### TC-02 — Global gate / 402 (super_admin lock)

1. As super_admin, `POST /api/admin/subscription/{workspace}/lock`.
2. As that workspace's owner, hit any protected route (e.g. open the dashboard) → **402**, and the SPA redirects to `/subscription/plans`.
3. Confirm `/api/subscription/plans` and `/api/auth/me` still respond (exempt).
4. `POST /api/admin/subscription/{workspace}/unlock` → access restored (status `lapsed`).

### TC-03 — Manual activation (super_admin)

1. `POST /api/admin/subscription/{workspace}/activate` with `{plan_id, period_days}`.
2. Workspace becomes `subscription_mode=paid`, `subscription_status=active`, `subscription_ends_at` set; the subscribed plan's limits apply.

### TC-04 — Lapse reconciliation

1. Set a workspace `subscription_status=active` with `subscription_ends_at` in the past.
2. Next authenticated request → middleware `reconcileStatus` flips it to `lapsed` (free-tier limits); not a hard lock.

---

## Endpoints reference

```
GET  /api/subscription/plans                          catalogue (auth)
GET  /api/subscription/current                         état du workspace courant
POST /api/subscription/select   {plan_id}              propriétaire choisit un plan
POST /api/admin/subscription/{workspace}/activate      super_admin (manual)
POST /api/admin/subscription/{workspace}/lock          super_admin (402 hard-lock)
POST /api/admin/subscription/{workspace}/unlock        super_admin

GET    /api/admin/plans            super_admin — list all plans (+workspaces_count)
POST   /api/admin/plans            super_admin — create a plan
PUT    /api/admin/plans/{plan}     super_admin — update a plan
DELETE /api/admin/plans/{plan}     super_admin — delete (422 if free or in use)
```

### TC-05 — Plan management (super_admin)

1. As super_admin, open **Plans** (admin sidebar) → `/admin/plans`.
2. **New plan** → fill slug/names/price/limits (−1 = unlimited) → save → appears in the table.
3. **Edit** a plan → change price/limits → save.
4. **Delete** is offered only for non-free plans with 0 workspaces; deleting the free plan or an in-use plan returns 422 with an explanatory message.

> Deploy note (Phase 8): run `php artisan db:seed --class=PlanSeeder` on each environment so the plans (incl. the free fallback) exist; the gate's free-tier fallback depends on a plan with `is_free = true`.
