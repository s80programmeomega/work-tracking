# Phase 9 Testing Guide — Payment Gateway (MTN MoMo + Orange Money)

> Branch: `feature/phase9-payment-momo-orange`
> Prerequisites: `php artisan migrate` + `php artisan db:seed --class=PlanSeeder` + your sandbox credentials in `.env` (see `.env.example` block) + `php artisan serve` + `npm run dev`.

---

## Scope

Real payment processing for the Phase 8 subscription flow: a confirmed payment
flips a workspace's selected plan from `pending` → `active`.

- **Provider-agnostic seam** — `PaymentProviderInterface` + `MtnMomoProvider` +
  `OrangeMoneyProvider` + `PaymentProviderRegistry` (Strategy pattern).
- **`payments` table** + `Payment` model (uuid `reference` = idempotency key).
- **`PaymentService`** — `startCheckout()` (create pending + initiate) and
  **idempotent** `confirm()` (a replayed callback never re-activates).
- **`SubscriptionService::activateFromPayment()`** — the `pending → active`
  transition (plan_id, subscription_mode=paid, subscription_status=active,
  subscription_ends_at = now + plan period).
- **Webhook-confirmed activation only** — see security note below.
- Frontend: payment modal on `/subscription/plans` (provider + phone), MTN
  push-poll / Orange redirect, `?payment=return|cancel` handling.

---

## Security model (read this)

Webhook endpoints (`POST /api/webhooks/payment/{momo|orange}`) are **public** —
providers call them with no session. They are therefore **never trusted by body**:

1. The callback is only a "go check" signal.
2. On receipt, `PaymentWebhookController` looks up our `Payment` by reference and
   **re-fetches the real status from the provider's API** (`fetchStatus()`) using
   our own credentials.
3. Only that verified status drives `confirm()`.

So a forged `{"status":"SUCCESS"}` POST cannot activate anything — our server
re-asks the provider, which says PENDING/FAILED, and nothing happens.

Other guards: idempotent `confirm()` (pending-check), owner-only `initiate`,
free plan rejected (422), amount frozen on the payment row, secrets only in
`.env`/`config()` (never logged or committed).

---

## Automated tests

```bash
php artisan test --compact tests/Feature/Payment/
```

Expected: **8 passed**. All use `Http::fake()` — **no network, no credentials**.

`tests/Feature/Payment/PaymentFlowTest.php` covers:
- MTN initiate → pending payment row;
- Orange initiate → returns `redirect_url`;
- non-owner initiate → 403; free plan → 422;
- MTN webhook (status re-verified SUCCESSFUL) → payment succeeded + workspace
  active/paid/plan set;
- webhook FAILED → no activation;
- **idempotent replay** → second callback doesn't re-activate (`subscription_ends_at`
  unchanged);
- unknown reference → 404.

Run the whole subscription + payment area together:
```bash
php artisan test --compact tests/Feature/Payment/ tests/Feature/Subscription/
# 51 passed (8 payment + 43 subscription)
```

---

## Pre-commit gates

```bash
vendor/bin/pint --dirty --format agent
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
```
Both clean.

---

## Manual sandbox testing

> Needs real MTN/Orange **sandbox** credentials in `.env`. Build never asks for or
> stores real values — you fill them yourself.

### TC-01 — MTN MoMo (push) happy path
1. `/subscription/plans` → choose a paid plan → modal → provider **MTN MoMo** + sandbox MSISDN → **Pay**.
2. Backend POSTs requesttopay (202); modal shows "approve on your phone".
3. Frontend polls `GET /api/payment/{ref}/status`. In sandbox, the test number auto-succeeds.
4. When MTN reports SUCCESSFUL (callback or poll), payment → succeeded, workspace → active. Modal closes; "subscription active".

### TC-02 — Orange Money (redirect) happy path
1. Choose paid plan → provider **Orange Money** → **Pay**.
2. Backend calls webpayment, returns `payment_url`; browser redirects to Orange.
3. Pay with OTP on Orange's page → redirected back to `/subscription/plans?payment=return`.
4. Orange POSTs `notif_url`; our webhook re-verifies via `transactionstatus` → activates.

### TC-03 — Webhook re-verification (security)
1. With a pending payment, `curl -XPOST /api/webhooks/payment/momo -d '{"referenceId":"<ref>","status":"SUCCESSFUL"}'` while the provider still reports PENDING.
2. Expect: **no activation** (our server trusts the provider API, not the POST body).

### TC-04 — Lapsed/locked workspace can still pay
1. Lock a workspace (super_admin) → owner hits a normal route → 402 → redirected to `/subscription/plans`.
2. The `payment/*` and `subscription/*` routes remain reachable (exempt) → owner completes payment → workspace reactivated.

---

## Endpoints reference

```
POST /api/payment/initiate            {plan_id, provider, payer_phone}  (auth, owner)
GET  /api/payment/{reference}/status                                    (auth, member)
POST /api/webhooks/payment/momo       public — re-verifies via MTN API
POST /api/webhooks/payment/orange     public — re-verifies via Orange API
```

> **Deploy note:** set all `PAYMENT_*` env vars per environment (sandbox vs production
> base URLs + credentials). MTN sandbox only accepts EUR; production uses the local
> currency. Orange base URLs/payloads differ by operator/country — confirm against your
> Orange onboarding pack. Webhook URLs must be publicly reachable (configure
> `PAYMENT_MOMO_CALLBACK_URL` / `PAYMENT_ORANGE_NOTIF_URL`).
