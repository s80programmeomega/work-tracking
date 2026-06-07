# Phase 7 Testing Guide — Help Center

> Branch: `feature/phase7-help-center`
> Prerequisites: `php artisan migrate` (MySQL — FULLTEXT index requires InnoDB) + `php artisan serve` + `npm run dev`
> Permissions seeded: `php artisan db:seed --class=RolePermissionSeeder`

---

## Scope

Phases 1–3 + 6 of `HELP_CENTER_PLAN.md` (AI Q&A and auto-screenshots are out of scope):

- Bilingual (FR/EN) categories + articles, published/draft lifecycle
- HTML body sanitized server-side via `mews/purifier` (`help-article` allowlist)
- MySQL FULLTEXT search across `titre_fr/en` + `body_plain_fr/en` (boolean mode, prefix)
- Reader UI: `/help`, `/help/c/:slug`, `/help/a/:slug`
- Author UI (owner/directeur + super_admin): list + Tiptap editor + image upload

---

## Automated tests

```bash
php artisan test --compact tests/Feature/Help/
```

| File | Coverage |
|---|---|
| `tests/Feature/Help/HelpCenterTest.php` (RefreshDatabase) | Read endpoints (published-only categories/articles), draft filtering, view counter, 404 on draft, manage authorization (plain user 403, super_admin/owner allowed), CRUD, publish/unpublish, validation, soft-delete, HTML sanitization on save, image-delete authorization (uploader/super_admin only), **granular permissions** (create-only ≠ publish/delete; publish-only ≠ create; `help_categories.manage` required for categories) |
| `tests/Feature/Help/HelpSearchTest.php` (DatabaseTruncation) | FULLTEXT search finds matching published article and excludes non-matching; prefix matching (`config` → `Configuration`) |

Expected: **18 passed** (15 + 3 granularity).

### Granular permissions (Phase 7 fix)

Management is split into action-based permissions — no single `manage`:
`help_articles.create`, `help_articles.edit`, `help_articles.publish`,
`help_articles.delete`, `help_articles.upload_image`, `help_categories.manage`.
Defaults: all six → owner/directeur + super_admin; managers and below stay read-only.
Each endpoint enforces its own permission (`AdminHelpController::authorizeHelp*`), and the
author list page hides each action button (`canCreate/Edit/Publish/Delete HelpArticles`).

> **Why two classes?** InnoDB FULLTEXT indexes do **not** see rows inserted inside an
> uncommitted transaction. `RefreshDatabase` wraps each test in a transaction, so
> `MATCH … AGAINST` would always return empty there. `HelpSearchTest` uses
> `DatabaseTruncation` (commits writes, truncates between tests) so FULLTEXT works.

Expected: **15 passed**.

---

## Pre-commit gates

```bash
vendor/bin/pint --dirty --format agent
php artisan clear-compiled && php -d memory_limit=1500M vendor/bin/phpstan analyse --memory-limit=1500M
```

Both must be clean (Pint `passed`, Larastan `[OK] No errors`).

---

## Manual test scenarios

### Prerequisites

1. A **super_admin** account and a **workspace owner/directeur** account.
2. A **plain member** (cadre/collaborateur/stagiaire/observateur) account.

### TC-01 — Author creates a draft (owner/super_admin)

1. Log in as owner or super_admin → sidebar **Aide** → **Gérer les articles** (top-right).
2. **Nouvel article** → pick a category, set slug + FR/EN titles, type FR/EN bodies in the Tiptap editors → **Enregistrer le brouillon**.
3. Expect: redirected to the list; article shows **Brouillon** status.

### TC-02 — Image upload requires a saved article

1. On a brand-new article (before first save), click the 🖼️ toolbar button.
2. Expect: alert "Enregistrez d'abord l'article…" (no orphan upload).
3. Save the draft → open it for edit → 🖼️ → choose a PNG/JPG/WEBP/GIF ≤ 5 MB.
4. Expect: image inserted inline; stored under `storage/app/public/help/images`.

### TC-03 — Publish / unpublish

1. From the list, click **Publier** on a draft.
2. Expect: status flips to **Publié**; the article now appears in the reader (`/help`).
3. Click **Dépublier** → it disappears from the reader but stays in the admin list.

### TC-04 — Reader: browse + read

1. Log in as a plain member → sidebar **Aide** (`/help`).
2. Expect: category grid with per-category published-article counts.
3. Open a category → published articles only; open an article → sanitized HTML in a
   `prose` block, view counter increments, related articles in the sidebar.

### TC-05 — Reader: FULLTEXT search

1. On `/help`, type ≥ 2 chars in the search box.
2. Expect: debounced results listing matching **published** articles only; a prefix
   like `config` matches `Configuration`.

### TC-06 — XSS / sanitization

1. As author, create an article whose FR body contains `<script>alert(1)</script>`
   and an `<img src=x onerror=alert(1)>`.
2. Save, then open the article in the reader.
3. Expect: no script executes; `<script>` and `onerror` are stripped (Purifier
   `help-article` allowlist). `body_plain_*` holds the de-tagged text for search.

### TC-08 — Reader language toggle (FR | EN)

1. As any user, open a published article (`/help/a/:slug`).
2. Expect: an **FR | EN** toggle near the title. The active language follows the app
   UI language by default.
3. Click the other language → title + body switch to that language's version
   **without** changing the whole app's UI language. Same toggle on the category page
   (`/help/c/:slug`) for category name + article titles.

### TC-09 — Editor cursor & contrast (author)

1. As an author, open the article editor (create or edit).
2. Expect: the caret is clearly visible and text is readable in **both** light and dark
   mode (dark text on white / white text on dark), the editor area has a visible
   min-height, and the chrome matches the other form inputs.

### TC-07 — Authorization (negative)

1. As a plain member, hit `GET /api/admin/help/articles` directly.
2. Expect: **403** (the "Gérer les articles" link is also hidden for them).
3. As a non-uploader manager, try `DELETE /api/admin/help/article-images/{id}` for an
   image uploaded by someone else → **403** (only uploader or super_admin).

### TC-10 — Help content in global search

1. Publish an article with a distinctive title; go to global search (`/search`).
2. Expect: the article appears under the **Help articles** tab; its category appears under
   **Help categories**; an attached image appears under **Help images** (searchable by filename).
3. Unpublish the article → it (and its images) **disappear** from search results.
4. A draft article must **never** appear in search (privacy guarantee — covered by
   `HelpSearchInclusionTest::test_draft_help_article_never_appears_in_search`).
5. **Deploy note:** Typesense schemas are not updated in place. On any environment, run
   `php artisan scout:flush` + `scout:import` for `App\Models\HelpArticle`,
   `App\Models\HelpCategory`, `App\Models\HelpArticleImage`, and ensure a queue worker is
   running if `SCOUT_QUEUE=true`.

### TC-11 — Category management (consolidated into the help admin page)

1. As owner/super_admin, open `/admin/help-articles` → switch to the **Catégories** tab
   (categories are managed on the same page as articles; no separate route).
2. Create a category (name FR/EN, slug auto-suggested, icon, position, publish toggle) →
   it appears in the Categories tab and in the article form's category dropdown.
3. Edit and delete a category; deleting does **not** delete its articles.
4. In the article form, click **+** next to the category dropdown → inline modal creates a
   category and auto-selects it.
5. As a plain member, the Categories tab is hidden and
   `POST /api/admin/help/categories` returns **403** (needs `help_categories.manage`).

### TC-12 — Help types in search export

1. Run a global search that returns help results; tick a few help rows and export the selection.
2. Expect an Excel sheet per help type (Articles d'aide / Catégories d'aide / Images d'aide)
   with the relevant columns; only **published** help content is exported (drafts excluded).

### Automated Dusk coverage

`tests/Browser/Help/HelpCenterTest.php` (3 tests, all green):
- reader sees the help index (categories grid + search box),
- reader opens an article and toggles FR ↔ EN,
- author sees the admin page and the Catégories tab renders.

> Dusk setup notes: test users need a `current_workspace_id` (the SPA redirects
> workspace-less non-admins to `/workspaces/create`); a super-admin author needs the
> `role` column = `super_admin` (signInAs derives the token's `is_super_admin` from
> `hasRole()`, which is column-based); async lists require `waitForText` rather than `pause`.

---

## Endpoints reference

Read (any authenticated user):
```
GET /api/help/categories
GET /api/help/categories/{slug}
GET /api/help/articles?q=&category=&page=&per_page=
GET /api/help/articles/{slug}
```

Manage (super_admin or workspace owner):
```
GET/POST/PUT/DELETE  /api/admin/help/categories[/{category}]
GET/POST/PUT/DELETE  /api/admin/help/articles[/{article}]
POST   /api/admin/help/articles/{article}/publish | /unpublish
POST   /api/admin/help/articles/{article}/images
DELETE /api/admin/help/article-images/{image}
```
