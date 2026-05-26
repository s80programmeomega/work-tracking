# Task 13 — Manual Testing Guide

Subscription modes + trial duration: SubscriptionService, middleware limits, trial banner, admin patch endpoint.

---

## Prerequisites

- A workspace with `subscription_mode = 'trial'` and `trial_started_at` set
- At least one workspace member (non-owner)
- Super admin account available

---

## TC-1 — Trial banner shown when trial is expiring soon

**Steps:**
1. Set a workspace's `trial_started_at` to `now()->subDays(28)` and `trial_duration_days` to 30 (2 days left)
2. Log in as workspace owner
3. Navigate to any workspace page

**Expected:**
- Amber banner appears at the top of the content area: "Votre essai expire dans 2 jours"
- Banner is dismissible (X closes it)

---

## TC-2 — Trial banner shows red when trial is expired

**Steps:**
1. Set a workspace's `trial_started_at` to `now()->subDays(35)` and `trial_duration_days` to 30 (expired 5 days ago)
2. Log in as workspace owner
3. Navigate to any workspace page

**Expected:**
- Red banner appears: "Votre période d'essai a expiré"
- CTA visible to contact or upgrade

---

## TC-3 — Banner resets when switching workspace

**Steps:**
1. Dismiss the trial banner on workspace A (trial expiring)
2. Switch to workspace B (paid mode)

**Expected:**
- Banner disappears on workspace B (paid — no banner)
- If switching back to workspace A, banner reappears (dismissed state is per-session)

---

## TC-4 — Invite blocked when trial expired (middleware)

**Steps:**
1. Use an expired trial workspace
2. As owner, attempt `POST /api/workspaces/{id}/members/invite` with a valid email

**Expected:**
- Response `403` with error key `subscription.errors.trial_expired`

---

## TC-5 — Document upload blocked when trial expired

**Steps:**
1. Use an expired trial workspace
2. Attempt `POST /api/documents` with a file upload

**Expected:**
- Response `403` with error key `subscription.errors.trial_expired`

---

## TC-6 — Super admin bypasses subscription limits

**Steps:**
1. Use an expired trial workspace
2. Log in as super admin
3. Attempt the invite endpoint

**Expected:**
- Request passes the middleware (no 403 from subscription layer)
- May still fail for other validation reasons (422) but NOT 403 from subscription

---

## TC-7 — SubscriptionService summary via API

**Steps:**
1. Call `GET /api/workspaces/{id}/subscription` as workspace owner

**Expected:**
- Response contains:
  - `subscription_mode`
  - `trial_expired: true/false`
  - `expiring_soon: true/false`
  - `remaining_days: <integer or null>`
  - `trial_started_at`
  - `trial_duration_days`

---

## TC-8 — Super admin can patch subscription mode

**Steps:**
1. Log in as super admin
2. Call `PATCH /api/workspaces/{id}/subscription` with `{ "subscription_mode": "paid" }`

**Expected:**
- Response 200
- Workspace `subscription_mode` updated to `paid`
- Trial banner no longer shown

---

## TC-9 — Trial expiring notification sent

**Steps:**
1. Manually dispatch `TrialExpiringNotification` for a workspace owner via Tinker
2. Check the owner's notifications

**Expected:**
- In-app notification of type `trial_expiring` appears
- Email sent (if mail configured): subject matches `subscription.notifications.trial_expiring.subject`

---

## Automated Tests

| Suite | File | Count |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Subscription/SubscriptionServiceTest.php` | 13 |
| PHPUnit Feature | `tests/Feature/Subscription/SubscriptionMiddlewareTest.php` | 8 |

Run with:
```bash
php artisan test --compact tests/Feature/Subscription/
```
