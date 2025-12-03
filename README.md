# 🚀 Work Tracking - Guide Développeur

## 📋 Vue d'ensemble

**Work Tracking** est une application web de suivi des travaux et projets avec une hiérarchie **workspace -> Projet → Activité → Tâches**. L'objectif est de centraliser la gestion, faciliter la collaboration et permettre un suivi précis de l'avancement des activités.

### Stack Technique
- **Backend** : Laravel 10 + PostgreSQL/MySQL
- **Frontend** : Vue.js 3 + AdminLTE3 + TailwindCSS
- **Authentification** : Laravel Fortify + Sanctum
- **Notifications** : Laravel Notifications + Mail

## 🏗️ Architecture du Projet

```
work-tracking/
├── app/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── Auth/
│   │   │   ├── ProjetController.php
│   │   │   ├── ActiviteController.php
│   │   │   ├── TacheController.php
│   │   │   ├── UserController.php
│   │   │   ├── NotificationController.php
│   │   │   └── ReportingController.php
│   │   ├── Middleware/
│   │   │   └── RoleMiddleware.php
│   │   └── Requests/
│   │       ├── ProjetRequest.php
│   │       ├── ActiviteRequest.php
│   │       └── TacheRequest.php
│   ├── Models/
│   │   ├── User.php
│   │   ├── Projet.php
│   │   ├── Activite.php
│   │   ├── Tache.php
│   │   ├── Commentaire.php
│   │   ├── Document.php
│   │   └── Notification.php
│   ├── Services/
│   │   ├── ProjetService.php
│   │   ├── TacheService.php
│   │   ├── NotificationService.php
│   │   └── ReportingService.php
│   └── Enums/
│       ├── StatusTache.php
│       ├── Priorite.php
│       └── Role.php
├── database/
│   ├── migrations/
│   │   ├── 2024_01_01_create_users_table.php
│   │   ├── 2024_01_02_create_projets_table.php
│   │   ├── 2024_01_03_create_activites_table.php
│   │   ├── 2024_01_04_create_taches_table.php
│   │   ├── 2024_01_05_create_commentaires_table.php
│   │   ├── 2024_01_06_create_documents_table.php
│   │   └── 2024_01_07_create_notifications_table.php
│   ├── seeders/
│   │   ├── UserSeeder.php
│   │   ├── ProjetSeeder.php
│   │   └── RoleSeeder.php
│   └── factories/
├── resources/
│   ├── js/
│   │   ├── app.js
│   │   ├── components/
│   │   │   ├── auth/
│   │   │   │   ├── Login.vue
│   │   │   │   └── Register.vue
│   │   │   ├── dashboard/
│   │   │   │   ├── Dashboard.vue
│   │   │   │   ├── StatCard.vue
│   │   │   │   └── ChartComponent.vue
│   │   │   ├── projet/
│   │   │   │   ├── ProjetIndex.vue
│   │   │   │   ├── ProjetCreate.vue
│   │   │   │   ├── ProjetEdit.vue
│   │   │   │   └── ProjetShow.vue
│   │   │   ├── activite/
│   │   │   │   ├── ActiviteIndex.vue
│   │   │   │   ├── ActiviteCreate.vue
│   │   │   │   └── ActiviteEdit.vue
│   │   │   ├── tache/
│   │   │   │   ├── TacheIndex.vue
│   │   │   │   ├── TacheCreate.vue
│   │   │   │   ├── TacheEdit.vue
│   │   │   │   ├── TacheKanban.vue
│   │   │   │   └── TacheCalendar.vue
│   │   │   ├── shared/
│   │   │   │   ├── Navbar.vue
│   │   │   │   ├── Sidebar.vue
│   │   │   │   ├── Modal.vue
│   │   │   │   ├── FileUpload.vue
│   │   │   │   └── CommentSection.vue
│   │   │   └── reporting/
│   │   │       ├── ReportDashboard.vue
│   │   │       └── ReportExport.vue
│   │   ├── stores/
│   │   │   ├── auth.js
│   │   │   ├── projet.js
│   │   │   ├── tache.js
│   │   │   └── notification.js
│   │   └── utils/
│   │       ├── api.js
│   │       ├── helpers.js
│   │       └── constants.js
│   └── views/
│       └── app.blade.php
├── routes/
│   ├── web.php
│   └── api.php
└── tests/
    ├── Feature/
    │   ├── ProjetTest.php
    │   ├── ActiviteTest.php
    │   └── TacheTest.php
    └── Unit/
        └── UserTest.php
```

## 🗄️ Modèles de Données

### Relations Principales
```php
// Projet (1) → Activités (n) → Tâches (n)
// Utilisateur (n) ↔ Tâches (n) [Many-to-Many]
// Tâche (1) → Commentaires (n)
// Tâche (1) → Documents (n)
```

### Migrations Prioritaires

#### 1. Users Table
```php
Schema::create('users', function (Blueprint $table) {
    $table->id();
    $table->string('nom');
    $table->string('email')->unique();
    $table->string('password');
    $table->enum('role', ['super_admin', 'manager', 'responsable_n1', 'responsable_n2', 'cadre', 'stagiaire']);
    $table->string('fonction')->nullable();
    $table->string('avatar')->nullable();
    $table->timestamps();
});
```

#### 2. Projets Table
```php
Schema::create('projets', function (Blueprint $table) {
    $table->id();
    $table->string('nom');
    $table->text('description');
    $table->date('date_debut');
    $table->date('date_fin');
    $table->foreignId('responsable_id')->constrained('users');
    $table->timestamps();
});
```

#### 3. Activites Table
```php
Schema::create('activites', function (Blueprint $table) {
    $table->id();
    $table->foreignId('projet_id')->constrained()->onDelete('cascade');
    $table->string('nom');
    $table->text('description');
    $table->foreignId('responsable_id')->constrained('users');
    $table->date('date_debut');
    $table->date('date_fin');
    $table->timestamps();
});
```

#### 4. Taches Table
```php
Schema::create('taches', function (Blueprint $table) {
    $table->id();
    $table->foreignId('activite_id')->constrained()->onDelete('cascade');
    $table->string('titre');
    $table->text('description');
    $table->text('objectif')->nullable();
    $table->text('indicateurs_resultats')->nullable();
    $table->enum('statut', ['a_faire', 'en_cours', 'termine'])->default('a_faire');
    $table->enum('priorite', ['faible', 'moyenne', 'elevee', 'critique'])->default('moyenne');
    $table->date('echeance');
    $table->integer('taux_realisation')->default(0); // 0-100%
    $table->boolean('validation_superieur')->default(false);
    $table->boolean('verrou_reevaluation')->default(false);
    $table->text('commentaire')->nullable();
    $table->timestamps();
});
```

#### 5. Tache_User Pivot Table
```php
Schema::create('tache_user', function (Blueprint $table) {
    $table->id();
    $table->foreignId('tache_id')->constrained()->onDelete('cascade');
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->boolean('is_responsable')->default(false);
    $table->timestamps();
});
```
...

## 📦 Installation & Configuration

### Prérequis
- PHP 8.1+
- Composer
- Node.js 16+
- PostgreSQL ou MySQL

### Installation
```bash
# 1. Cloner le repository
git clone https://github.com/votre-org/work-tracking.git
cd work-tracking

# 2. Installer les dépendances PHP
composer install

# 3. Installer les dépendances Node.js
npm install

# 4. Configurer l'environnement
cp .env.example .env
php artisan key:generate

# 5. Configuration base de données (.env)
DB_CONNECTION=mysql  # ou