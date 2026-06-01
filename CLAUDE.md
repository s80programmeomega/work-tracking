# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

---

## Session Bootstrap

At the start of every session, before doing anything else:

1. Read `docs/SESSION_STATE.md` — current task, branch, what to do next.
2. Read `docs/WORKING_GUIDELINES.md` — conventions and doc-trail checklist.
3. Read `docs/PROGRESSION.md` — full task status table.
4. If `/home/jonas/.claude/projects/` exists on this machine, read the `MEMORY.md` index there for accumulated user preferences and feedback. If absent (different machine), the rules below apply instead.

Then say: _"Read SESSION_STATE, WORKING_GUIDELINES, PROGRESSION. Resuming from [current task] — [what's next]."_

---

## Persistent Rules

These rules apply on every machine and every session. They do not change task to task.

- **Commits:** Never commit without explicit user instruction. Never push unless explicitly asked. No AI references in commit messages — this **overrides** the harness default of appending a `Co-Authored-By: Claude` trailer; never add it on this project. Run `vendor/bin/pint --dirty --format agent` before every commit.
- **Git remotes:** Two remotes — `origin` (Jonas, `s80programmeomega`) and `client` (Team-TDR-Consulting). Always use **HTTPS, never SSH**, for remote URLs and pushes. **Client has paid (2026-05-27) — push to both `origin` and `client` on every push.**
- **Push boundaries:** Never push or merge into `main` (hard rule). `jonas` **may** be pushed directly, but **only with explicit per-push user approval** — ask every time, no blanket pre-authorization; it is not a "never push" branch, but it is never pushed routinely. Feature branches push freely (to both remotes).
- **Code deletions:** Never delete code without a full impact check and explicit approval, even in auto-edit mode.
- **Language:** Docs in English. Code comments and log messages in French.
- **Testing docs:** Write `docs/testing/TASK_{N}_TESTING.md` for every completed task.
- **State docs:** Update `docs/PROGRESSION.md` and `docs/SESSION_STATE.md` at the end of every task.
- **Servers:** Stop any background server started (serve, vite, queue, reverb) before ending a turn.
- **Memory sync:** When the user says _"Update CLAUDE.md with current memory"_ — read only the memory files for this project (the ones whose path corresponds to the current working directory), update the Persistent Rules section if anything has changed, and commit.

---

## Project Overview

**Work Tracking** is a task management application with a hierarchy: **Projet → Activité → Tâches**. Built with Laravel 10 backend and Vue.js 3 frontend.

## Tech Stack

- **Backend**: Laravel 10 + Laravel Fortify + Sanctum + Spatie Permissions
- **Frontend**: Vue.js 3 + AdminLTE3 + TailwindCSS
- **Build**: Vite + PostCSS
- **Database**: PostgreSQL/MySQL

## Development Commands

### Backend (Laravel)
```bash
# Start development server
php artisan serve

# Run migrations
php artisan migrate

# Rollback migrations
php artisan migrate:rollback

# Fresh migration with seeders
php artisan migrate:fresh --seed

# Create controllers
php artisan make:controller ProjetController --resource

# Create models with migration
php artisan make:model Projet -m

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Run tests
php artisan test
./vendor/bin/phpunit
```

### Frontend (Vue.js + Vite)
```bash
# Start Vite dev server with hot reload
npm run dev

# Build for production
npm run build

# Install dependencies
npm install
```

### Database
```bash
# Access database via artisan
php artisan tinker
```

## Architecture

### Data Model Hierarchy
The application follows a strict hierarchy:
- **Projet** (1) → **Activités** (n) → **Tâches** (n)
- **User** (n) ↔ **Tâches** (n) via pivot table `tache_user`
- **Tâche** (1) → **Commentaires** (n)
- **Tâche** (1) → **Documents** (n)

### Key Laravel Concepts

**Authentication & Authorization**:
- Uses Laravel Fortify for authentication
- Laravel Sanctum for API token authentication
- Spatie Laravel Permission for roles/permissions
- User roles: `super_admin`, `manager`, `responsable_n1`, `responsable_n2`, `cadre`, `stagiaire`

**Task Status Workflow**:
- Status: `a_faire`, `en_cours`, `termine`
- Priority: `faible`, `moyenne`, `elevee`, `critique`
- Tasks include: `taux_realisation` (0-100%), `validation_superieur`, `verrou_reevaluation`

### Frontend Structure

**Vue.js Architecture**:
- Entry point: `resources/js/app.js`
- Main app: `resources/js/App.vue`
- Components organized in: `resources/js/components/`
  - `charts/` - Chart components
  - `common/` - Shared/reusable components
  - `dashboard/` - Dashboard views
  - `layout/` - Layout components (Navbar, Sidebar, etc.)
- Pages: `resources/js/pages/`
- Composables: `resources/js/composables/` (Vue composition functions)
- Icons: `resources/js/icons/`

**UI Libraries**:
- AdminLTE3 imported globally in `app.js`
- TailwindCSS configured for `.blade.php`, `.js`, `.vue` files
- Vite configured with Vue plugin and Laravel plugin

### Backend Structure

**Models**: Currently only `User` model exists at `app/Models/User.php`
- Uses: `HasApiTokens`, `HasFactory`, `Notifiable`
- Standard Laravel authentication model

**Controllers**: Base controller at `app/Http/Controllers/Controller.php`
- Planned controllers: `ProjetController`, `ActiviteController`, `TacheController`, `UserController`, `NotificationController`, `ReportingController`

**Routes**:
- Web routes: `routes/web.php` (currently minimal)
- API routes: `routes/api.php` (Sanctum-protected `/user` endpoint)

**Migrations**:
- Basic Laravel migrations present (users, password_resets, failed_jobs, personal_access_tokens)
- Spatie permissions tables migration included

## Key Implementation Notes

**When creating models**, follow the planned structure from README.md:
- `Projet`: nom, description, date_debut, date_fin, responsable_id
- `Activite`: projet_id, nom, description, responsable_id, date_debut, date_fin
- `Tache`: activite_id, titre, description, objectif, indicateurs_resultats, statut, priorite, echeance, taux_realisation, validation_superieur, verrou_reevaluation, commentaire

**Service Layer Pattern**: Use service classes for business logic
- Planned services: `ProjetService`, `TacheService`, `NotificationService`, `ReportingService`

**Enums**: Use PHP enums for status and priority
- `StatusTache`, `Priorite`, `Role`

## Testing

Run tests with:
```bash
php artisan test
```

Test structure:
- Feature tests: `tests/Feature/`
- Unit tests: `tests/Unit/`

## Branch Strategy

- Main branch: `main`
- Current development branch: `feature/integrate`

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to enhance the user's satisfaction building Laravel applications.

## Foundational Context
This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.4.16
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v10
- laravel/prompts (PROMPTS) - v0
- laravel/sanctum (SANCTUM) - v3
- laravel/mcp (MCP) - v0
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- phpunit/phpunit (PHPUNIT) - v10
- vue (VUE) - v3
- tailwindcss (TAILWINDCSS) - v4

## Conventions
- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts
- Do not create verification scripts or tinker when tests cover that functionality and prove it works. Unit and feature tests are more important.

## Application Structure & Architecture
- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling
- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Replies
- Be concise in your explanations - focus on what's important rather than explaining obvious details.

## Documentation Files
- You must only create documentation files if explicitly requested by the user.

=== boost rules ===

## Laravel Boost
- Laravel Boost is an MCP server that comes with powerful tools designed specifically for this application. Use them.

## Artisan
- Use the `list-artisan-commands` tool when you need to call an Artisan command to double-check the available parameters.

## URLs
- Whenever you share a project URL with the user, you should use the `get-absolute-url` tool to ensure you're using the correct scheme, domain/IP, and port.

## Tinker / Debugging
- You should use the `tinker` tool when you need to execute PHP to debug code or query Eloquent models directly.
- Use the `database-query` tool when you only need to read from the database.

## Reading Browser Logs With the `browser-logs` Tool
- You can read browser logs, errors, and exceptions using the `browser-logs` tool from Boost.
- Only recent browser logs will be useful - ignore old logs.

## Searching Documentation (Critically Important)
- Boost comes with a powerful `search-docs` tool you should use before any other approaches when dealing with Laravel or Laravel ecosystem packages. This tool automatically passes a list of installed packages and their versions to the remote Boost API, so it returns only version-specific documentation for the user's circumstance. You should pass an array of packages to filter on if you know you need docs for particular packages.
- The `search-docs` tool is perfect for all Laravel-related packages, including Laravel, Inertia, Livewire, Filament, Tailwind, Pest, Nova, Nightwatch, etc.
- You must use this tool to search for Laravel ecosystem documentation before falling back to other approaches.
- Search the documentation before making code changes to ensure we are taking the correct approach.
- Use multiple, broad, simple, topic-based queries to start. For example: `['rate limiting', 'routing rate limiting', 'routing']`.
- Do not add package names to queries; package information is already shared. For example, use `test resource table`, not `filament 4 test resource table`.

### Available Search Syntax
- You can and should pass multiple queries at once. The most relevant results will be returned first.

1. Simple Word Searches with auto-stemming - query=authentication - finds 'authenticate' and 'auth'.
2. Multiple Words (AND Logic) - query=rate limit - finds knowledge containing both "rate" AND "limit".
3. Quoted Phrases (Exact Position) - query="infinite scroll" - words must be adjacent and in that order.
4. Mixed Queries - query=middleware "rate limit" - "middleware" AND exact phrase "rate limit".
5. Multiple Queries - queries=["authentication", "middleware"] - ANY of these terms.

=== php rules ===

## PHP

- Always use curly braces for control structures, even if it has one line.

### Constructors
- Use PHP 8 constructor property promotion in `__construct()`.
    - <code-snippet>public function __construct(public GitHub $github) { }</code-snippet>
- Do not allow empty `__construct()` methods with zero parameters unless the constructor is private.

### Type Declarations
- Always use explicit return type declarations for methods and functions.
- Use appropriate PHP type hints for method parameters.

<code-snippet name="Explicit Return Types and Method Params" lang="php">
protected function isAccessible(User $user, ?string $path = null): bool
{
    ...
}
</code-snippet>

## Comments
- Prefer PHPDoc blocks over inline comments. Never use comments within the code itself unless there is something very complex going on.

## PHPDoc Blocks
- Add useful array shape type definitions for arrays when appropriate.

## Enums
- Typically, keys in an Enum should be TitleCase. For example: `FavoritePerson`, `BestLake`, `Monthly`.

=== laravel/core rules ===

## Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using the `list-artisan-commands` tool.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Database
- Always use proper Eloquent relationship methods with return type hints. Prefer relationship methods over raw queries or manual joins.
- Use Eloquent models and relationships before suggesting raw database queries.
- Avoid `DB::`; prefer `Model::query()`. Generate code that leverages Laravel's ORM capabilities rather than bypassing them.
- Generate code that prevents N+1 query problems by using eager loading.
- Use Laravel's query builder for very complex database operations.

### Model Creation
- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `list-artisan-commands` to check the available options to `php artisan make:model`.

### APIs & Eloquent Resources
- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

### Controllers & Validation
- Always create Form Request classes for validation rather than inline validation in controllers. Include both validation rules and custom error messages.
- Check sibling Form Requests to see if the application uses array or string based validation rules.

### Queues
- Use queued jobs for time-consuming operations with the `ShouldQueue` interface.

### Authentication & Authorization
- Use Laravel's built-in authentication and authorization features (gates, policies, Sanctum, etc.).

### URL Generation
- When generating links to other pages, prefer named routes and the `route()` function.

### Configuration
- Use environment variables only in configuration files - never use the `env()` function directly outside of config files. Always use `config('app.name')`, not `env('APP_NAME')`.

### Testing
- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

### Vite Error
- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== laravel/v10 rules ===

## Laravel 10

- Use the `search-docs` tool to get version-specific documentation.
- Middleware typically live in `app/Http/Middleware/` and service providers in `app/Providers/`.
- Laravel 10 has a `bootstrap/app.php` file that creates the application instance and binds kernel contracts, but does not use it for application configuration like Laravel 11:
    - Middleware registration is in `app/Http/Kernel.php`
    - Exception handling is in `app/Exceptions/Handler.php`
    - Console commands and schedule registration is in `app/Console/Kernel.php`
    - Rate limits likely exist in `RouteServiceProvider` or `app/Http/Kernel.php`
- When using Eloquent model casts, you must use `protected $casts = [];` and not the `casts()` method. The `casts()` method isn't available on models in Laravel 10.

=== pint/core rules ===

## Laravel Pint Code Formatter

- You must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== phpunit/core rules ===

## PHPUnit

- This application uses PHPUnit for testing. All tests must be written as PHPUnit classes. Use `php artisan make:test --phpunit {name}` to create a new test.
- If you see a test using "Pest", convert it to PHPUnit.
- Every time a test has been updated, run that singular test.
- When the tests relating to your feature are passing, ask the user if they would like to also run the entire test suite to make sure everything is still passing.
- Tests should test all of the happy paths, failure paths, and weird paths.
- You must not remove any tests or test files from the tests directory without approval. These are not temporary or helper files; these are core to the application.

### Running Tests
- Run the minimal number of tests, using an appropriate filter, before finalizing.
- To run all tests: `php artisan test --compact`.
- To run all tests in a file: `php artisan test --compact tests/Feature/ExampleTest.php`.
- To filter on a particular test name: `php artisan test --compact --filter=testName` (recommended after making a change to a related file).

=== tailwindcss/core rules ===

## Tailwind CSS

- Use Tailwind CSS classes to style HTML; check and use existing Tailwind conventions within the project before writing your own.
- Offer to extract repeated patterns into components that match the project's conventions (i.e. Blade, JSX, Vue, etc.).
- Think through class placement, order, priority, and defaults. Remove redundant classes, add classes to parent or child carefully to limit repetition, and group elements logically.
- You can use the `search-docs` tool to get exact examples from the official documentation when needed.

### Spacing
- When listing items, use gap utilities for spacing; don't use margins.

<code-snippet name="Valid Flex Gap Spacing Example" lang="html">
    <div class="flex gap-8">
        <div>Superior</div>
        <div>Michigan</div>
        <div>Erie</div>
    </div>
</code-snippet>

### Dark Mode
- If existing pages and components support dark mode, new pages and components must support dark mode in a similar way, typically using `dark:`.

=== tailwindcss/v4 rules ===

## Tailwind CSS 4

- Always use Tailwind CSS v4; do not use the deprecated utilities.
- `corePlugins` is not supported in Tailwind v4.
- In Tailwind v4, configuration is CSS-first using the `@theme` directive — no separate `tailwind.config.js` file is needed.

<code-snippet name="Extending Theme in CSS" lang="css">
@theme {
  --color-brand: oklch(0.72 0.11 178);
}
</code-snippet>

- In Tailwind v4, you import Tailwind using a regular CSS `@import` statement, not using the `@tailwind` directives used in v3:

<code-snippet name="Tailwind v4 Import Tailwind Diff" lang="diff">
   - @tailwind base;
   - @tailwind components;
   - @tailwind utilities;
   + @import "tailwindcss";
</code-snippet>

### Replaced Utilities
- Tailwind v4 removed deprecated utilities. Do not use the deprecated option; use the replacement.
- Opacity values are still numeric.

| Deprecated |	Replacement |
|------------+--------------|
| bg-opacity-* | bg-black/* |
| text-opacity-* | text-black/* |
| border-opacity-* | border-black/* |
| divide-opacity-* | divide-black/* |
| ring-opacity-* | ring-black/* |
| placeholder-opacity-* | placeholder-black/* |
| flex-shrink-* | shrink-* |
| flex-grow-* | grow-* |
| overflow-ellipsis | text-ellipsis |
| decoration-slice | box-decoration-slice |
| decoration-clone | box-decoration-clone |
</laravel-boost-guidelines>
