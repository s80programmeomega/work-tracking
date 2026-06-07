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
| `tests/Feature/Help/HelpCenterTest.php` (RefreshDatabase) | Read endpoints (published-only categories/articles), draft filtering, view counter, 404 on draft, manage authorization (plain user 403, super_admin/owner allowed), CRUD, publish/unpublish, validation, soft-delete, HTML sanitization on save, image-delete authorization (uploader/super_admin only) |
| `tests/Feature/Help/HelpSearchTest.php` (DatabaseTruncation) | FULLTEXT search finds matching published article and excludes non-matching; prefix matching (`config` → `Configuration`) |

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

### TC-07 — Authorization (negative)

1. As a plain member, hit `GET /api/admin/help/articles` directly.
2. Expect: **403** (the "Gérer les articles" link is also hidden for them).
3. As a non-uploader manager, try `DELETE /api/admin/help/article-images/{id}` for an
   image uploaded by someone else → **403** (only uploader or super_admin).

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
