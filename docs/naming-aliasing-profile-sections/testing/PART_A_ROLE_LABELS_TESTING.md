# Part A — Role Label Centralization: Manual Testing Guide

**Branch:** `feature/role-label-centralization`
**Date:** 2026-07-01

---

## What changed

- New `app/Permissions/RoleLabel.php` — single PHP source of truth for all 10 role display labels
- New `lang/fr/roles.php` + `lang/en/roles.php` — bilingual label files
- `app/Enums/Role.php::label()` — now delegates to `RoleLabel::label()`
- `resources/js/permissions/Permission.js` — `RoleLabels` extended to all 10 roles, new `getRoleLabel(role, locale)` function added, `task_responsable` added to `RoleHierarchy`
- **6 PHP Notification classes** — broken private `getRoleLabel()` removed, now call `RoleLabel::label()` correctly
- **18 Vue/JS files** — local `getRoleLabel()` implementations removed, now all import from `@/permissions/Permission`
- **5 Vue files** — hardcoded `<option>` label text now uses `{{ getRoleLabel('...') }}`

---

## Quick sanity checks (Tinker)

```bash
php artisan tinker
```

```php
// French labels
app()->setLocale('fr');
\App\Permissions\RoleLabel::label('manager');    // → "Manager"
\App\Permissions\RoleLabel::label('cadre');      // → "Cadre"
\App\Permissions\RoleLabel::label('observateur'); // → "Observateur"
\App\Permissions\RoleLabel::label('task_responsable'); // → "Responsable de tâche"
\App\Permissions\RoleLabel::label('unknown');    // → "unknown" (safe fallback)

// English labels
app()->setLocale('en');
\App\Permissions\RoleLabel::label('cadre');      // → "Team Lead"
\App\Permissions\RoleLabel::label('super_admin'); // → "Super Admin"

// Role enum delegation
\App\Enums\Role::DIRECTEUR->label();  // → "Director" (en) / "Directeur" (fr)

// Full map
\App\Permissions\RoleLabel::all();
// Should return all 10 role-key => label pairs for the active locale
```

---

## UI checks (browser, French locale)

### 1. Workspace member management

1. Open a workspace → **Paramètres / Membres**
2. Invite a new member → the role dropdown options should read: "Propriétaire", "Manager", "Cadre", "Collaborateur", "Stagiaire", "Observateur"
3. View an existing member's role badge → should show the correct label (e.g. "Manager", not "Gestionnaire")

### 2. Projet member management

1. Open a project → **Membres** tab
2. Existing members in the list → role shown should use the label (e.g. "Cadre", not "cadre" raw string)
3. Open "Modifier les permissions" modal → role dropdown shows correct labels

### 3. Activité member management

1. Open an activité → **Membres** section
2. "Ajouter un membre" modal → role dropdown options: "Cadre", "Collaborateur", "Stagiaire", "Observateur"
3. "Modifier permissions" modal → role change dropdown same labels

### 4. Notifications (live bug fix verification)

1. Invite a user to a workspace with role "manager"
2. Check the notification email / in-app notification → role should appear as **"Manager"**, not "Gestionnaire"
3. Accept a project invitation → role shown on the acceptance page should be correct

### 5. Accept invitation pages

1. Open `/accept-invitation/{token}` (workspace invite) → role shown on the card should be the full label
2. Open `/invitations/projet/{token}` (project invite) → same check

### 6. Admin panels

1. Go to **Admin → Utilisateurs** → filter dropdown roles show correct labels
2. "Créer un utilisateur" modal → role dropdown shows correct labels

---

## Rename smoke test

To confirm the whole architecture works end-to-end, temporarily edit `lang/fr/roles.php`:
```php
'cadre' => 'Chef de projet',  // changed
```
Then reload the app. Every place that shows the "Cadre" role (member lists, dropdowns, notifications) should now say "Chef de projet" — with **zero other code changes**. Revert the test change afterward.

---

## Verification grep (must return zero results)

```bash
# No local getRoleLabel definitions remain in Vue/JS
grep -rn "^const getRoleLabel\|^  const getRoleLabel" resources/js --include="*.vue" --include="*.js"

# No wrong vocabulary in notifications
grep -rn "Gestionnaire\|'admin'\s*=>" app/Notifications --include="*.php"

# No ROLE_LABELS constant (replaced by shared registry)
grep -rn "const ROLE_LABELS\s*=" resources/js --include="*.vue" --include="*.js"
```

All three should return **no output**.
