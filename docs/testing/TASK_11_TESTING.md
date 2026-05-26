# Task 11 — Manual Testing Guide

Task creation UX: 4-step wizard, intervenant picker, assignee/resource notifications.

---

## Prerequisites

- A workspace with at least one activité
- Members: owner, one manager, one cadre, one collaborateur
- Mail configured (or check `storage/logs/laravel.log` for mail output)

---

## TC-1 — Wizard opens from activité detail page

**Steps:**
1. Log in as owner or manager
2. Navigate to `/activites/{id}`
3. Click "Nouvelle tâche" (or equivalent create button)

**Expected:**
- A 4-step modal wizard opens (step 1: Informations)
- Step indicator shows "1 / 4"

---

## TC-2 — Step 1: required fields validation

**Steps:**
1. Open the wizard
2. Click "Suivant" without filling any fields

**Expected:**
- Validation error shown: titre is required
- Wizard does not advance to step 2

---

## TC-3 — Step 2: intervenant picker searches workspace members

**Steps:**
1. Fill step 1 (titre, statut, priorité)
2. Advance to step 2 (Assignation)
3. Type a partial name in the picker search field

**Expected:**
- Matching workspace members appear as selectable options
- Responsable chip is locked (cannot be removed)
- Multiple intervenants can be selected (appear as chips)

---

## TC-4 — Full wizard submission creates task

**Steps:**
1. Complete all 4 steps (Informations → Assignation → Ressources → Validation)
2. Click "Créer la tâche" on step 4

**Expected:**
- `POST /api/activites/{id}/taches` returns 201
- New task appears in the activité's task list without page reload
- Wizard modal closes

---

## TC-5 — Assignee notification sent on task creation

**Steps:**
1. Create a task (step 4) and assign at least one intervenant
2. Log in as that intervenant

**Expected:**
- Intervenant has a new in-app notification of type `tache_assigned`
- If mail is configured: email sent with task title, activité name, CTA link

---

## TC-6 — Resource notification sent when documents/links added

**Steps:**
1. On step 3 (Ressources), attach a document or link
2. Complete and submit the wizard

**Expected:**
- In-app notification `tache_resources` sent to intervenants on the task

---

## TC-7 — Edit task still uses TacheForm (not wizard)

**Steps:**
1. Click "Modifier" on an existing task card/row

**Expected:**
- `TacheForm` modal opens (not the wizard)
- Fields are pre-filled with existing values

---

## TC-8 — Wizard blocked for collaborateur (no create permission)

**Steps:**
1. Log in as collaborateur
2. Navigate to `/activites/{id}`

**Expected:**
- "Nouvelle tâche" button is hidden or disabled
- `POST /api/activites/{id}/taches` returns 403

---

## Automated Tests

| Suite | File | Count |
|---|---|---|
| PHPUnit Feature | `tests/Feature/Task11/WizardValidationTest.php` | 5 |
| Dusk Browser | `tests/Browser/Tasks/WizardTest.php` | 3 |

Run with:
```bash
php artisan test --compact tests/Feature/Task11/
php artisan dusk tests/Browser/Tasks/WizardTest.php
```
