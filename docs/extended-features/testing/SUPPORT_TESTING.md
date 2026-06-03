# Phase 3 — Help & Support Testing Guide

> Manual test guide for Phase 3: support ticket system (user contact form, admin management, SLA, replies, attachments).
> Run after `php artisan serve` + `npm run dev` + `php artisan queue:work`.

---

## Prerequisites

- At least one regular user and one super-admin account exist.
- Queue worker running: `php artisan queue:work` (notifications are queued).
- Mail configured (or `MAIL_MAILER=log` — check `storage/logs/laravel-YYYY-MM-DD.log`).

---

## 1 — User contact form

| # | Action | Expected |
|---|---|---|
| 1.1 | Sign in as a regular user, open sidebar → Help & Support | Contact form + "My Tickets" panel visible |
| 1.2 | Submit form without filling required fields | Validation errors on Category, Subject, Message |
| 1.3 | Fill all fields + optional reproducibility + steps + attach 1 file, submit | Success message; ticket appears in "My Tickets" with status **Open** and 6-digit number (#000001) |
| 1.4 | Check the SLA deadline shown on the ticket | Bug=4h, Feature=48h, Billing/Account=8h, Other=24h from submission time |
| 1.5 | Try to attach 6 files | Validation error: max 5 attachments |

---

## 2 — Super-admin receives notification

| # | Action | Expected |
|---|---|---|
| 2.1 | Open notification bell as super-admin after user submits ticket | New notification titled `#000001 — [subject]` with badge |
| 2.2 | Click the notification | Modal opens with ticket details; action button "Gérer les tickets" navigates to /support (admin view) |
| 2.3 | Check email (or log) | Email with subject `[Support #000001] [subject]`, link → `/support` |

---

## 3 — Admin view + mandatory reply

| # | Action | Expected |
|---|---|---|
| 3.1 | Sign in as super-admin, open /support | Admin management view (ticket list, filters, reply panel) |
| 3.2 | Click a ticket in the list | Right panel shows ticket details, reply content + attachments, and the reply form |
| 3.3 | Try to submit the reply form with empty body | Reply button remains disabled / validation error |
| 3.4 | Enter a reply + select status "In progress", submit | Reply appears in thread; ticket status updates; `first_responded_at` is now set |
| 3.5 | Submit another reply without changing status (keep "In progress") | Reply added; **user receives notification** even though status didn't change |
| 3.6 | Set status to "Resolved" | `resolved_at` is stamped; ticket shows resolved badge |

---

## 4 — User receives reply notification

| # | Action | Expected |
|---|---|---|
| 4.1 | As the original requester, open notification bell | Notification titled "Réponse sur votre ticket #000001 — [subject]" |
| 4.2 | Click the notification | Modal opens with reply preview + action button "Voir mon ticket" → `/support?ticket=1` |
| 4.3 | Check email | Email with reply body visible, link → `http://APP_FRONTEND_URL/support?ticket=1` |
| 4.4 | Click email link in a browser where you're logged in as the requester | Opens /support showing the ticket (no login redirect) |
| 4.5 | Click email link in a browser where you're NOT logged in | Redirected to /signin with redirect param |

---

## 5 — Attachments

| # | Action | Expected |
|---|---|---|
| 5.1 | Upload a screenshot on ticket creation | Attachment visible in admin panel and user ticket view |
| 5.2 | Admin uploads additional attachment via "Add attachments" | Appears in the ticket thread |
| 5.3 | Click attachment download link | File downloads with original filename |
| 5.4 | Try to download another user's attachment without auth | 403 |

---

## 6 — SLA breached filter

| # | Action | Expected |
|---|---|---|
| 6.1 | Create a ticket with category "bug" (SLA = 4h), manually set `sla_deadline` to the past via tinker | Ticket shows "SLA overdue" badge |
| 6.2 | In admin view, toggle "SLA overdue only" filter | Only overdue open tickets shown |

---

## 7 — Log Viewer access

| # | Action | Expected |
|---|---|---|
| 7.1 | As super-admin in local env, navigate to `http://localhost:8000/log-viewer` | Log viewer opens without 403 |
| 7.2 | As super-admin, use the "Journal applicatif" sidebar link | Opens `/log-viewer?token=xxx` in new tab; log viewer accessible |
| 7.3 | As a regular user, navigate to `http://localhost:8000/log-viewer` | In local: accessible (no auth in dev). In production: 403 without token |

---

## Negative cases

| Scenario | Expected |
|---|---|
| Guest submits ticket | 401 |
| Regular user accesses `GET /api/admin/support` | 403 |
| Regular user posts reply to `POST /api/admin/support/{ticket}/reply` | 403 |
| Submit reply with empty body | 422 validation error |
| Invalid category (`"xyz"`) | 422 |
