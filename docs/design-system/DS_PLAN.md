# Design System v1 — Implementation Plan

> Source spec: `Design System.html` (provided 2026-05-27)
> Branch: `feature/design-system-v1` (from `jonas`)

---

## Context

The user has provided a complete HTML design spec for the Work Tracking app. The spec defines a specific visual language:

- **Brand palette:** navy `#1E3A5F` (structure) + blue `#2D7DD2` (action)
- **Base size:** 13px dense UI ("dense, not cramped")
- **No shadows** — border-based surface separation only
- **Border radius scale:** 3px (tags) → 4px (buttons) → 5px (cards) → 6px (panels)
- **Tag taxonomy:** fixed 5-color system (purple/orange/green/blue/stone)
- **French-first copy:** sentence case, `Ko/Mo`, `12 avr. 2026`

Current frontend uses Tailwind v4, brand-500 = `#465fff` (blue-purple), Outfit font, dark mode via `html.dark`, 204 components across 72 pages.

**Approach:** Token-swap + component reskin — no page rewrites. Preserve all functionality, permissions logic, and backend wiring.

---

## Step 1 — Design Token Replacement (`resources/css/app.css`)

Replace the entire `@theme { }` block. Keep: Tailwind import, `@custom-variant dark`, `@utility` classes, all third-party overrides (ApexCharts, FullCalendar, Flatpickr, Swiper).

### Color tokens

```css
/* Brand / structure — map to existing brand-* names so Tailwind classes still work */
--color-brand-500: #2D7DD2;      /* DS blue (was #465fff) */
--color-brand-600: #2469b8;      /* hover */
--color-brand-700: #1a5a9e;
--color-brand-50:  #EBF2FB;      /* blue-lt tint */
--color-brand-100: #d4e6f7;
--color-brand-900: #1E3A5F;      /* navy = deepest brand */
--color-brand-950: #152b47;

/* New navy direct token */
--color-navy: #1E3A5F;
--color-blue: #2D7DD2;
--color-blue-lt: #EBF2FB;

/* Neutrals — adjust key stops to DS palette */
--color-gray-900: #1E3A5F;       /* headings → navy (was #101828) */
--color-gray-700: #5A6B7E;       /* body text */
--color-gray-500: #8A9BB0;       /* muted */
--color-gray-200: #D0DAE8;       /* border */
--color-gray-100: #F4F6F9;       /* surface-alt (bg) */

/* Semantic accents */
--color-success-500: #1A7A4A;
--color-success-50:  #EAFAF1;
--color-error-500:   #C0392B;
--color-error-50:    #FDEDEC;
--color-warning-500: #D68000;
--color-warning-50:  #FFF3CD;
--color-purple-500:  #534AB7;
--color-purple-50:   #EEEDFE;
```

### Typography

```css
/* DS base is 13px */
--text-theme-sm: 13px;           /* was 14px */
--text-theme-xs: 12px;           /* UI default */
/* title-* scales unchanged */
```

Add to `@layer base`:
```css
html { font-size: 13px; }
```

### Shadows — DS uses none

```css
--shadow-theme-xs: none;
--shadow-theme-sm: none;
--shadow-theme-md: none;
--shadow-theme-lg: none;
--shadow-theme-xl: none;
```

Components needing depth use `border border-gray-200` instead.

### Border radius tokens

```css
--radius-1: 3px;   /* tags, badges, small icon buttons */
--radius-2: 4px;   /* buttons, inputs */
--radius-3: 5px;   /* cards, containers */
--radius-4: 6px;   /* panels, modals */
```

---

## Step 2 — Core UI Components

### `resources/js/components/ui/Button.vue`

New variants:
```js
primary: 'h-[30px] px-3 text-[12px] rounded-[4px] bg-[#1E3A5F] text-white border border-[#1E3A5F] hover:brightness-105',
accent:  'h-[30px] px-3 text-[12px] rounded-[4px] bg-brand-500 text-white border border-brand-500 hover:brightness-105',
outline: 'h-[30px] px-3 text-[12px] rounded-[4px] bg-white text-gray-700 border border-gray-200 hover:border-brand-500 hover:text-brand-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-700',
ghost:   'h-[30px] px-3 text-[12px] rounded-[4px] bg-transparent border-transparent text-gray-700 hover:bg-gray-100 dark:text-gray-300',
danger:  'h-[30px] px-3 text-[12px] rounded-[4px] bg-white text-red-600 border border-red-600 hover:bg-red-50',
add:     'h-auto px-[10px] py-[3px] text-[12px] rounded-[4px] text-brand-500 border border-brand-500 hover:bg-brand-50',
```

Small size (sm):
```js
sm: 'h-[24px] px-[9px] text-[11px] rounded-[3px]',
```

Update TypeScript interface: add `accent | ghost | danger | add` to variant union.

### `resources/js/components/ui/Badge.vue`

Font: always `text-[10px]`. Padding: `px-[6px] py-[2px]`. Radius: `rounded-[3px]`. Border: always present.

Color map:
```js
primary: 'bg-brand-50 text-brand-500 border border-brand-200',
success: 'bg-[#EAFAF1] text-[#1A7A4A] border border-[#5DCAA5]',
error:   'bg-[#FDEDEC] text-[#C0392B] border border-[#E6B0AA]',
warning: 'bg-[#FFF3CD] text-[#D68000] border border-[#F0C060]',
purple:  'bg-[#EEEDFE] text-[#534AB7] border border-[#AFA9EC]',
stone:   'bg-[#F1EFE8] text-[#5F5E5A] border border-[#B4B2A9]',
info:    'bg-blue-light-50 text-blue-light-500 border border-blue-light-200',
dark:    'bg-gray-100 text-gray-700 border border-gray-200',
```

### `resources/js/components/ui/Card.vue`

Remove all shadow utilities → `shadow-none`. Add `border border-gray-200 dark:border-gray-700`. Radius → `rounded-[5px]`. Background → `bg-white dark:bg-gray-800`.

### `resources/js/components/ui/Alert.vue`

Use DS semantic color pairs (bg-lt + text-dark + border-mid). Radius → `rounded-[5px]`. Remove shadows.

### `resources/js/components/ui/Modal.vue`

Backdrop: `bg-black/40`. Panel: `rounded-[6px] border border-gray-200 shadow-none`.

---

## Step 3 — Layout Shell

### `resources/js/components/layout/AppSidebar.vue`

- Logo/workspace badge: `bg-[#1E3A5F]` (navy) replacing gradient or brand-500
- Border: `border-r border-gray-200 dark:border-gray-700`
- Active menu item: uses `bg-brand-50 text-brand-500` — already correct once token lands
- Workspace selector gradient → solid navy badge

### `resources/js/components/layout/AppHeader.vue`

- Remove any gradient backgrounds → `bg-white dark:bg-gray-900`
- Border bottom: `border-b border-gray-200 dark:border-gray-700`
- Search bar: gray bg with left icon (matches DS search spec)

### `resources/js/components/layout/MainLayout.vue`

Replace hardcoded hex values (`#f8fafc`, `#dc2626`, `#fef2f2`) with Tailwind utilities.

### `resources/js/components/layout/AdminLayout.vue`

Content area: `bg-gray-100 dark:bg-gray-900` (DS `--bg` = `#F4F6F9`).

---

## Step 4 — Domain Component Color Pass

| Component | Change |
|-----------|--------|
| `pages/Dashboard.vue` | Replace gradient stat cards with flat white cards, border-based |
| `pages/dashboard/TaskCard.vue` | Update left-border priority colors to DS palette |
| `components/taches/StatusBadge.vue` | Map statut colors to DS semantic tokens |
| `components/taches/PriorityBadge.vue` | Map priority colors to DS semantic tokens |

**Do NOT touch:** ApexCharts, FullCalendar, Flatpickr, jsvectormap — these have their own theme systems with existing CSS overrides in `app.css`.

---

## Step 5 — Dark Mode

Keep existing dark mode system intact. The token changes in Step 1 automatically update dark-mode color stops via existing `dark:` Tailwind variants. Verify `ThemeProvider.vue` is unchanged.

---

## Execution Order

1. Create branch `feature/design-system-v1` from `jonas`
2. `resources/css/app.css` — token block replacement
3. `npm run build` — check visual baseline in browser
4. `Button.vue` → `Badge.vue` → `Card.vue` → `Alert.vue` → `Modal.vue`
5. `AppSidebar.vue` → `AppHeader.vue` → `MainLayout.vue`
6. `Dashboard.vue` → `TaskCard.vue` → `StatusBadge.vue` → `PriorityBadge.vue`
7. `npm run build` + browser check (Dashboard, Projets, Taches, Documents, Workspaces, dark mode)
8. `php artisan test --compact` — confirm all pass (no backend changes)
9. `vendor/bin/pint --dirty --format agent` (PHP only)
10. Commit + push to `origin` + `client`

---

## Critical Files

| File | Change |
|------|--------|
| `resources/css/app.css` | Replace `@theme` token block + add base font-size |
| `resources/js/components/ui/Button.vue` | New variants + DS sizes |
| `resources/js/components/ui/Badge.vue` | DS tag colors, 10px font, border |
| `resources/js/components/ui/Card.vue` | No shadow, border-based, radius-3 |
| `resources/js/components/ui/Alert.vue` | DS semantic color pairs |
| `resources/js/components/ui/Modal.vue` | Lighter backdrop, no shadow, radius-4 |
| `resources/js/components/layout/AppSidebar.vue` | Navy logo badge, border tweak |
| `resources/js/components/layout/AppHeader.vue` | Remove gradients, DS border |
| `resources/js/components/layout/MainLayout.vue` | Remove hardcoded hex |
| `resources/js/components/layout/AdminLayout.vue` | DS bg color |
| `resources/js/pages/Dashboard.vue` | Flat cards, no gradient |
| `resources/js/pages/dashboard/TaskCard.vue` | DS priority border colors |
| `resources/js/components/taches/StatusBadge.vue` | DS semantic colors |
| `resources/js/components/taches/PriorityBadge.vue` | DS semantic colors |

## Unchanged

- All composables
- All router routes + guards
- All backend PHP files
- `ThemeProvider.vue`
- `app.js` entry point
- All third-party chart/calendar/map CSS overrides in `app.css`
