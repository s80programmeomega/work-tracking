# Verification Checklist — Seeded Data Demo
**Script:** `DEMO_SCRIPT_EN.md`
**Run after:** `php artisan migrate:fresh --seed` and before presenting.

---

## ✅ Critical Issues — All Fixed

### Issue 1 — Subscription limit demo ✅ FIXED
**Was:** `WorkspaceSeeder` never assigned a `plan_id` — limits were never enforced.
**Fixed in:** `database/seeders/WorkspaceSeeder.php` — now looks up the Free plan and assigns it to all 3 workspaces immediately after creation. WS1 has 11 members seeded against a 5-member Free plan limit — the upgrade toast fires on the first invite attempt.

---

## ⚠️ Minor Issues (wording adjustments only — no code fix needed)

### Issue 2 — "Digest frequency" toggle doesn't exist in the Notification Preferences UI
**What the script says:** *"Low-priority updates can be batched into a daily or weekly digest email."*
**What actually exists:** Per-event toggles (in-app, email, push) and a **Quiet Hours** time-range — no digest frequency dropdown.

**In the demo, say instead:**
> "Users can set quiet hours — no notifications between 10 PM and 7 AM, for example. Outside those hours, everything arrives in real time."

The daily digest exists in the backend (`notifications:send-digest` command) but is not exposed in the UI — do not mention it.

---

### Issue 3 — @mention autocomplete in team chat is not visible
**What the script says:** *"I used @mention to notify Éric directly."*
**What actually exists:** The backend fires `ChatMentionNotification` when a message contains `@Name` — but there is no autocomplete dropdown UI. Type the mention as plain text.

**In the demo, say instead:**
> "Team members can mention colleagues by name. Éric will receive a notification instantly."

---

### Issue 4 — "Share by Email" lives inside a sub-component
**What the script says:** Navigate to Workspace Documents → click Share by Email.
**What actually exists:** The button is inside the `DocumentManager` sub-component — one level deeper. Click into a document first, then find Share in the action menu.

---

### Issue 5 — Kanban drag-and-drop has a TODO stub
**What the script says:** Drag a project card from Active → Completed.
**What actually exists:** The `@task-drop` binding exists in `Dashboard.vue` (line 171) but the handler has a TODO comment — the state change may not persist to the backend.

**Safer approach:** Skip the drag. Say instead:
> "The Kanban board gives me a visual overview of all my projects at a glance."

Test it before the demo — keep it if it persists, skip it if it doesn't.

---

## ✅ Confirmed Working — No Action Needed

| Feature | File | Status |
|---------|------|--------|
| Dashboard stat cards with trend arrows | `Dashboard.vue` | ✅ |
| Monthly progress area chart (ApexCharts) | `Dashboard.vue` | ✅ |
| My Tasks + team members sidebar | `Dashboard.vue` | ✅ |
| Task table inline edit (status/priority/date) via PATCH | `TacheTable.vue` + `routes/api.php` | ✅ |
| Task detail tabs (Info, Subtasks, Comments, Documents, Audit) | `TacheDetail.vue` | ✅ |
| Weighted subtask progress bar | `SousTacheList.vue` | ✅ |
| Task comments seeded (Éric → Kofi → Aïcha thread) | `WorkspaceSeeder.php` | ✅ |
| TacheResultat scenarios seeded (N0 queue with pending results) | `WorkspaceSeeder.php` | ✅ |
| Router guard blocks `/admin/*` for non-super-admins | `router/index.ts` line 909 | ✅ |
| 403 page on unauthorized access | `FourZeroFour.vue` | ✅ |
| Workspace Tasks page with Excel export | `WorkspaceTaches.vue` | ✅ |
| Real-time notification via Echo (`App.Models.User.{id}`) | `useLiveNotifications.js` | ✅ |
| Notification bell badge + dropdown | `NotificationMenu.vue` | ✅ |
| Result submission form (Submit Result button) | `TacheResultsTab.vue` | ✅ |
| Approve / Send Back validation buttons | `TacheResultsTab.vue` | ✅ |
| Team chat with replies and typing indicator | `Teams/Show.vue` | ✅ |
| Real-time Echo subscription in team chat | `Teams/Show.vue` line 1891 | ✅ |
| Search page with type tabs + Export button | `Search.vue` | ✅ |
| Cmd+K / Ctrl+K search palette | `SearchBar.vue` line 112 | ✅ |
| Evaluation Dashboard (Top Performers + Alerts + date filter) | `EvaluationDashboard.vue` | ✅ |
| Agent Sheet (8 criteria bars) | `AgentSheet.vue` | ✅ |
| Performance Équipe (inline progress bars) | `PerformanceEquipe.vue` | ✅ |
| MFA / TOTP: QR code + recovery codes with stagger animation | `TwoFactorSettings.vue` | ✅ |
| Security tab in UserProfile | `UserProfile.vue` | ✅ |
| Notification preferences per-event toggles (3 channels) | `NotificationPreferences.vue` | ✅ |
| Subscription plan cards with dynamic limits | `Plans.vue` | ✅ |
| Payment modal: MTN / Orange Money provider selection | `Plans.vue` | ✅ |
| CheckSubscriptionLimits middleware blocks add_member | `CheckSubscriptionLimits.php` | ✅ |
| Admin Dashboard: 6 workspace + 4 user stat cards | `AdminDashboard.vue` | ✅ |
| Admin Workspaces: Extend Trial button wired | `AdminWorkspaces.vue` | ✅ |
| Admin Logs: Validation Audit Log tab | `AdminLogs.vue` | ✅ |
| Help Center: search bar + category grid | `HelpIndex.vue` | ✅ |
| Help Article Form: draft auto-save + restore banner | `HelpArticleForm.vue` | ✅ |
| Subtasks seeded on ERP tasks | `WorkspaceSeeder.php` inline | ✅ |
| 3 workspaces, 16 users after seeding | `WorkspaceSeeder.php` | ✅ |
| Notification preferences seeded per demo user | `NotificationDemoSeeder.php` | ✅ |
| Seeder execution order correct | `DatabaseSeeder.php` | ✅ |

---

## Seed Data Quick-Verify

After seeding, run in `php artisan tinker`:

```php
User::count();                                              // expect 16
Workspace::count();                                         // expect 3
Plan::pluck('name', 'slug');                                // expect free / starter / pro
Projet::count();                                            // expect 12
Tache::count();                                             // expect 50+
TacheResultat::where('statut', 'en_verification_n0')->count(); // expect 3
SousTache::count();                                         // expect 10+
Workspace::whereNull('plan_id')->count();                   // expect 0
```

---

## Pre-Demo Server Checklist

```bash
php artisan migrate:fresh --seed   # reset with realistic data
php artisan serve                  # backend API
npm run dev                        # Vite frontend
php artisan queue:work             # async jobs (exports, emails)
php artisan reverb:start           # WebSocket (real-time notifications + chat)
```

---

## Browser Tab Setup

| Tab | Account | Password | Role |
|-----|---------|----------|------|
| Tab 1 | `directeur@worktracking.com` | `password` | Main — Director |
| Tab 2 | `collaborateur@worktracking.com` | `password` | Kofi — submits results |
| Tab 3 | `superadmin@worktracking.com` | `password` | Platform admin |

Log all three in **before** the audience arrives.

---

## Summary

| Priority | Issue | Action |
|----------|-------|--------|
| ✅ Fixed | Subscription limits not enforcing (plan_id NULL) | Fixed in WorkspaceSeeder |
| ✅ Fixed | Script mentioned digest frequency UI | Say "Quiet Hours" instead |
| ✅ Fixed | Script claimed @mention autocomplete | Type @name as plain text |
| ⚠️ Test first | Kanban drag-and-drop may not persist | Test before demo; skip if broken |
| ✅ Ready | All other features | No action needed |
