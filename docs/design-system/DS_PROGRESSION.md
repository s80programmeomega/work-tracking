# Design System v1 — Progression Tracker

> Branch: `feature/design-system-v1`
> Started: 2026-05-27
> Plan: `docs/design-system/DS_PLAN.md`

---

## Status Legend

| Symbol | Meaning |
|---|---|
| ⬜ | Not started |
| 🔄 | In progress |
| ✅ | Complete |
| ⚠️ | Blocked |

---

## Phase 1 — Design Token Replacement

| # | Item | File | Status | Notes |
|---|------|------|--------|-------|
| 1.1 | Replace `@theme` color tokens | `resources/css/app.css` | ✅ | navy + blue palette, remap brand-* |
| 1.2 | Update typography base (13px) | `resources/css/app.css` | ✅ | `--text-theme-sm: 13px` + `@layer base html` |
| 1.3 | Zero out all shadow tokens | `resources/css/app.css` | ✅ | `--shadow-theme-*: none` |
| 1.4 | Add border-radius tokens | `resources/css/app.css` | ✅ | `--radius-1` through `--radius-4` |
| 1.5 | Build check + browser baseline | — | ✅ | `npm run build`, open /dashboard |

---

## Phase 2 — Core UI Components

| # | Component | File | Status | Notes |
|---|-----------|------|--------|-------|
| 2.1 | Button variants + DS sizes | `components/ui/Button.vue` | ✅ | primary/accent/outline/ghost/danger/add; h-30/h-24 |
| 2.2 | Badge DS tag colors + 10px | `components/ui/Badge.vue` | ✅ | purple/stone added; border always on |
| 2.3 | Card → border-based, no shadow | `components/ui/Card.vue` | ✅ | rounded-3, border-gray-200 |
| 2.4 | Alert → DS semantic color pairs | `components/ui/Alert.vue` | ✅ | rounded-3, no shadow |
| 2.5 | Modal → lighter backdrop, no shadow | `components/ui/Modal.vue` | ✅ | bg-black/40 |

---

## Phase 3 — Layout Shell

| # | Component | File | Status | Notes |
|---|-----------|------|--------|-------|
| 3.1 | Sidebar — navy logo badge, border | `components/layout/AppSidebar.vue` | ✅ | Gradient badges → bg-[#1E3A5F], border-gray-200 |
| 3.2 | Header — remove gradients, DS border | `components/layout/AppHeader.vue` | ✅ | border-b always visible, no shadow |
| 3.3 | MainLayout — remove hardcoded hex | `components/layout/MainLayout.vue` | ✅ | CSS vars, btn radius 4px |
| 3.4 | AdminLayout — DS bg color | `components/layout/AdminLayout.vue` | ✅ | bg-gray-100 dark:bg-gray-900 |

---

## Phase 4 — Domain Components

| # | Component | File | Status | Notes |
|---|-----------|------|--------|-------|
| 4.1 | Dashboard — flat cards, no gradients | `pages/Dashboard.vue` | ✅ | Flat white cards, DS selects, canonical tokens |
| 4.2 | TaskCard — DS priority border colors | `components/dashboard/TaskCard.vue` | ✅ | error-500/brand-500 left borders, no shadow |
| 4.3 | StatusBadge — DS semantic colors | `components/taches/StatusBadge.vue` | ✅ | a_faire/en_cours/termine → DS tokens |
| 4.4 | PriorityBadge — DS semantic colors | `components/taches/PriorityBadge.vue` | ✅ | faible/moyenne/elevee/critique → DS tokens |

---

## Phase 5 — Verification & Ship

| # | Step | Status | Notes |
|---|------|--------|-------|
| 5.1 | `npm run build` — no errors | ✅ | 2 pre-existing CSS scrollbar warnings only |
| 5.2 | Browser: Dashboard | ⬜ | Stat cards, buttons, badges |
| 5.3 | Browser: Projets list | ⬜ | Project cards, status badges |
| 5.4 | Browser: Taches | ⬜ | Task list, priority badges, kanban |
| 5.5 | Browser: Documents | ⬜ | Doc cards, archive rows, tags |
| 5.6 | Browser: Workspace settings | ⬜ | Workspace switcher, trial banner |
| 5.7 | Browser: Dark mode toggle | ⬜ | All pages readable in dark |
| 5.8 | `php artisan test --compact` | ✅ | 284/284 passed |
| 5.9 | `vendor/bin/pint --dirty` | ✅ | Passed — no PHP changes |
| 5.10 | Commit + push origin + client | ⬜ | |

---

## Session Log

| Date | Work done | Outcome |
|------|-----------|---------|
| 2026-05-27 | Created plan doc + progression doc | Ready to start implementation |
| 2026-05-27 | Phases 1–4 complete: tokens, core UI, layout shell, domain components | Build passes, 284/284 tests pass |
