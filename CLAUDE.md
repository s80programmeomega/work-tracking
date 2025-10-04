# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

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
