# Phase 11D Testing Guide — Help Center Draft Auto-Save

**Branch:** `feature/phase11d-help-draft-autosave`
**Date:** 2026-06-10
**Scope:** `PATCH /admin/help/articles/{article}/draft` endpoint + `HelpArticleForm.vue` debounced
auto-save + restore/discard banner.

---

## Prerequisites

- MySQL running, migrations applied (`php artisan migrate`).
- A super_admin user or a workspace member with `help_articles.edit` permission.
- The `RolePermissionSeeder` has been run (`php artisan db:seed --class=RolePermissionSeeder`).
- At least one published `HelpArticle` in the database for manual UI tests.
- Dev server running: `php artisan serve` + `npm run dev`.

---

## Automated Tests

Run the targeted test class:

```bash
php artisan test --compact tests/Feature/Help/HelpArticleDraftTest.php
```

Expected: **7 tests passing**.

---

## Manual Test Cases

### MT-1 — Auto-save indicator

1. Log in as a super_admin (or workspace member with `help_articles.edit`).
2. Navigate to **Admin → Centre d'aide** and open any existing article for editing.
3. Click inside the "Contenu (FR)" or "Contenu (EN)" editor and make a small change
   (type a word).
4. Wait **~5 seconds** without clicking Save.
5. **Expected:** A small grey text "Brouillon enregistré à HH:MM" (or "Draft saved at HH:MM")
   appears below the editors.
6. **Expected:** The network tab shows a `PATCH /api/admin/help/articles/{id}/draft` request
   with a `200` response.

### MT-2 — Restore banner on reload

1. After MT-1 completes (draft saved), **reload the page** without clicking the Save button.
2. **Expected:** An amber banner appears at the top of the form reading
   "Vous avez un brouillon non publié du {date}" (or English equivalent) with
   **Restaurer** and **Ignorer** buttons.
3. Click **Restaurer**.
4. **Expected:** The editor content reflects the draft change made in MT-1; banner disappears.

### MT-3 — Discard banner

1. Repeat MT-1 + MT-2 up to the banner display.
2. Click **Ignorer**.
3. **Expected:** Banner disappears; editor content stays as originally loaded (no draft applied).

### MT-4 — No banner when draft is older than last save

1. Edit an article in MT-1 → click **Enregistrer** (Save) → confirm redirect back.
2. Re-open the same article.
3. **Expected:** No amber restore banner (the article's `updated_at` is now newer than
   `draft_saved_at`).

### MT-5 — Auto-save does not fire on new articles

1. Navigate to **Nouvel article** (create mode).
2. Fill in the title fields and start typing in the body editors.
3. Wait > 5 seconds.
4. **Expected:** No `PATCH /api/admin/help/articles/null/draft` request is fired
   (auto-save is suppressed until `articleId` is set, i.e. only in edit mode).

### MT-6 — Authorization: 403 for plain user

1. Open an incognito session and log in as a user without `help_articles.edit` permission.
2. Make a direct `PATCH /api/admin/help/articles/{id}/draft` request via the browser console or
   Postman with a valid Sanctum token.
3. **Expected:** `403 Forbidden`.

### MT-7 — Publish does not lose changes

1. Open an article, make a content change, wait for auto-save (MT-1).
2. Click **Enregistrer** (full save, not just draft).
3. **Expected:** The saved article has the new content (the full `PUT` endpoint, not the draft
   endpoint, was called); no data loss.

---

## Negative Cases

| Scenario | Expected |
|---|---|
| PATCH draft without auth | 401 Unauthorized |
| PATCH draft with non-editor user | 403 Forbidden |
| PATCH draft with no body fields | 200 (all fields optional — `sometimes` validation) |
| Body editor unchanged for 5s | No `PATCH` fired (debounce watches `body_fr`/`body_en` only) |

---

## Cleanup

No cleanup required. Draft columns are nullable and do not affect published content.
