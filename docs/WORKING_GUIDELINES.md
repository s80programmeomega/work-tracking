# Working Guidelines — Work Tracking v2.0

> Read this before every session. These are the conventions agreed between Jonas and the AI agent.

---

## Guide 1 — Branch Naming

Each task maps to one branch:

```
feature/v2-task-N-<short-name>
```

Examples: `feature/v2-task-2-subtask-model`, `feature/v2-task-5-validation-n0`

Always branch from `jonas`, PR back into `jonas`. Never push to `main`.
**Never push directly to `jonas`** — it only receives changes via PR merges.

---

## Guide 0 — Commit Messages

- Use `fix:`, `feat:`, `chore:`, `refactor:`, `test:`, `docs:` prefixes.
- Messages must be short and descriptive — describe the change, not the author.
- **Never include AI authorship references** (e.g., no `Co-Authored-By: Claude`, no "Generated with AI") in any commit message.

---

## Guide 2 — Read Before Write

Before implementing any task, explicitly state which existing files were read (Service + Controller + Composable + Page) and which pattern is being followed. Never invent new patterns that conflict with existing code.

---

## Guide 3 — Migration Naming

All v2 migrations are prefixed `2026_05_` and use descriptive names:

- `create_sous_taches_table`
- `add_bypass_columns_to_tache_resultats`
- `create_validation_audit_logs_table`

Never modify existing migrations — always additive.

---

## Guide 4 — Permission Checklist

Every task touching permissions follows this exact order:

1. Add method to `app/Services/PermissionService.php`
2. Add permission string to `database/seeders/RolePermissionSeeder.php`
3. Add to `Role::permissions()` in `app/Enums/Role.php` for relevant roles
4. Add to the relevant frontend composable (`useWorkspacePermissions.js`, `useProjetPermissions.js`, `useActivitePermissions.js`, or `useTachePermissions.js`)
5. **Update `docs/PERMISSIONS_MATRIX.md`** with the new permission row and which roles get it

All 5 steps must be done before marking a task complete.

---

## Guide 5 — Translation Key Format

All new keys follow `file.section.key`:

```
sous_taches.status.en_retard
validation.errors.comment_too_short
evaluation.criteria.respect_delais
```

This prevents key collisions and keeps files scannable.

---

## Guide 6 — Test-First for Business Rules

For CDC rules R1–R8, write the unit test first, confirm it fails, then implement. This ensures the rule is actually enforced.

---

## Guide 7 — End-of-Task Summary

At the end of each task, produce a short list:

- Files created
- Files modified
- Migrations added
- Permissions added (backend + frontend)
- Notifications added (class name, recipient, channel)
- Emails added (class name, template type: inline vs Blade, template path if Blade)
- Translation keys added
- Commits made

Then **tick off every completed item in the `docs/PROGRESSION.md` deliverables checklist** for that task. Leave unchecked any item that was intentionally deferred to a later task.

This doubles as the `ONBOARDING.md` update content.

---

## Guide 8 — No Silent Assumptions

If the CDC is ambiguous on a point, flag it as a question before implementing. Never guess and build the wrong thing.

---

## Guide 9 — Bug Protocol

- **Blocking bug** (feature cannot work without fixing it) → fix in the same branch, separate `fix:` commit, note in end-of-task summary
- **Non-blocking bug** (something nearby is broken) → flag to Jonas with description and suggested fix, do not touch it; decide together
- **Ambiguous** (not sure if bug or intentional) → ask before doing anything

Never silently fix something out of scope.

---

## Guide 10 — Logging Convention

| Situation | Tool |
|---|---|
| Model create/update/delete | `LogsActivity` trait (spatie/laravel-activitylog) |
| Circuit events (N0, bypass, N1, N2 decisions) | Immutable row in `validation_audit_logs` |
| Automatic state transitions | `Log::info()` with structured context |
| Rejected/blocked actions | `Log::warning()` with structured context |
| Errors and unexpected states | `Log::error()` with structured context |

Structured context always includes: `user_id`, the relevant entity id, `action`, `reason`.

Never log sensitive data (passwords, tokens, personal content).

---

## Guide 11 — Laravel Boost ToolsLaravel Boost is installed. Use its MCP tools during implementation:

- **`search-docs`** — run this before writing any code involving a Laravel feature or package. Pass specific package names to filter results.
- **`tinker`** — execute PHP to verify Eloquent queries and debug before assuming behavior
- **`database-query`** — read from the database directly to inspect data
- **`browser-logs`** — diagnose frontend errors before asking Jonas to check the console
- **`list-artisan-commands`** — check available options before any `artisan make:*` command

### Laravel 10 constraints
- Middleware registration: `app/Http/Kernel.php`
- Model casts: `protected $casts = []` array syntax only (no `casts()` method)
- Rate limits: `RouteServiceProvider` or `Kernel.php`

### Coding rules enforced by Boost
- Validation always in Form Request classes — never inline in controllers
- Prefer `Model::query()` over `DB::` raw queries
- Eager load to prevent N+1 problems
- `env()` only inside config files — use `config('key')` everywhere else
- Named routes and `route()` for URL generation
- Every new model gets a factory and a seeder
- API responses use Eloquent API Resources (follow existing convention)

### Testing rules
- PHPUnit only (not Pest)
- Run minimal filter after each change: `php artisan test --compact --filter=testName`
- Tests must cover happy path, failure path, and edge cases
- Never delete test files without approval

---

## Guide 12 — Email Handling

All notifications implement `ShouldQueue` and use `['mail', 'database']` channels. Follow this rule to decide how to build the email content:

**Simple notifications** (member added, task assigned, result submitted, status changed) → use Laravel's inline `MailMessage` fluent builder directly in the notification class. No template file needed.

```php
public function toMail($notifiable): MailMessage
{
    return (new MailMessage)
        ->subject(__('notifications.result_submitted.subject'))
        ->greeting("Bonjour {$notifiable->nom},")
        ->line(...)
        ->action(...);
}
```

**Complex notifications** (bypass context with history, evaluation sheet summary, trial expiry, workspace suspended) → use a Blade template via `->view()`.

```php
public function toMail($notifiable): MailMessage
{
    $locale = $notifiable->preferred_locale ?? app()->getLocale();
    return (new MailMessage)
        ->subject(__('notifications.bypass_activated.subject'))
        ->view("emails.bypass-activated.{$locale}", ['resultat' => $this->resultat]);
}
```

Template files live in `resources/views/emails/<notification-name>/<locale>.blade.php`:
```
resources/views/emails/
  bypass-activated/
    fr.blade.php
    en.blade.php
  evaluation-sheet-ready/
    fr.blade.php
    en.blade.php
```

**Channel preferences** — never hardcode `['mail', 'database']` in new v2 notifications. Instead call the `NotificationService` (built in Task 8) to resolve channels based on user preferences:

```php
public function via($notifiable): array
{
    return app(NotificationService::class)->channelsFor($notifiable, 'result_submitted');
}
```

**Never** send email synchronously. All notification classes must implement `ShouldQueue`.

---

## Guide 13 — Doc Updates and User Testing Guide After Each Task

At the end of every task, after ticking the PROGRESSION.md checklist (Guide 7), do the following:

**1. Update related docs**
- `docs/PROGRESSION.md` — tick completed checklist items
- `docs/SESSION_STATE.md` — update Current Task, Last Completed Task, Open PRs, Session Log
- `docs/PERMISSIONS_MATRIX.md` — add any new permissions introduced in the task
- `docs/IMPLEMENTATION_PLAN.md` — update the Current Task section at the bottom
- `docs/ONBOARDING.md` — only if architecture or conventions changed

**2. Create a user testing guide**
Create `docs/testing/TASK_N_TESTING.md` for every completed task. This file is for Jonas to manually verify the feature works end-to-end in the browser before the PR is merged.

Structure of every testing guide:
- **Prerequisites** — what must be set up before testing (seeded data, env vars, running services)
- **Test cases** — numbered, each with: action to perform, expected result, how to verify
- **Negative cases** — what should be blocked (wrong role, missing data, etc.)
- **Cleanup** — anything to reset after testing (optional)
