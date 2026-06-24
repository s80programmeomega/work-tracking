# Live Demo Script — Work Tracking App
**Audience:** M. Kemtio's team  
**Duration:** 60–75 min | **You drive, they watch**  
**Story:** You are the **Director of CERD Africa**. This is your working day.

---

## Pre-Demo Setup (do before anyone arrives)

```bash
# Reset to clean realistic data
php artisan migrate:fresh --seed

# Start all 4 servers (4 terminals)
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
```

**Pre-open 3 browser tabs and log in before the audience arrives:**

| Tab | Email | Password | Who they are |
|-----|-------|----------|--------------|
| Tab 1 | `directeur@worktracking.com` | `password` | You — the Director |
| Tab 2 | `collaborateur@worktracking.com` | `password` | Kofi Mensah — a team member |
| Tab 3 | `superadmin@worktracking.com` | `password` | The platform administrator |

---

## The Story

> *"CERD Africa is a growing organisation with three departments:*
> - *The **General Direction** (HR & Strategy) — 5 active projects*
> - *The **Technical Department** (IT & Infrastructure) — 4 projects including a critical cloud migration*
> - *The **Innovation Unit** — 3 experimental projects (chatbot, BI, RPA)*
>
> *You are the Director. Let's walk through your day."*

---

## Scene 1 — Your Morning Dashboard
**Tab:** 1 (directeur) | **Page:** `/` | **Time:** ~5 min

### What to say and exactly what to click:

**1. Land on the dashboard.**
> "First thing in the morning, I open my dashboard. Four indicators tell me the health of my workspace instantly."

Point at each stat card:
- **Projets actifs** → shows your 5 active projects, with a trend arrow vs last month
- **Mes tâches** → your personal assigned tasks count
- **Taux de complétion** → overall completion rate across all tasks (real, computed from DB)
- **Tâches en retard** → shown in red — these need immediate attention

> "These numbers are live — computed from real data every time the page loads. The arrows show whether things are improving or getting worse compared to last month."

**2. Kanban board.**
> "Below the stats, my projects are laid out as a board — Active on the left, Completed on the right."

You'll see cards for:
- Déploiement ERP RH & Paie
- Portail Client Self-Service
- Gouvernance Documentaire
- Stratégie Communication Interne

> "Let me drag the 'Gouvernance Documentaire' project to Completed to show you how this works."
Drag it across. It animates smoothly.
Drag it back.

**3. Monthly progress chart.**
> "This area chart shows three months of activity — total projects, tasks created, and tasks completed. I can immediately see if my team is keeping up with their commitments or falling behind."

**4. Right sidebar panels.**
> "On the right: my personal task list for today, and my team members — each showing how many tasks they're currently carrying."

Point at Éric Kouassi, Aïcha Traoré, Kofi Mensah — each with a task count badge.

---

## Scene 2 — The Project Hierarchy (Projet → Activité → Tâche)
**Tab:** 1 (directeur) | **Time:** ~10 min

### Step 1 — Open a project
Navigate to **Sidebar → Projects → My Projects**.

> "CERD Africa's General Direction manages 5 projects. Let me open our most critical one — the ERP deployment."

Click **"Déploiement ERP RH & Paie"**.

You see the project detail: description, dates (started 3 months ago, ends in 4 months), responsible person (Éric Kouassi), and the list of activities.

### Step 2 — Drill into an activity
> "A project is divided into Activities — phases of work. This ERP project has 3 phases."

You see:
- ✅ **Cadrage et analyse des besoins** — Completed
- 🔵 **Paramétrage et développement** — In progress
- ⬜ **Tests et mise en production** — Not started yet

Click **"Paramétrage et développement"**.

> "Let's look at the active phase."

### Step 3 — The task list (table view)
You land on the activity detail. The task list shows:

| Task | Progress | Status | Priority |
|------|----------|--------|----------|
| Configurer le module paie | 45% | EN_COURS | CRITIQUE |
| Développer le module de reporting RH | 30% | EN_COURS | Normal |
| Mettre en place la gestion des congés | 0% | À_FAIRE | Normal |
| Configurer l'interface de gestion des contrats | 0% | À_FAIRE | Normal |
| Développer le portail self-service employé | 20% | **EN_RETARD** | ÉLEVÉE |

> "Five tasks. Four are on track. But this last one — the self-service employee portal — is overdue. It was supposed to be further along by now."

### Step 4 — Inline edit (live, no modal)
> "I can fix the status directly in the table. Watch."

Click the **EN_RETARD** status cell on the self-service portal task.
→ A dropdown appears. Change it to `EN_COURS`.
→ It saves instantly, the cell updates.

Click the **priority** cell → change to `CRITIQUE`.
→ Saves instantly.

> "No form, no save button, no page reload. Direct editing in place."

### Step 5 — Open the full task detail
Click the task name **"Développer le portail self-service employé"**.

Walk through each tab:

**Info tab:**
> "The full task sheet. Progress is at 20% — the responsible person is Kofi Mensah. Due date was last week."
Point at: description, taux_réalisation bar, priority badge, due date (shown in red because overdue), assigned team members.

**Subtasks tab:**
> "This task is broken into 3 subtasks with weighted progress."

| Subtask | Weight | Progress | Status |
|---------|--------|----------|--------|
| Préparation et analyse | — | 40% | TERMINÉ |
| Exécution et développement | — | 60% | EN_COURS |
| Livraison et validation | — | 0% | À_FAIRE |

> "The parent task's progress bar is computed automatically from these subtasks. When all three are done, the task is automatically marked complete."

**Comments tab:**
> "There's an existing comment from Éric Kouassi explaining the delay."

You'll see a comment like:
> *"Le portail self-service est bloqué en attente du module d'authentification — prévu pour la semaine prochaine."*

> "The team communicates context directly on the task. Everything is traceable."

**Documents tab:**
> "Any file relevant to this task can be attached here."

Upload a test file (a PDF or Word doc from your desktop).
→ It appears in the list with uploader name, date, and size.

**Audit tab:**
> "Every change to this task is logged immutably — who changed what, and when."

You'll see the status change you just made (EN_RETARD → EN_COURS) with timestamp.

---

## Scene 3 — Roles & Permissions in Action
**Tabs:** 1 and 2 | **Time:** ~8 min

### Step 1 — Show the collaborator's restricted view
Switch to **Tab 2** (Kofi Mensah — collaborateur).

> "Let me show you what Kofi — a team collaborator — sees when he logs in."

Compare the sidebar with Tab 1 (directeur):
- **No** "All Projects" menu item (only sees his own assigned tasks)
- **No** "Workspace Tasks" (manager+ only)
- **No** "Evaluation Dashboard"
- **No** "Administration" section

> "The interface adapts completely to the role. A collaborator only sees what they're permitted to see. This isn't just visual — the backend enforces it too."

### Step 2 — Try to access a restricted URL directly
While on Tab 2 (collaborateur), type `/admin/dashboard` directly in the browser address bar. Press Enter.

→ Redirected to the **403 Unauthorized** page.

> "Even if Kofi knows the URL, he cannot access it. The server rejects the request — it's not just a hidden button."

### Step 3 — Workspace Tasks (director-only power view)
Switch back to **Tab 1** (directeur). Navigate to **Sidebar → Workspace Tasks** (`/workspace/taches`).

> "As Director, I have a power view — all tasks across all activities in my workspace. I can filter, sort, and search."

Filter by **Statut: EN_RETARD** → all overdue tasks across ALL projects appear in one list.

> "This is the control view. I don't have to click into each project to find problems."

Filter by **Project: Déploiement ERP RH & Paie** → narrows to just that project's tasks.

Click **Export Excel** → downloads a spreadsheet of the filtered task list.

> "One click — a full Excel export of whatever I'm looking at."

---

## Scene 4 — The Validation Circuit + Real-Time Notifications
**Tabs:** 1 and 2 | **Time:** ~10 min

> "CERD Africa uses a formal validation circuit. A collaborator submits their work results. Their N0 supervisor reviews and approves or sends it back. Then the N1 director confirms. Nothing is accepted without approval — and every action is timestamped."

### Step 1 — Kofi submits a result
Switch to **Tab 2** (Kofi Mensah — collaborateur).

Navigate to **My Tasks** → find **"Configurer le module paie"** (45%, EN_COURS).

Click **Submit Result**.

Fill in the form:
- **Result description:** `"Configuration du module paie effectuée à 45%. Les règles de calcul des cotisations sociales sont paramétrées. En attente de validation des règles fiscales."`
- **Completion:** drag slider to 45%
- Attach a document (e.g., a test PDF)

Click **Submit**.

> "Kofi has submitted his work for review. Watch what happens on the other tab."

### Step 2 — Real-time notification fires
Switch immediately to **Tab 1** (directeur).

> "Watch the bell icon in the top bar."

The bell shows a **red badge with "1"** (unread notification).

Click the bell → dropdown opens showing:
> *"Kofi Mensah a soumis un résultat pour : Configurer le module paie"*
With timestamp: just now.

> "Real-time. No page refresh. The notification arrived via WebSocket the moment Kofi clicked Submit."

Click the notification → jumps directly to the task result page.

### Step 3 — N0 reviews the submission
On the result page, the director sees:
- Kofi's description
- The attached document
- The 45% completion level
- Two action buttons: **Approuver** and **Renvoyer**

**Option A — Approve it:**
Click **Approuver**.
→ The result moves to the N1 validation queue.
> "Approved. The result is now in my N1 queue for final confirmation."

**Option B (more dramatic — do this instead):**
Click **Renvoyer**.
Enter a comment: `"La documentation des règles fiscales est manquante. Merci de joindre le fichier de paramétrage complet avant validation."`
Click Submit.

> "I sent it back with a specific comment. Kofi will be notified immediately."

Switch to **Tab 2** (Kofi) → the bell shows a new notification:
> *"Votre résultat a été renvoyé par Test Directeur — Configurer le module paie"*

> "Kofi knows exactly what to fix. The circuit continues until the work meets the standard."

### Step 4 — Show the audit trail
Back on **Tab 1**, open the task's **Audit Log**.

> "Every step of this circuit is logged permanently — submission time, rejection time, the comment, the actor. This is your compliance record."

---

## Scene 5 — Team Communication & Real-Time Chat
**Tab:** 1 (directeur) | **Time:** ~5 min

Navigate to **Sidebar → Teams**.

> "Beyond tasks, CERD Africa's teams communicate in real-time channels — like a built-in messaging system."

You'll see the team list (e.g., the General Direction team, the IT team).

Click into a team (e.g., **"Équipe Direction Générale"**).

> "This is a real-time team chat. Messages appear instantly for all members."

Type and send a message:
`"@Éric Kouassi — le module paie est en retard critique. Merci de prioriser cette semaine."`

> "I used @mention to notify Éric directly. He'll see a notification on his bell icon and receive a ping in the chat."

Show the chat history — previous messages visible with timestamps and user avatars.

> "If Reverb is running, open a second window as Éric and watch the message appear in real time — no refresh needed."

Scroll up in the chat history to show older messages.

> "The full conversation history is preserved. New members who join the team can scroll back and read the context."

---

## Scene 6 — Search & Full Traceability
**Tab:** 1 (directeur) | **Time:** ~5 min

### Global command palette (Cmd+K)
Press **Cmd+K** (or Ctrl+K on Windows) from anywhere.

> "From anywhere in the app — one keyboard shortcut opens the global search."

Type `ERP` → instant results appear:
- The project "Déploiement ERP RH & Paie"
- Tasks mentioning ERP
- Documents attached to ERP tasks

Press **Escape**.

### Full search page
Click the **search icon** in the top bar, or navigate to `/search`.

Type `sécurité` → results grouped by type:
- **Projects:** "Audit et Renforcement Sécurité SI" (from Workspace 2 — Technical Dept)
- **Tasks:** "Réaliser le pentest du SI" (55%, EN_COURS, CRITIQUE), "Mettre en place le MFA sur tous les accès" (0%, À_FAIRE)
- **Documents:** any security-related files

Click the **Tasks tab** → see only task results.
Click on **"Mettre en place le MFA sur tous les accès"** → a read-only preview modal opens showing the task detail.

> "I can preview any result without leaving the search page."

Close the modal.

Click **Export** → a dialogue asks for the export size → confirm → Excel file downloads with all results.

> "8 types of content are searchable: projects, activities, tasks, subtasks, documents, team messages, users, and notifications. Everything is indexed."

---

## Scene 7 — Performance & Evaluation
**Tab:** 1 (directeur) | **Time:** ~8 min

### Evaluation Dashboard
Navigate to **Sidebar → Evaluations → Evaluation Dashboard** (`/evaluations/tableau-de-bord`).

> "This is my team performance overview — automatically computed from the validation history. No manual data entry."

**Top Performers section:**
> "The top 5 performers are ranked with gold, silver, and bronze — scores computed from 8 criteria: completion rate, deadline respect, result quality, first-pass validation rate, and more."

Point at the score colours:
- Green (good score) → e.g., Éric Kouassi
- Yellow (average)
- Red (needs attention)

**Alerts section:**
> "Two types of automatic alerts:"
- **Abusive Escalations** (red flag) → a team member who repeatedly bypasses the normal validation circuit
- **High Inaction Rate** (orange) → a member with tasks assigned but no recent submissions

Change the **date range filter** to last quarter → scores update.
> "I can look at any period — weekly, monthly, quarterly."

### Individual Agent Sheet
Click on a team member's name (e.g., Kofi Mensah).

→ Opens `/evaluations/personnel/{id}/historique`

> "Every team member has an individual evaluation sheet."

Show:
- **Score global** at the top (e.g., 7.2/10) with a colour indicator
- **8 criteria bars** — each criterion shown as a horizontal bar with value
- **Validation history** — paginated list of every result Kofi ever submitted, with outcome (approved, rejected, sent back)

> "Management has an objective, documented view of every person's performance — based on actual work outcomes, not subjective impressions."

### Performance Équipe
Navigate to **Performance Équipe**.
Select the activity **"Paramétrage et développement"** from the dropdown.

> "Activity-level performance: how is each team member doing on this specific phase of work?"

You'll see a table:

| Member | Total Tasks | Completed | In Progress | Overdue | Rate |
|--------|------------|-----------|-------------|---------|------|
| Kofi Mensah | 3 | 0 | 2 | 1 | 33% |
| Éric Kouassi | 2 | 1 | 1 | 0 | 50% |

Each row has an **inline progress bar** (green/orange/red by percentage).

> "At a glance, I know who is delivering and who needs support."

---

## Scene 8 — Document Management
**Tab:** 1 (directeur) | **Time:** ~3 min

Navigate to **Sidebar → Documents → Workspace Documents**.

> "Every document uploaded to any task, activity, or project in this workspace is accessible here — a central document library."

Show the document list with columns: name, type, uploader, upload date, linked project/task.

Filter by **Project: Déploiement ERP RH & Paie** → narrows to ERP documents only.

Click a document → preview opens.

Click **Share by Email**:
- Enter an external email address (e.g., a client or auditor's email)
- Click Send

> "I can share a document with someone external — an auditor, a client, a partner — without giving them access to the app. They receive a link by email."

---

## Scene 9 — Security: Two-Factor Authentication (MFA)
**Tab:** 1 (directeur) | **Time:** ~4 min

Navigate to **Profile** (top-right avatar → Profile) → **Security tab**.

> "CERD Africa requires all users to protect their accounts with two-factor authentication."

**Enable TOTP:**
Click **"Activer TOTP"**.
→ A QR code appears with a manual setup key below it.

> "The user scans this QR code with Google Authenticator or Authy on their phone. From that moment, every login requires both the password AND a 6-digit code that changes every 30 seconds."

*(If you have your phone ready: scan and enter the code. If not: show the QR and continue.)*

Click Continue → enter a code → confirm.

→ **Recovery codes** appear with a stagger animation — 8 codes, each shown in a monospace block.

> "Recovery codes are one-time use — stored securely by the user in case they lose their phone. The system generates them at setup."

> "With MFA active: even if a password is compromised, the account is safe. The attacker would also need physical access to the user's phone."

---

## Scene 10 — Notifications & Email Preferences
**Tab:** 1 (directeur) | **Time:** ~3 min

Navigate to **Sidebar → Notification Preferences** (`/notification-preferences`).

> "Each user controls exactly which events notify them, and by which channel."

Show the toggles grouped by event type:
- Task assigned → in-app ✅, email ✅, push ✅
- Result submitted → in-app ✅, email ✅
- Result approved → in-app ✅
- Trial expiring → email ✅ (high-signal, always sent)

Show the **Digest frequency** setting:
> "Low-priority updates can be batched into a daily or weekly digest email instead of instant notifications. Critical events always arrive immediately."

Show the **Web Push** panel:
> "If the user has allowed browser push notifications, they'll receive alerts even when the app is closed — like a mobile app notification on desktop."

Navigate to **Sidebar → Notifications** (`/notifications`).

> "The notification centre shows everything — read and unread, with timestamps and direct links to the relevant task or event."

Click one notification → it marks as read and navigates to the resource.
Click **Mark all as read**.

---

## Scene 11 — Subscriptions, Billing & Resource Limits
**Tab:** 1 (directeur) | **Time:** ~5 min

Navigate to **Sidebar → Subscription** (`/workspaces/{id}/subscription`).

> "Each workspace runs on a subscription plan. Right now, the General Direction workspace is on a 30-day trial."

Show:
- Days remaining in trial (e.g., "12 jours restants")
- Members: 10/5 used *(if Free plan max is 5, this is already over — good to illustrate)*
- Storage used vs limit

Navigate to **Subscription → Plans** (`/subscription/plans`).

> "Three plans:"

Point at each card:
- **Free** → 0 XAF, 5 members max, 100 MB storage
- **Starter** → 15,000 XAF/month, 25 members, 5 GB storage
- **Pro** → 50,000 XAF/month, unlimited members, unlimited storage

> "Pricing is in XAF — the local currency. Plans are designed for African SMEs."

Click **"Choisir"** on the Pro plan → payment modal opens.

> "Payment goes through MTN Mobile Money or Orange Money — the two dominant mobile payment providers in the region."

Show the modal:
- Provider selection: MTN MoMo / Orange Money
- Phone number input field

> "The user enters their phone number. MTN sends a push payment request to their phone. The moment they confirm on their phone, our system receives a webhook from MTN, verifies it independently, and activates the plan. We never trust the browser alone — the activation is webhook-confirmed."

Close the modal.

**Show limit enforcement:**
> "Let me show what happens when a workspace exceeds its plan limits."

Navigate to **Workspace Settings → Members** → click **Invite Member** → enter an email.

→ If the workspace is on the Free plan and already has 5 members, a toast error fires:
> *"Limite atteinte — votre plan actuel autorise 5 membres maximum. Passez à un plan supérieur pour inviter davantage de collaborateurs."*

> "The backend enforces this on every request. The UI reflects it, but the server is the real gate."

---

## Scene 12 — Platform Administration
**Tab:** 3 (superadmin) | **Time:** ~5 min

Switch to **Tab 3** (`superadmin@worktracking.com`).

Navigate to **Admin → Platform Dashboard** (`/admin/dashboard`).

> "The super admin sees the entire platform — every workspace, every user, every subscription — in one dashboard."

Point at the stat cards:

**Workspace stats (6 cards):**
- Total workspaces: 3
- Active: 3
- On trial: 2
- Paid: 1
- Expiring soon: 1
- Expired: 0

**User stats (4 cards):**
- Total users: 16
- Active in last 30 days
- New this week
- Super admins: 1

**Recent Workspaces table:**
Shows "Direction Générale", "Département Technique", "Pôle Innovation" with subscription badges (Trial/Paid), member counts, and trial countdowns.

> "The admin sees which workspaces are about to expire — so they can reach out proactively."

Navigate to **Admin → Workspaces**.

> "I can manage any workspace from here."

Find "Direction Générale" → click **Extend Trial** → enter 15 days → confirm.
→ Toast: "Essai étendu de 15 jours. Le directeur a été notifié par email."

Navigate to **Admin → Plans**.

> "I manage the subscription plan catalogue — I can create new plans, adjust pricing and limits, or retire old ones."

Show the 3 plans (Free, Starter, Pro) in a table with edit buttons.

Navigate to **Admin → Users**.

> "Full user directory — all 16 users across all workspaces, searchable by name or email."

Navigate to **Admin → Logs** → click the **Validation Audit Log** tab.

> "Every N0, N1 validation action — approval or rejection — is logged here permanently. Timestamp, actor, target task, action taken. This is the compliance record — it cannot be edited or deleted."

Filter by date range → show filtered results.

---

## Scene 13 — Help Center
**Tab:** 1 (directeur) | **Time:** ~2 min

Navigate to **Sidebar → Help** (`/help`).

> "Users have a built-in help center — bilingual French and English, searchable."

Show the category grid (icons + titles + article counts). e.g.:
- Gestion de projets (5 articles)
- Tâches et validations (8 articles)
- Permissions et rôles (4 articles)

Click a category → articles list appears.
Click an article → rich text content with formatted sections.

Type a search query in the help search bar (e.g., `"validation"`) → relevant articles appear instantly.

> "Help content is also included in the global search — so users don't need to know where to look."

Switch to **Tab 3** (superadmin) → **Admin → Help Articles**.

> "Admins author help content with a rich text editor. Articles auto-save as drafts every 5 seconds. If the author leaves and comes back, a banner offers to restore the unsaved draft."

---

## Closing — What They Don't See (spoken only, no clicks)
**Time:** ~2 min

> "Behind everything you just saw:
>
> - **840+ automated tests** run on every code change — no feature ships without being tested
> - **Static analysis** catches type errors before they reach production
> - The entire app is **bilingual** — every label, notification, email, and error message exists in both French and English
> - All permission checks are enforced **on the server** — the UI is just a mirror, not the gate
> - Every page is **mobile-responsive** — the same app works on phone, tablet, and desktop
> - **Dark mode** is supported on every single page"

---

## Time Guide

| Scene | Feature | Min |
|-------|---------|-----|
| 1 | Dashboard — stats, Kanban, chart | 5 |
| 2 | Full hierarchy + inline edit + subtasks + audit | 10 |
| 3 | Roles & permissions — restricted view + backend enforcement | 8 |
| 4 | Validation circuit + real-time notification fires live | 10 |
| 5 | Team chat + @mentions | 5 |
| 6 | Global search (Cmd+K + page) + Excel export | 5 |
| 7 | Evaluation dashboard + agent sheet + team performance | 8 |
| 8 | Document management + share by email | 3 |
| 9 | MFA / Two-factor authentication | 4 |
| 10 | Notification preferences + email + web push | 3 |
| 11 | Subscription plans + MTN/Orange payment + limit enforcement | 5 |
| 12 | Platform admin — workspaces, users, audit logs | 5 |
| 13 | Help center | 2 |
| — | Closing | 2 |
| **Total** | | **~75 min** |

**To cut to 60 min:** skip Scenes 8, 10, and 13.

---

## Gotchas to Avoid

| Risk | Fix |
|------|-----|
| Bell doesn't update in real-time | `php artisan reverb:start` must be running |
| Excel export hangs | `php artisan queue:work` must be running |
| Search returns no results | Search for `"ERP"`, `"sécurité"`, or `"monitoring"` — these exist in seed data |
| Payment modal shows error | Expected in dev — just show the UI and explain the webhook flow verbally |
| Subscription limit demo doesn't trigger | Verify the workspace is on Free plan (max 5 members) post-seeding |
| Wrong tab | Keep them labelled: Tab 1 = Directeur, Tab 2 = Kofi, Tab 3 = Superadmin |
