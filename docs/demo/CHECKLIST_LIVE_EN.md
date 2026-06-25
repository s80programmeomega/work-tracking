# Verification Checklist — Live Data Entry Demo
**Script:** `DEMO_SCRIPT_LIVE_FR.md`
**Run after:** `php artisan migrate:fresh --seed --class=RolePermissionSeeder && php artisan db:seed --class=PlanSeeder`

---

## Pre-Demo Setup

```bash
# Minimal seed — roles, permissions and plans only (no business data)
php artisan migrate:fresh --seed --class=RolePermissionSeeder
php artisan db:seed --class=PlanSeeder

# Start all servers
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
```

---

## ✅ Confirmed Working — All Creation Forms

| Feature | File | Status |
|---------|------|--------|
| Sign-up form (5 fields, no email verification required) | `Signup.vue` | ✅ |
| Workspace creation form (name, description, visibility, permissions) | `workspaces/Create.vue` | ✅ |
| Invite member modal (email, role, message, multi-email support) | `InviteMemberModal.vue` | ✅ |
| Pending invitation auto-detected at sign-up | `Signup.vue` + backend | ✅ |
| Project creation form (4 sections) | `ProjetForm.vue` | ✅ |
| Activity creation modal (3 sections) | `ActivityForm.vue` | ✅ |
| 4-step task creation wizard | `TacheCreateWizard.vue` | ✅ |
| Real-time notification fires on task assignment | `useLiveNotifications.js` | ✅ |
| Subtask creation + automatic weighted progress recalculation | `SousTacheList.vue` | ✅ |
| Result submission form (6 fields incl. completion slider) | `SubmitResultModal.vue` | ✅ |
| Workspace member role change | `WorkspaceUsers.vue` | ✅ |
| Server-side block on restricted URL → 403 page | `router/index.ts` + backend | ✅ |
| Team creation form (name, description, responsible) | `TeamModal.vue` | ✅ |
| Real-time team chat between two browser tabs | `Teams/Show.vue` | ✅ |
| Support ticket form (category, subject, message, attachments) | `Support.vue` | ✅ |
| Help article creation with rich editor + draft auto-save | `HelpArticleForm.vue` | ✅ |
| MFA / TOTP setup (QR code + recovery codes) | `TwoFactorSettings.vue` | ✅ |
| Subscription plan cards with dynamic limits | `Plans.vue` | ✅ |
| Payment modal: MTN / Orange Money provider selection | `Plans.vue` | ✅ |
| Subscription limit enforcement (invite beyond plan max) | `CheckSubscriptionLimits.php` | ✅ |
| Admin Dashboard reflects newly created data in real time | `AdminDashboard.vue` | ✅ |
| Notification bell badge fires on task assign / result submit | `NotificationMenu.vue` | ✅ |
| Cmd+K search indexes newly created data immediately | `SearchBar.vue` | ✅ |

---

## ⚠️ Things to Watch During a Live Entry Demo

### @mention autocomplete does not exist
Type `@Éric Kouassi` as plain text in the chat input. The backend will still fire `ChatMentionNotification`. Do not claim there is a dropdown — say:
> "Team members mention colleagues by name. The system notifies them instantly."

### Invitation email may be slow
If the queue worker is running, the invite email will arrive within seconds. If not, the invited user can still register with the same email and the system will detect the pending invitation automatically — no email needed to proceed in the demo.

### Private browsing tabs for second-user flows
Use **Ctrl+Shift+N** (Chrome/Firefox) to open a private tab. This allows you to be logged in as two different users simultaneously without conflict.

### Draft auto-save needs a 6-second wait
In Act 17 (help article), after typing content in the editor, wait 6 seconds without clicking. The "Draft saved at HH:MM" message will appear automatically. Do not click Save — let it auto-save.

### Subscription limit requires the Free plan to be assigned
After the minimal seed, the workspace created during the demo has no plan assigned yet. The limit enforcement demo (Act 15) only works once there are 5+ members in the workspace AND the Free plan is assigned.

**Verify mid-demo via tinker if needed:**
```php
$ws = Workspace::first();
$ws->plan_id = Plan::where('slug', 'free')->value('id');
$ws->save();
```

---

## Quick Database Verify (minimal seed)

Run in `php artisan tinker` after the minimal seed:

```php
// Only roles and plans should exist — no users, workspaces or projects
User::count();          // expect 2 (superadmin + directeur from RolePermissionSeeder)
Workspace::count();     // expect 0
Projet::count();        // expect 0
Plan::count();          // expect 3 (free / starter / pro)
```

---

## Browser Tab Setup

| Tab | Account | Password | Role |
|-----|---------|----------|------|
| Tab 1 | *(created live during demo)* | — | Director — created in front of audience |
| Tab 2 | `superadmin@worktracking.com` | `password` | Platform admin |

All other accounts (Kofi, etc.) are created live using **private browsing tabs**.

---

## Items to Prepare Before Presenting

| Item | Used in |
|------|---------|
| A test PDF file on your desktop | Act 10 (result submission) + Act 18 (support ticket) |
| Your phone with Google Authenticator | Act 13 (MFA) — optional but more impressive |
| This script open on a second screen or printed | Reference for values to type |
| Any URL copied to clipboard | Act 7 Step 3 (external link on task) |

---

## Summary

| Priority | Item | Action |
|----------|------|--------|
| ✅ Ready | All creation forms | Verified working |
| ✅ Ready | Real-time notifications | Verified — Reverb must be running |
| ✅ Ready | Real-time chat | Verified — Reverb must be running |
| ✅ Ready | Draft auto-save | Wait 6 sec after typing — fires automatically |
| ⚠️ Mid-demo | Subscription limit (Act 15) | Assign Free plan via tinker if limit doesn't trigger |
| ⚠️ Note | @mention autocomplete | Does not exist — type @name as plain text |
| ⚠️ Note | Invite email timing | Queue worker must be running for fast delivery |
