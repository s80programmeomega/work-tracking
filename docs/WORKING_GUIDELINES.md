# Working Guidelines — Work Tracking v2.0

> Read this before every session. These are the conventions agreed between Jonas and the AI agent.

---

## Guide 0 — Consult Before Acting

Before executing any step of a task:

1. **Read the relevant guides in this document** — identify every guide that applies to what you are about to do (branching, migrations, permissions, translations, emails, logging, etc.) and follow them exactly.
2. **Before modifying anything that already exists** — search the full codebase for all usages of that code (PHP, Vue, JS, migrations, seeders, tests, translations) and analyse the impact before touching it.
3. **Never assume** a change is isolated. A method rename, a column addition, a role value change, or a pivot field removal can break things far from the edit site. Always verify scope first.

This guide takes precedence over everything else. Skipping it to save time has caused broken endpoints, wrong role values, and missing columns in this project.

---

## Guide 1 — Branch Naming

Each task maps to one branch:

```
feature/v2-task-N-<short-name>
```

Examples: `feature/v2-task-2-subtask-model`, `feature/v2-task-5-validation-n0`

Always branch from `jonas`. **Never push or merge into `main`** — this is the one hard, absolute boundary.

**Push boundaries (clarified 2026-06-01):**
- `main` — never push, never merge. No exceptions.
- `jonas` — **may** be pushed directly, but **only with explicit per-push approval from the user**. Ask on *every* push; there is no blanket pre-authorization. It is not a "never push" branch (the previous "only via PR merges" wording was too strict), but it is never pushed routinely or silently.
- Feature branches — push freely, to **both** `origin` and `client` (client has paid).

**Remote protocol:** always use **HTTPS, never SSH** for remote URLs and pushes.

---

## Guide 0 — Commit Messages

- Use `fix:`, `feat:`, `chore:`, `refactor:`, `test:`, `docs:` prefixes.
- Messages must be short and descriptive — describe the change, not the author.
- **Never include AI authorship references** (e.g., no `Co-Authored-By: Claude`, no "Generated with AI") in any commit message. This rule **overrides** the agent harness's default behaviour of appending a `Co-Authored-By: Claude` trailer — on this project that trailer is never added.

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
5. Expose in the relevant API Resource (`TacheResource`, `ActiviteResource`, etc.) if the frontend needs to read it from API responses
6. **Update `docs/PERMISSIONS_MATRIX.md`** — see Guide 15. This is a hard gate.

All 6 steps must be done before marking a task complete.

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

---

## Guide 14 — No Silent Deletions

Before removing any code (column reference, method, class, route, field, relation, etc.):

1. **Search the full codebase** for all usages of that code — PHP, JS/Vue, migrations, seeders, tests, translations.
2. **Report the impact** to Jonas: what uses it, what breaks, what is safe to remove.
3. **Wait for explicit approval** before deleting anything — even in auto-edit mode.

This applies to:
- Model `fillable`, `casts`, `withPivot`, relationships
- Controller methods and routes
- Service methods
- Vue components, composables, and store properties
- Translation keys
- Migration columns

**Why:** Removing code without a full impact check has caused broken endpoints and missing DB columns in this project. The cost of asking is always lower than the cost of reverting.

---

## Guide 16 — Laravel Dusk Browser Testing

Laravel Dusk (`laravel/dusk`) is installed for end-to-end browser testing. Browser tests live in `tests/Browser/`, organised by feature area.

### Running tests

```bash
# Terminal 1 — server must be running before dusk
php artisan serve

# Terminal 2
php artisan dusk                                         # all browser tests
php artisan dusk tests/Browser/Auth/AuthenticationTest.php  # specific file
php artisan dusk:fails                                   # only previously-failed tests
```

> The server uses `.env.dusk.local` automatically, which points to the `work-tracking-dusk` database. Never run Dusk against the dev database.

### Base class

All Dusk tests must extend `Tests\Browser\WorkTrackingTestCase`, not `DuskTestCase` directly. It provides:

| Helper | When to use |
|---|---|
| `$this->signInAs($browser, $user)` | Any test that is NOT testing authentication. Injects a Sanctum token into localStorage directly — fast, no UI. |
| `$this->signInViaUi($browser, $email, $password)` | Tests that verify the login form itself. |

### `dusk` attributes on Vue elements

Add a `dusk="element-name"` attribute to any Vue element a test needs to target. Target it in tests with `@element-name`.

```html
<!-- Vue component -->
<input dusk="email" v-model="form.email" />
<button dusk="login-button" type="submit">Se connecter</button>
```

```php
// Dusk test
$browser->type('@email', 'test@example.com')
        ->click('@login-button');
```

Never target elements by CSS class — classes change with UI updates. `dusk` attributes are stable test handles.

### Test organisation

```
tests/Browser/
├── WorkTrackingTestCase.php   ← base class, extend this
├── Auth/                      ← login, logout, redirect guards
├── Taches/                    ← task creation, result submission
├── Validation/                ← N0 approve/return, bypass, N1/N2
└── Workspace/                 ← workspace creation, settings
```

### Session state between tests

Dusk keeps the browser session alive across tests in the same file. If a test leaves the user authenticated and the next test expects a guest, clear localStorage explicitly:

```php
$browser->tap(fn ($b) => $b->script([
    "localStorage.removeItem('auth_token');",
    "localStorage.removeItem('user');",
]))->visit('/signin');
```

### Migration strategy

Dusk tests use `DatabaseMigrations` (not `RefreshDatabase` — transactions don't work across HTTP requests). All migration `down()` methods must be safe to run on a fresh database — use `Schema::hasColumn()` and existence checks before dropping columns, indexes, or foreign keys.

### What to test with Dusk vs PHPUnit

| PHPUnit (Feature tests) | Dusk (Browser tests) |
|---|---|
| API responses, status codes | Page renders after an action |
| Permission checks (403s) | Button shows/hides based on role |
| Business rule enforcement | Form validation messages visible |
| Job dispatching, notifications | Full user flow (submit → approve → badge updates) |

Write at least one Dusk test for every user-facing flow introduced by a task.

---

## Guide 15 — Permissions Matrix is a Hard Gate

`docs/PERMISSIONS_MATRIX.md` must be updated **in the same commit** as any permission change. A task is not complete if the matrix is out of date.

**Triggers — update the matrix whenever you:**
- Add a method to `PermissionService`
- Add a permission string to `RolePermissionSeeder`
- Add a permission to a frontend composable (`useWorkspacePermissions.js`, `useProjetPermissions.js`, `useActivitePermissions.js`, `useTachePermissions.js`)
- Expose a permission key in an API Resource (`TacheResource`, `ActiviteResource`, etc.)
- Register a new Policy in `AuthServiceProvider`

**For each new permission, add a row to the matrix with:**
- The permission name
- Which roles get it (✅ / ❌ / 🔑)
- Any pivot flag conditions (🔑)

**Also append a row to the Changelog table** at the bottom of the matrix with: date, task number, and what changed.

**Why:** The matrix was not updated for Tasks 2, 3, or 4 despite all permission steps being completed — the omission meant there was no single source of truth for role capabilities, making it impossible to audit what each role can do without reading multiple files.

---

## Guide 17 — Log Messages in French

All human-readable log messages passed to `Log::info`, `Log::warning`, `Log::error`, or any other logging facade **must be in French**. Structured context keys and values that are machine-readable identifiers stay in English.

**Examples — do this:**
```php
Log::info('Résultat soumis au N0', [
    'user_id' => $actor->id,
    'tache_resultat_id' => $resultat->id,
    'reason' => 'submission',           // English key + machine-readable value
]);

Log::warning('Drapeau escalades_abusives activé', [
    'user_id' => $auteur->id,
    'consecutive_invalid_bypasses' => 3,
]);
```

**Don't do this:**
```php
Log::warning('Score impact skipped — task has no responsable', [...]);   // ❌ English message
Log::info('Bypass activated by user', [...]);                            // ❌ English message
```

**Why:** The target user base (CERD Africa team) reads French; logs end up in incident reports, support tickets, and audit exports. Mixing English messages into a French-language operational log breaks the reading flow and can be misclassified by translation tools and grep filters built around French keywords.

**How to apply:**
- Existing French logs in `TacheResultatService` (`'Résultat soumis au N0'`, `'Bypass anti-sabotage activé'`, `'Drapeau escalades_abusives activé'`) are the reference style — short, in the past tense or descriptive present, with structured context as a separate array.
- Structured context keys stay in English (`user_id`, `tache_id`, `reason`, `action`) — they're identifiers, not narrative text. The same goes for enum-like values (`'comment_too_short'`, `'n0_inaction'`, `'subtasks_exist'`).
- Exception messages thrown with `__('...')` keys (e.g., `__('circuit_validation.errors.motif_too_short')`) handle localisation via the translation layer — don't double-translate inside the throw.
- When you touch an English log message in code you're editing for other reasons, translate it in the same commit. Don't make a separate "translate logs" PR — that's churn.

---

## Guide 18 — Dusk Test Is Mandatory Per Task

Every task that touches the UI in any way **must** ship with at least one Laravel Dusk browser test before it is considered complete. This is a hard gate, not a nice-to-have.

**Why it's enforced as a separate guide** (in addition to Guide 16 which describes Dusk usage):
- A task isn't shipped if its user-facing flow can't be exercised through the browser.
- Tasks 4, 5 (early in v2) **did** ship without Dusk coverage. This created accumulated debt that had to be paid back later. That regression is the trigger for this guide.

**Mandatory checks at task end (extend the Guide 7 end-of-task checklist):**
1. New `tests/Browser/<FeatureArea>/<FlowName>Test.php` file exists.
2. It uses `extends Tests\Browser\WorkTrackingTestCase` and `use DatabaseTruncation` (not `DatabaseMigrations`).
3. At least one method exercises the **happy path** of the new flow end-to-end (login → action → assertion on rendered UI).
4. UI elements the test targets have `dusk="…"` attributes added to the Vue component (this is the developer's responsibility, not the tester's).
5. The test runs green via `php artisan dusk tests/Browser/<FeatureArea>/<FlowName>Test.php` against the dedicated `work-tracking-dusk` database.

**What counts as a "UI-touching" task:**
- Any new Vue page or component.
- Any new sidebar entry, modal, or interactive element.
- Any API endpoint whose response is consumed by an existing UI component.
- New permissions that gate UI visibility.

**What does NOT need a Dusk test:**
- Pure backend infrastructure (queues, jobs, broadcasting plumbing — no UI surface).
- Migrations / seeders / factories that only affect data shape.
- Documentation-only changes.

**If a task genuinely has no user-facing flow** (e.g., a pure refactor): document that explicitly in the PR description and in `docs/PROGRESSION.md`'s row for that task. Do not silently skip.

**Why this is a stronger guarantee than "we usually write Dusk tests":** I (the AI agent) have a pattern of getting absorbed in the implementation and forgetting Dusk at task end. Guide 18 + the auto-memory entry mean the check happens automatically at every end-of-task wrap-up, regardless of how busy the work was.

---

## Guide 19 — Explanatory Code Comments in French

When code comments are written, they **must be in French** — same language policy as log messages (Guide 17). Applies to all produced code: PHP, JS/Vue, Blade templates, migrations, tests.

**What counts as a comment under this guide:**
- Inline `//` comments
- Block `/* … */` comments
- PHPDoc / JSDoc blocks (`/** … */`) — the human-readable description; type annotations stay English
- Single-line explanations above a non-obvious block

**Examples — do this:**
```php
// On mappe la décision N1 vers un couple (critere, valeur) via match().
// match() est le commutateur strict de PHP 8 qui retourne une valeur.
[$critere, $valeur] = match ($decision) {
    'validated_despite_return' => [self::CRITERE_VALIDATED_DESPITE_RETURN, self::PENALTY],
    'confirmed_return' => [self::CRITERE_CONFIRMED_RETURN, self::BONUS],
    default => throw new \InvalidArgumentException("Décision N1 inconnue: {$decision}"),
};
```

```php
/**
 * Service de scoring pour les décisions de validation N1 sur les TacheResultats.
 *
 * Pour chaque décision N1, ce service :
 *   1. Résout le « responsable de tâche » (utilisateur avec is_responsable = true
 *      sur le pivot tache_user) — c'est lui qui est scoré, pas l'assignee.
 *   2. Décide si la décision mérite une pénalité, un bonus ou pas d'impact.
 *
 * @param  string  $eventType  identifiant machine, reste en anglais
 */
```

**Don't do this:**
```php
// Map the decision to a (critere, valeur) pair via match().   ❌ anglais
// Get the workspace responsable                                ❌ anglais
```

**What stays in English (consistency with Guide 17):**
- Class names, method names, variable names, route names, permission strings, event types, audit log action names, constant identifiers.
- Type annotations inside PHPDoc (`@param string $x`, `@return User|null`).
- TODO/FIXME markers stay English so grep tooling and shared conventions still work: `// TODO: extraire ce calcul dans un helper`.

**Why:** the codebase ends up reviewed by French-speaking team members. Mixing English explanatory prose into otherwise French operational text breaks the reading flow and creates a translation tax on every review. By keeping the narrative in French and the machine-readable identifiers in English, we get the best of both: code that grep'able internationally + comments that read naturally to the team.

**Default still applies:** Guide 19 doesn't override CLAUDE.md's "default to writing no comments" rule. **Write comments only when the WHY is non-obvious.** When you do write one, write it in French.

**How to apply with existing English comments:**
- Touch them only when you're editing the surrounding code for other reasons (Guide 14 — no silent rewrites).
- A comment-only "translate all comments" PR is acceptable scope if explicitly requested, but never a side-effect of a feature PR.

---

## Guide 20 — Web Search Is Allowed

You can — and often should — use web search to confirm library behavior, look up Laravel/Vue/PHPUnit syntax, check Stack Overflow for a tricky error message, or read a package's official docs. Don't guess when a quick search would settle the question.

**When to search:**
- A framework method's exact signature or return type
- Known issues / breaking changes between minor versions
- Library APIs (Spatie permissions, Laravel Sanctum, PHPUnit assertions, Tailwind utilities)
- Migration patterns from other Laravel projects facing the same problem

**When NOT to search:**
- Internal project conventions — those live in `CLAUDE.md`, `WORKING_GUIDELINES.md`, and the existing code. Read the code.
- Anything Laravel Boost's `search-docs` MCP tool can answer faster (Guide 11). Try that first.
- User-specific data, credentials, or production state.

**Why:** the AI agent has a habit of inventing plausible-but-wrong method signatures when uncertain. A 5-second `WebFetch` or `WebSearch` is cheaper than the round-trip of "I tried X, X doesn't exist, let me try Y" cycles.

---

## Guide 22 — API Documentation (Scribe + Swagger UI)

`knuckleswtf/scribe` is installed as a dev dependency. It auto-generates API docs from routes, FormRequests, and annotations, and produces an OpenAPI 3.0.3 spec consumed by a Swagger UI view.

### Endpoints (all require `auth:sanctum`)

| URL | Content |
|---|---|
| `/api/docs` | Scribe Pastel UI (HTML) |
| `/api/docs/swagger` | Swagger UI (reads the OpenAPI JSON) |
| `/api/docs.openapi` | Raw OpenAPI YAML |
| `/api/docs.json` | OpenAPI spec as JSON |
| `/api/docs.postman` | Postman collection v2.1 |

Routes are registered in `app/Providers/ScribeServiceProvider.php` (not via `scribe.laravel.add_routes`).

### Regenerating docs

```bash
php artisan scribe:generate
```

Run this after any controller, FormRequest, or annotation change. Output lands in `storage/app/scribe/` — commit those generated files so the docs are always up to date.

### Annotating endpoints

Group endpoints with `@group` and describe them with `@description`. Parameters are auto-inferred from FormRequests; add `@bodyParam` or `@queryParam` only when auto-inference misses something.

```php
/**
 * @group Tâches
 *
 * @description Retourne la liste des tâches pour une activité.
 *
 * @queryParam statut string Filtre par statut. Example: en_cours
 */
public function index(Request $request, Activite $activite): JsonResponse
```

Mark public endpoints (login, register) explicitly:

```php
/**
 * @group Authentification
 * @unauthenticated
 */
public function login(LoginRequest $request): JsonResponse
```

### Excluding routes

Add patterns to `config/scribe.php` → `routes[0].exclude`:

```php
'exclude' => [
    'api/broadcasting/auth',
    '_laravel-brain/*',
    'api/internal/*',
],
```

### Config file

`config/scribe.php` — key settings:
- `auth.enabled = true` + `auth.default = true` — all endpoints shown as authenticated by default
- `openapi.version = '3.0.3'` — OpenAPI version
- `groups.order` — controls sidebar ordering in both Scribe and Swagger UIs
- `examples.faker_seed = 1234` — reproducible example values

### Swagger UI token injection

The Swagger view (`resources/views/scribe/swagger.blade.php`) reads `localStorage.getItem('auth_token')` automatically — the same key the Vue frontend stores the Sanctum token in. No manual authorisation needed after login.

---

## Guide 21 — Larastan Static Analysis (pre-commit hard gate)

**[Larastan](https://github.com/larastan/larastan)** (`larastan/larastan` v2.11) is installed and configured at **level 5**. It is **mandatory before every commit**, alongside Pint — both must pass with zero errors. Do not commit if Larastan reports errors; fix them first.

**Important:** the binary is `vendor/bin/phpstan` but this IS Larastan — `phpstan.neon` loads the `larastan/larastan` extension which adds full Laravel awareness (Eloquent models, query builders, relations, facades, magic methods). Raw PHPStan without this extension would miss most Laravel-specific type errors. Never run PHPStan without the project's `phpstan.neon`.

### Running Larastan

```bash
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
```

Use `--memory-limit=1500M` — the 512 MB default is not enough on this codebase.

### Pre-commit checklist order

1. `vendor/bin/pint --dirty --format agent` — must output `"result":"passed"` or `"result":"fixed"` with no remaining issues
2. `php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M` — must output `[OK] No errors`

Only then commit.

### Suppressing false positives

Add patterns to the `ignoreErrors` section of `phpstan.neon` (project root). Use `#` as the regex delimiter; escape literal `#` characters inside patterns as `\#`.

```neon
ignoreErrors:
    - '#Access to an undefined property App\\Models\\Foo::\$bar#'
    - message: '#Some pattern that may not always match#'
      reportUnmatched: false
```

Use `reportUnmatched: false` for patterns that only fire conditionally (e.g., dead-code warnings that PHPStan version-dependently emits). Without it, an unmatched pattern itself becomes an error.

### What to fix vs. suppress

| Situation | Action |
|---|---|
| Wrong field name / wrong method call | Fix the code |
| Missing `@property` on a model (real DB column) | Add `@property` PHPDoc to the model |
| Route model binding type in FormRequest (`$this->route('model')` returns `mixed`) | Add `/** @var ModelClass $var */` assertion before use |
| Dynamic SQL alias property (`selectRaw('count(*) as total')`) | Add `@property` PHPDoc to the model |
| Pivot property access (`$model->pivot->field`) | Suppress with `ignoreErrors` pattern |
| Dead code / always-true branch PHPStan detects | Suppress with `reportUnmatched: false` |
| Unused private/protected method that is intentionally kept | Suppress with `ignoreErrors` |

**Never suppress a real type error** — if PHPStan says a method doesn't exist, verify the field/method name before suppressing.

### PHPDoc conventions for models

```php
/**
 * @property int $id
 * @property string $nom
 * @property \Carbon\Carbon|null $created_at
 * @property float $score_total  SQL alias from aggregate queries
 */
class MyModel extends Model
```

### JsonResource conventions

Both `@property` and `@mixin` are required on resources for PHPStan to resolve proxy property accesses:

```php
/**
 * @property MyModel $resource
 * @mixin MyModel
 */
class MyModelResource extends JsonResource
```

### When to run

- Before every commit (alongside Pint).
- After any model, service, controller, or resource change.
- After adding new routes (FormRequest route model binding may need `@var` assertions).


---

## Guide 23 — Stagger Animations on Data Lists (mandatory)

Every component that renders a **list, table, or grid of data items** MUST apply the stagger entrance animation. This creates the polished, professional feel that is consistent across the whole application.

### Pattern

```javascript
import { useStagger } from '@/composables/useAnimations'
const { staggerRef, applyStagger } = useStagger(50) // delay in ms between items

// After data loads:
onMounted(async () => {
  await loadData()
  applyStagger()
})

// For reactive reloads (filters, search):
watch(filters, async () => {
  await loadData()
  await nextTick()
  applyStagger()
})
```

Template:
```html
<div ref="staggerRef" class="space-y-3">
  <div v-for="item in items" :key="item.id" class="stagger-item ...">
```

**Rules:**
- `ref="staggerRef"` on the **container** element (the `v-for` parent)
- `class="stagger-item"` (append to existing classes) on each **list item**
- Call `applyStagger()` **after** data is assigned and after `nextTick()` when needed
- Default delay: **50ms**; dense tables: **30–40ms**; heavy media cards: **60ms**
- Multiple lists on one page: use separate `useStagger()` calls with different refs
- Skipping stagger is only acceptable for single-item renders (e.g. a detail card, not a list)

**What counts as a list:** any `v-for` that renders 2+ items for the user to scan — cards, table rows, notification items, ticket rows, recovery codes, etc.

---

## Guide 24 — Full Integration Before Marking Complete (mandatory)

Every new feature MUST be 100% integrated and functional from backend through frontend before it is considered done. "Done" means:

1. **Backend**: endpoint exists, is authenticated/authorized, validates input, returns correct responses
2. **Frontend**: the Vue page/component calls the endpoint, handles success + error states, and displays real data (no hardcoded text, no `console.log` left in, no TODO comments)
3. **UI responsiveness**: the component works correctly on both mobile (small screen) and desktop. Use Tailwind responsive prefixes (`sm:`, `md:`, `lg:`) where needed
4. **i18n**: all user-visible strings use `$t()` — no hardcoded French or English text in templates
5. **Stagger**: data lists use `useStagger` (Guide 23)
6. **Tests**: at least one feature test covers the happy path. At least one Dusk test if the feature has a user-facing UI (Guide 18)
7. **Build**: `npm run build` succeeds without new errors
8. **Larastan**: `vendor/bin/phpstan analyse` reports zero errors (Guide 21)

If any of these are missing, the feature is **not done** — do not commit, do not mark complete, do not move to the next phase.
