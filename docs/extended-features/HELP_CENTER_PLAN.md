# In-App Help Center + AI Q&A — Implementation Plan

> **Status:** Not started.
> **Scheduled:** After Task 14 (Platform Super Admin Dashboard), as the final v2 deliverable.
> **Branch (when started):** `feature/v2-help-center`
> **Owner:** TBD (Jonas + future PM/support hire).

This file is a standalone plan — it is **not** part of `IMPLEMENTATION_PLAN.md`'s numbered task list. Treat it like Task 15 in spirit, but separate so the v2 plan can ship without it.

---

## 1. Goal

Ship a Help Center **inside** the existing Laravel + Vue SPA so that:

- Authenticated end-users open it from a sidebar entry and read manuals with screenshots.
- A non-developer (PM / support / future hire) can author articles from inside the app with a rich-text editor — no git, no Markdown required.
- Search returns relevant articles fast even with 20–100 entries.
- An AI Q&A button (powered by the Anthropic Claude API) lets a user ask a question in natural language and get an answer grounded in the actual articles, with citations.

Everything stays in the existing codebase. No third-party doc platform. No public-facing marketing site (covered separately if/when needed).

---

## 2. Audience & non-goals

**In scope (audience):** authenticated workspace members of any role. Articles are workspace-agnostic — the same set is visible to every logged-in user.

**Out of scope:**
- Public, unauthenticated readers (SEO, marketing site). Add later if needed.
- Per-workspace custom articles. The Help Center is global to the app.
- Article versioning ("manual for v2.0 vs v2.1"). One canonical version per article.
- Multiple admin authors editing the same draft simultaneously. Last write wins; no locking.

---

## 3. Architecture overview

```
┌─────────────────── End-user (every authenticated role) ───────────────────┐
│                                                                            │
│  Sidebar → "Aide" → /help                                                  │
│   ├─ /help                      landing: search + featured categories      │
│   ├─ /help/c/{category-slug}    category list                              │
│   ├─ /help/a/{article-slug}     full article (Tiptap → HTML, sanitized)    │
│   └─ /help/ask                  AI Q&A drawer (natural-language)           │
│                                                                            │
└────────────────────────────────────────────────────────────────────────────┘

┌──────────── Admin (super_admin + directeur/owner) ────────────────────────┐
│                                                                            │
│  /admin/help-articles                                                      │
│   ├─ list (table, filters by category/published)                           │
│   ├─ create / edit (Tiptap editor, drag-drop image upload, FR/EN tabs)     │
│   └─ categories CRUD (same screen, lightweight)                            │
│                                                                            │
└────────────────────────────────────────────────────────────────────────────┘

Storage
  - MySQL tables:    help_categories, help_articles, help_article_images,
                     help_ai_queries (audit log of Q&A calls)
  - File storage:    storage/app/public/help-images/{uuid}.png
                     served at /storage/help-images/* via `php artisan storage:link`

Search
  - MySQL FULLTEXT(titre_fr, titre_en, body_plain_fr, body_plain_en)
  - body_plain_* is auto-extracted from Tiptap HTML on save (model boot hook)

AI Q&A (separate phase, see §11)
  - POST /api/help/ask  →  retrieves top-K articles by FULLTEXT,
                            sends question + article context to Claude API,
                            returns answer + citations (article links).
  - Server-side rate limit + per-user daily quota.
```

---

## 4. Data model

### 4.1 Migration `create_help_categories_table`

```php
Schema::create('help_categories', function (Blueprint $table) {
    $table->id();
    $table->string('slug')->unique();              // 'getting-started'
    $table->string('nom_fr');                       // 'Premiers pas'
    $table->string('nom_en');                       // 'Getting started'
    $table->text('description_fr')->nullable();
    $table->text('description_en')->nullable();
    $table->string('icon', 32)->default('fa-book'); // FontAwesome class
    $table->integer('position')->default(0);
    $table->timestamp('published_at')->nullable();
    $table->softDeletes();
    $table->timestamps();
    $table->index('position');
});
```

### 4.2 Migration `create_help_articles_table`

```php
Schema::create('help_articles', function (Blueprint $table) {
    $table->id();
    $table->foreignId('category_id')->constrained('help_categories')->cascadeOnDelete();
    $table->string('slug')->unique();
    $table->string('titre_fr');
    $table->string('titre_en');
    $table->longText('body_fr');                     // Tiptap HTML, post-sanitize
    $table->longText('body_en');
    $table->longText('body_plain_fr');               // extracted text, for search
    $table->longText('body_plain_en');
    $table->string('cover_image')->nullable();       // path under public disk
    $table->unsignedInteger('views_count')->default(0);
    $table->timestamp('published_at')->nullable();   // null = draft
    $table->foreignId('created_by')->constrained('users');
    $table->foreignId('updated_by')->nullable()->constrained('users');
    $table->softDeletes();
    $table->timestamps();

    $table->index(['category_id', 'published_at']);
});

// FULLTEXT must be added in raw SQL (Schema::table fluent API doesn't expose it
// portably for MySQL ≤ 5.7):
DB::statement('ALTER TABLE help_articles
    ADD FULLTEXT idx_help_articles_search
    (titre_fr, titre_en, body_plain_fr, body_plain_en)');
```

### 4.3 Migration `create_help_article_images_table`

```php
Schema::create('help_article_images', function (Blueprint $table) {
    $table->id();
    $table->foreignId('article_id')->constrained('help_articles')->cascadeOnDelete();
    $table->string('path');                          // help-images/{uuid}.png
    $table->string('mime', 64);
    $table->unsignedInteger('size_bytes');
    $table->foreignId('uploaded_by')->constrained('users');
    $table->timestamps();
});
```

### 4.4 Migration `create_help_ai_queries_table` (Phase 4)

```php
Schema::create('help_ai_queries', function (Blueprint $table) {
    $table->id();
    $table->foreignId('user_id')->constrained();
    $table->string('question', 1000);
    $table->json('retrieved_article_ids');          // top-K context
    $table->longText('answer');
    $table->unsignedInteger('input_tokens')->default(0);
    $table->unsignedInteger('output_tokens')->default(0);
    $table->string('model', 64);                    // 'claude-haiku-4-5'
    $table->float('latency_ms');
    $table->boolean('flagged_helpful')->nullable(); // optional user feedback
    $table->timestamps();

    $table->index('user_id');
});
```

---

## 5. Models

- `App\Models\HelpCategory` — `hasMany(HelpArticle::class)`, `slug`, `position` cast, scope `published`.
- `App\Models\HelpArticle` — `belongsTo(HelpCategory)`, `belongsTo(User, 'created_by')`, scope `published`, scope `searchable(string $q)` using `MATCH … AGAINST` boolean mode. Boot hook auto-fills `body_plain_*` from `body_*` via a `HtmlSanitizer + strip_tags` helper.
- `App\Models\HelpArticleImage` — `belongsTo(HelpArticle)`, accessor `url` returning `Storage::disk('public')->url($this->path)`.
- `App\Models\HelpAiQuery` — `belongsTo(User)`, casts `retrieved_article_ids` to `array`.

---

## 6. Permissions

Two new permission strings in `app/Permissions/Permission.php`:

| Constant | String | Granted to |
|---|---|---|
| `HELP_ARTICLES_READ` | `help_articles.read` | every authenticated role (default in `forRole()` for all) |
| `HELP_ARTICLES_MANAGE` | `help_articles.manage` | super_admin + directeur/owner (via `forRole('owner')`'s `array_diff(self::all(), [...])`) |
| `HELP_AI_ASK` | `help.ai_ask` | every authenticated role; admin can revoke per user later if quota abuse appears |

Mirror in `resources/js/permissions/Permission.js`. Expose `can_read_help_articles`, `can_manage_help_articles`, `can_ask_help_ai` in `WorkspaceController` user_permissions payload (3 locations, same pattern as Tasks 7/8/9). Add `canReadHelpArticles`, `canManageHelpArticles`, `canAskHelpAi` to `useWorkspacePermissions.js`.

Update `docs/PERMISSIONS_MATRIX.md` with a "Help Center" section and a changelog row.

---

## 7. Backend endpoints

All under `Route::middleware('auth:sanctum')` group in `routes/api.php`.

### 7.1 Read (every authenticated user)

```
GET    /api/help/categories                       index, published only
GET    /api/help/categories/{slug}                show + paginated articles
GET    /api/help/articles                         ?q=…&category=…&page=…  FULLTEXT-backed
GET    /api/help/articles/{slug}                  show + views_count++
```

### 7.2 Admin (HELP_ARTICLES_MANAGE)

```
GET    /api/admin/help/categories                 incl. drafts
POST   /api/admin/help/categories                 create
PUT    /api/admin/help/categories/{id}            update
DELETE /api/admin/help/categories/{id}            soft-delete

GET    /api/admin/help/articles                   incl. drafts, filters
POST   /api/admin/help/articles                   create draft
PUT    /api/admin/help/articles/{id}              update (any field)
POST   /api/admin/help/articles/{id}/publish      set published_at = now()
POST   /api/admin/help/articles/{id}/unpublish    null published_at
DELETE /api/admin/help/articles/{id}              soft-delete

POST   /api/admin/help/articles/{id}/images       multipart upload (max 5 MB, png/jpg/webp/gif)
DELETE /api/admin/help/article-images/{id}        only if owned by uploader or super_admin
```

### 7.3 AI Q&A (HELP_AI_ASK, Phase 4)

```
POST   /api/help/ask                              { question: string }
GET    /api/help/ask/history                      user's own past queries
POST   /api/help/ask/{id}/feedback                { helpful: bool }
```

### 7.4 Form requests

- `StoreHelpArticleRequest`, `UpdateHelpArticleRequest` — validate `titre_fr|titre_en` required, `body_fr|body_en` required, `category_id` exists, `slug` unique except self.
- `StoreHelpCategoryRequest`, `UpdateHelpCategoryRequest` — same shape.
- `AskHelpAiRequest` — `question` required, max 1000 chars, `Throttle:5,1` (5/min) + custom daily quota middleware.

### 7.5 HTML sanitization

`composer require mews/purifier` (free, MIT). Configure an allowlist in `config/purifier.php`:

```php
'help-article' => [
    'HTML.Allowed' => 'p,br,h2,h3,h4,strong,em,u,s,code,pre,ul,ol,li,a[href|title|target],img[src|alt|width|height],blockquote,hr,table,thead,tbody,tr,th,td',
    'AutoFormat.RemoveEmpty' => true,
    'URI.AllowedSchemes' => ['http' => true, 'https' => true, 'mailto' => true],
],
```

Apply on save in `HelpArticle::boot()` via a `saving` hook:
```php
$model->body_fr = Purifier::clean($model->body_fr, 'help-article');
$model->body_plain_fr = strip_tags($model->body_fr);
```

---

## 8. Frontend pages (Vue 3 + TailwindCSS)

### 8.1 Reader (3 pages)

| Path | Component | Notes |
|---|---|---|
| `/help` | `resources/js/pages/help/HelpIndex.vue` | Big search box + grid of category cards with icon + article count |
| `/help/c/:slug` | `resources/js/pages/help/HelpCategory.vue` | Category header + article list, breadcrumb back to `/help` |
| `/help/a/:slug` | `resources/js/pages/help/HelpArticle.vue` | Renders sanitized HTML inside a `<div class="prose dark:prose-invert">` (TailwindCSS Typography plugin — add if missing). Sidebar with related articles + "Was this helpful?" thumbs |

Sidebar nav entry in `AppSidebar.vue`, somewhere reasonable (after the user's main work area, before profile):

```html
<router-link :to="{ name: 'help.index' }" dusk="nav-help">
  <i class="fas fa-question-circle"></i>
  Aide
</router-link>
```

### 8.2 Editor (admin)

| Path | Component | Notes |
|---|---|---|
| `/admin/help-articles` | `resources/js/pages/admin/HelpArticlesList.vue` | Table, filter chips (Published / Draft / All), search box, "New article" button. Permission-gated by `canManageHelpArticles` |
| `/admin/help-articles/:id/edit` | `resources/js/pages/admin/HelpArticleEditor.vue` | Tiptap editor + FR/EN tabs + category picker + cover image upload + publish toggle |
| `/admin/help-categories` | inline modal inside the list page | Light CRUD, no dedicated page needed |

### 8.3 Tiptap editor setup

```
npm install @tiptap/vue-3 @tiptap/starter-kit @tiptap/extension-image \
            @tiptap/extension-link @tiptap/extension-placeholder
```

Drag-and-drop image extension:
- On `drop` / `paste` of a `File`, POST to `/api/admin/help/articles/{id}/images` (the article must already be a saved draft so it has an id — autosave on first keystroke).
- On 200, inject `<img src="…">` at cursor.
- Keep the original PNG; do not transcode server-side for v1.

Output of editor: HTML string. Passed to the backend which Purifier-sanitizes again on save (defense in depth).

### 8.4 Bilingual editing

Two tabs at the top of the editor: "Français" / "English". Each switches `v-model` between `articleDraft.body_fr` and `articleDraft.body_en`. Same Tiptap instance, content swapped via `editor.commands.setContent(…)` on tab change. Both must be non-empty to publish.

---

## 9. Search

- Backend: `HelpArticle::searchable($q)->published()->paginate(20)`.
- The scope: `MATCH(titre_fr, titre_en, body_plain_fr, body_plain_en) AGAINST (? IN BOOLEAN MODE)` with `$q` rewritten as `+word1* +word2*` for prefix matching.
- Frontend: debounced input on `/help` landing → `GET /api/help/articles?q=…` → render results inline below the search box.
- No client-side fuzzy matching. MySQL FULLTEXT handles plurals/stemming acceptably for FR + EN.

---

## 10. Auto-screenshot pipeline (anti-staleness)

Reuse the existing Dusk setup. New directory `tests/Browser/HelpScreenshots/`.

```
tests/Browser/HelpScreenshots/
├── DashboardScreenshotTest.php
├── KanbanScreenshotTest.php
├── ValidationFlowScreenshotTest.php
└── …
```

Each test class:

```php
class DashboardScreenshotTest extends WorkTrackingTestCase
{
    use DatabaseTruncation;

    public function test_capture_dashboard(): void
    {
        $user = User::factory()->create([...]);
        $this->seedDemoActivity($user);   // shared helper for realistic visuals

        $this->browse(function (Browser $browser) use ($user) {
            $this->signInAs($browser, $user)
                ->visit('/')
                ->waitFor('@dashboard-loaded', 10)
                ->screenshot('help/dashboard-overview');
            // → tests/Browser/screenshots/help/dashboard-overview.png
        });
    }
}
```

Add a tiny script `bin/refresh-help-screenshots.sh` that:
1. Clears `tests/Browser/screenshots/help/`.
2. Runs `php artisan dusk --filter=HelpScreenshots`.
3. Copies `tests/Browser/screenshots/help/*.png` → `storage/app/public/help-images/auto/`.

Articles reference these auto-paths for stable UI panels (`/storage/help-images/auto/dashboard-overview.png`). The PM uploads custom screenshots via the editor for one-off illustrations; those go to `/storage/help-images/{uuid}.png` and are tracked in `help_article_images`.

When a UI change breaks the visuals, the Dusk test either still passes (and the screenshot just updates) or fails (selector renamed) — failure is the signal to refresh.

---

## 11. AI Q&A (Phase 4 — depends on Phases 1–3 being live)

### 11.1 What the user sees

A `/help/ask` route or a global "Ask AI" button in the help center header opens a chat-like drawer:

```
┌──────────────────────────────────────────────────────┐
│  Question:  [How do I submit a result for a task?  ] │
│  [Ask ▸]                                              │
├──────────────────────────────────────────────────────┤
│  Answer:                                              │
│  To submit a result, open the task detail page and   │
│  click the "Soumettre résultat" button [1]. Fill in  │
│  the form and click submit. Your responsable will…   │
│                                                       │
│  Sources:                                             │
│  [1] How to submit a task result   →                  │
│  [2] Result validation circuit (N0/N1/N2)             │
│                                                       │
│  Was this helpful?  👍  👎                            │
└──────────────────────────────────────────────────────┘
```

### 11.2 Backend pipeline (retrieval + generation)

```
POST /api/help/ask  body: { question: "..." }

1. Throttle: 5 requests / 60 seconds / user (Laravel Rate Limiter).
2. Quota: 20 questions / day / user (custom middleware reading help_ai_queries).
3. Retrieve top-K=5 articles via FULLTEXT:
     SELECT id, slug, titre_fr, body_plain_fr,
            MATCH(...) AGAINST (?) AS relevance
     FROM help_articles
     WHERE published_at IS NOT NULL
     ORDER BY relevance DESC
     LIMIT 5
4. Build the prompt:
     System:
        You are a help assistant for Work-tracking, a French-language
        task-management app. Answer only from the provided articles.
        If the answer is not in them, say so. Cite article slugs in
        square brackets, e.g. [getting-started].
     User:
        Question: {question}
        Articles:
        [{slug}] {titre_fr}
        {body_plain_fr (truncated to ~2000 chars each)}
        ---
        ...
5. Call Anthropic Messages API (model: claude-haiku-4-5 for cost, with
   a config flag to upgrade to claude-sonnet-4-6 for hard questions).
   Use the official PHP SDK or a thin Guzzle wrapper.
6. Parse out citations (regex on [slug] tokens), map to article URLs.
7. Persist row in help_ai_queries (audit + analytics).
8. Return JSON: { answer, citations: [{slug, titre, url}], query_id }.
```

### 11.3 Why retrieval-augmented (RAG) and not "send Claude the whole knowledge base"

- 20–100 articles × ~5 KB plain text = ~100–500 KB. Fits in a single Claude context window, but expensive on every question.
- FULLTEXT pre-filter cuts the prompt to ~5 articles ≈ 10 KB → 95% token saving vs sending everything.
- Avoids hallucination on articles that don't apply: the model only sees relevant context.

### 11.4 Prompt caching

Anthropic's prompt cache (5-minute TTL) is a perfect fit:
- Cache the **system prompt** (long, stable, identical across users).
- Do NOT cache the articles — they change per question.

`cache_control: { type: 'ephemeral' }` on the system prompt block. Save ~80% on input tokens for hot Q&A periods.

### 11.5 Model selection

| Model | When to use |
|---|---|
| `claude-haiku-4-5` (default) | Standard "how do I X" questions. Fastest, cheapest. |
| `claude-sonnet-4-6` (opt-in via admin config) | Hard / synthesis questions. Slower, ~5× cost. |

Expose as `config('services.anthropic.help_qa_model')` so it's tunable per environment without code change.

### 11.6 Cost guard-rails

- Throttle: 5/min/user (Laravel `RateLimiter`).
- Daily quota: 20 questions/user, configurable per role via `config('help.ai_quota_per_day.{role}', 20)`.
- Per-environment monthly cap: a scheduled `php artisan help:check-ai-budget` warns admins when monthly spend > $X. Won't auto-disable (avoid surprise outages), just alerts.
- Log every call to `help_ai_queries` with token counts — easy to compute monthly cost.

### 11.7 Privacy & compliance

- The question text is sent to Anthropic. **No workspace data or PII** beyond what the user typed; only published help articles are included in the prompt context.
- Add a note in the UI: "Powered by Claude (Anthropic). Your question is sent to a third-party AI service." — covers GDPR Art. 13 transparency.
- Don't send the user's name, email, workspace id, or any tache/projet data in the prompt.
- `help_ai_queries.question` is stored in your DB for analytics. Retention: cleared after 90 days via a scheduled job (`php artisan help:purge-old-ai-queries`).

### 11.8 Feedback loop

`POST /api/help/ask/{id}/feedback { helpful: bool }` updates `flagged_helpful`. Admin dashboard surfaces:
- 👎 questions ordered by recency → highlights gaps in the manual.
- Most-asked questions → suggests articles to write.

---

## 12. Translations

Append to `lang/fr/help.php` and `lang/en/help.php` (new files):

- `nav.aide`
- `index.search_placeholder`, `index.no_results`, `index.categories_title`
- `article.last_updated`, `article.helpful_question`, `article.helpful_yes`, `article.helpful_no`
- `admin.list.title`, `admin.list.new`, `admin.list.publish`, `admin.list.unpublish`, `admin.list.delete`
- `admin.editor.title_fr`, `admin.editor.title_en`, `admin.editor.body_fr`, `admin.editor.body_en`, `admin.editor.category`, `admin.editor.cover_image`, `admin.editor.publish`, `admin.editor.save_draft`
- `errors.ai_quota_exceeded`, `errors.ai_unavailable`, `errors.cannot_view_help`, `errors.cannot_manage_help`
- `ai.system_prompt` (NOT user-facing — but stored in lang for review)
- `ai.privacy_notice`
- `ai.helpful_question`, `ai.placeholder`

---

## 13. Tests

### 13.1 Backend (PHPUnit)

| File | Coverage |
|---|---|
| `tests/Feature/HelpArticleReadTest` | index, show, search FULLTEXT relevance, drafts hidden from non-admin |
| `tests/Feature/HelpArticleAdminTest` | create, update, publish/unpublish, soft-delete, permission gate (collaborateur → 403) |
| `tests/Feature/HelpArticleImageUploadTest` | upload, mime/size validation, ownership delete |
| `tests/Feature/HelpAiAskTest` (Phase 4) | throttle, quota, model is called with sanitized prompt, citations parsed correctly. Mock Anthropic HTTP via Http::fake() |
| `tests/Unit/HelpArticleSearchScopeTest` | scope returns expected order for known relevance |
| `tests/Unit/HelpArticleSanitizationTest` | XSS payload stripped by Purifier; body_plain extracted correctly |

### 13.2 Dusk (Browser)

| File | Coverage |
|---|---|
| `tests/Browser/Help/HelpReaderTest` | open `/help`, search returns results, open an article, click a citation |
| `tests/Browser/Help/HelpEditorTest` | admin creates a draft, uploads an image, publishes; non-admin can't reach `/admin/help-articles` |
| `tests/Browser/Help/HelpAiAskTest` | submit a question, see an answer + at least one citation (Anthropic mocked at the SDK boundary) |

### 13.3 Manual test guide

`docs/testing/TASK_HELP_TESTING.md` (or `TASK_15` if numbering catches up).

---

## 14. Documentation trail (per Guide 7)

- `docs/PROGRESSION.md` — add a row for "Help Center + AI Q&A" once started.
- `docs/IMPLEMENTATION_PLAN.md` — leave alone; this plan is standalone. Optionally cross-reference at the end of the v2 task list.
- `docs/PERMISSIONS_MATRIX.md` — add Help Center section + changelog row.
- `docs/ONBOARDING.md` — short paragraph under "Key Workflows" describing how the Help Center fits.
- `docs/testing/TASK_HELP_TESTING.md` — manual test cases.

---

## 15. Phased delivery (~20h total, broken to ship-able chunks)

| Phase | Time | Deliverable | Can ship alone? |
|---|---|---|---|
| **1. Backend + storage** | 4h | Migrations, models, factories, sanitizer, read+admin controllers, 8 PHPUnit tests | Yes (admin-only endpoints exist, no UI yet) |
| **2. Reader UI** | 3h | 3 Vue pages, sidebar entry, FULLTEXT search, 1 Dusk test | Yes (read-only mode, articles seeded by tinker/seeder) |
| **3. Author UI + Tiptap** | 5h | Admin list + editor + image upload, 1 Dusk test | Yes (PM can start writing) |
| **4. Auto-screenshot pipeline** | 3h | Helper trait, 5 example screenshot tests, `bin/refresh-help-screenshots.sh` | Yes (optional polish) |
| **5. AI Q&A (RAG)** | 4h | `/api/help/ask` endpoint, prompt cache, throttle + quota, citations parser, audit table, feedback endpoint, 1 Dusk test | Yes (gated by config flag; disable=hide UI button) |
| **6. Docs + polish** | 1h | TASK_HELP_TESTING.md, ONBOARDING update, PERMISSIONS_MATRIX update | — |

Each phase = its own commit (or small commit chain) on `feature/v2-help-center`. The branch merges into `jonas` once Phase 5 is verified end-to-end.

---

## 16. Open questions to resolve before starting

1. **Bilingual or French-only for v1?** Dropping the `_en` columns saves ~30% UI work. Pick before Phase 1.
2. **`mews/purifier` vs `ezyang/htmlpurifier` direct vs a manual allowlist?** Purifier is mature and free, recommended. Confirm before Phase 1.
3. **Anthropic API key management:** where is it stored — `.env` `ANTHROPIC_API_KEY` like other secrets? Confirm before Phase 5. Add to `.env.example` with a clear placeholder.
4. **Daily quota numbers** (20/user/day) — tune after Phase 5 lands and you have a week of real usage data.
5. **Should `super_admin` and `directeur` share the manage permission, or only `super_admin`?** Plan says both. Confirm.
6. **Public-facing Help Center later?** If yes, the article model is reusable but you'll need an unauthenticated reader route + SEO meta tags + Blade SSR. Scoped separately when needed.

---

## 17. Risks and mitigations

| Risk | Mitigation |
|---|---|
| Tiptap learning curve (first rich-text editor in this app) | Budget +2h on Phase 3; Tiptap docs are excellent; can ship a basic toolbar first and add features iteratively |
| XSS via admin-authored HTML | Two layers: Purifier on save + `v-html` only on sanitized output. No `innerHTML` shortcuts |
| AI hallucination citing non-existent articles | Validate every `[slug]` in the answer matches an article row before returning; strip unknown citations |
| Anthropic API outage | Catch SDK exceptions, return 503 with translated `errors.ai_unavailable`. Don't block article reading |
| Cost overrun on AI | Hard daily quota + monthly budget alerts. Default model is Haiku (~$1/1M input tokens) |
| Search returns nothing because FR/EN mixed indexes | Verify FULLTEXT index works on multi-language content during Phase 1; fallback to per-language indexes if scores are off |
| Image storage growth | Schedule a `php artisan help:purge-orphan-images` to delete `help_article_images` rows whose `path` is no longer referenced by any article body. Run weekly |

---

## 18. What this plan does NOT cover

- Public marketing site for SEO (separate concern; static site generator or Blade SSR if needed later).
- Per-workspace customisation of help articles (the Help Center is global).
- Multi-version manuals (e.g. one set for v2.0, another for v3.0). Add a `help_article_versions` table later if needed.
- In-app guided tours / walkthroughs (e.g. Driver.js overlays). Different feature, can be added on top.
- Translation memory or auto-translation (FR → EN). Authors write both, or v1 is FR-only.

---

**Last updated:** 2026-05-23 (initial draft, captured during Task 9 follow-up discussion).
