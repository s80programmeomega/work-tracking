# 🔐 Système d'Authentification - Work Tracking

## Vue d'ensemble

Système d'authentification complet intégré avec Laravel 10 + Vue.js 3, utilisant :
- **Backend**: Laravel Sanctum + Spatie Permissions + Spatie Activity Log
- **Frontend**: Vue 3 + Pinia + Vue Router
- **UI**: TailwindCSS + AdminLTE3

---

## 🚀 Installation Rapide

### Option 1: Script automatique
```bash
./setup.sh
```

### Option 2: Installation manuelle

1. **Démarrer MySQL et créer la base de données**
```bash
mysql -u root -p
CREATE DATABASE Work_Tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

2. **Exécuter les migrations**
```bash
php artisan migrate
```

3. **Initialiser les rôles et permissions**
```bash
php artisan db:seed --class=RolePermissionSeeder
```

4. **Démarrer les serveurs**
```bash
# Terminal 1
php artisan serve

# Terminal 2
npm run dev
```

5. **Accéder à l'application**
```
http://localhost:5173
```

---

## 👥 Utilisateurs de Test

| Email | Mot de passe | Rôle |
|-------|--------------|------|
| admin@worktracking.com | password | Super Admin |
| manager@worktracking.com | password | Manager |
| cadre@worktracking.com | password | Cadre |

---

## 📱 Fonctionnalités Implémentées

### ✅ Authentification
- [x] Inscription avec validation
- [x] Connexion avec "Remember Me"
- [x] Déconnexion
- [x] Mot de passe oublié
- [x] Réinitialisation de mot de passe
- [x] Vérification d'email
- [x] Support 2FA (backend prêt)

### ✅ Autorisations
- [x] Système de rôles (6 rôles configurés)
- [x] Système de permissions granulaires
- [x] Guards de navigation (Vue Router)
- [x] Middleware backend (Spatie)

### ✅ Sécurité
- [x] Tokens JWT avec expiration (7 jours)
- [x] Refresh token automatique
- [x] CSRF Protection
- [x] XSS Prevention
- [x] Validation des entrées
- [x] Rate limiting (prêt à configurer)
- [x] Activity logging

### ✅ UX/UI
- [x] Gestion d'état de chargement
- [x] Affichage des erreurs de validation
- [x] Messages de succès/erreur
- [x] Redirection intelligente après login
- [x] Design responsive (TailwindCSS)
- [x] Mode clair/sombre (hérité d'AdminLTE)

---

## 🏗️ Architecture

### Backend

```
app/
├── Enums/Role.php                    # Énumération des rôles
├── Services/AuthService.php          # Logique métier
├── Http/
│   ├── Controllers/Api/
│   │   └── AuthController.php        # Endpoints API
│   ├── Requests/Auth/
│   │   ├── LoginRequest.php          # Validation login
│   │   └── RegisterRequest.php       # Validation register
│   └── Resources/
│       └── UserResource.php          # Transformation données
└── Models/User.php                   # Modèle avec traits Spatie
```

### Frontend

```
resources/js/
├── api/
│   ├── axios.js                      # Configuration Axios + Intercepteurs
│   └── auth.js                       # API d'authentification
├── stores/
│   └── authStore.js                  # Store Pinia (état global)
├── composables/
│   └── useAuth.js                    # Composable réutilisable
├── pages/Auth/
│   ├── Signin.vue                    # Page de connexion
│   ├── Signup.vue                    # Page d'inscription
│   ├── ForgotPassword.vue            # Mot de passe oublié
│   └── ResetPassword.vue             # Réinitialisation
└── router/index.ts                   # Guards de navigation
```

---

## 🔑 Rôles et Permissions

### Hiérarchie des Rôles

```
super_admin (*)                       # Toutes les permissions
    │
    ├── manager
    │   ├── Projets: CRUD
    │   ├── Activités: CRUD
    │   ├── Tâches: CRUD
    │   └── Users: view, assign
    │
    ├── responsable_n1
    │   ├── Activités: view, update
    │   └── Tâches: CRUD, validate
    │
    ├── responsable_n2
    │   └── Tâches: view, update, validate
    │
    ├── cadre
    │   └── Tâches: view, update, comment
    │
    └── stagiaire
        └── Tâches: view, comment
```

### Utilisation dans le Code

**Backend (Laravel):**
```php
// Dans un controller
if ($user->can('taches.create')) {
    // Créer une tâche
}

// Middleware dans routes
Route::middleware(['permission:projets.delete'])->delete('/projets/{id}');
```

**Frontend (Vue):**
```javascript
// Dans un composant
import { useAuth } from '@/composables/useAuth'

const { hasPermission, hasRole } = useAuth()

if (hasPermission('taches.create')) {
    // Afficher le bouton "Créer"
}

if (hasRole('manager')) {
    // Afficher les options de gestion
}
```

**Router Guards:**
```javascript
{
    path: '/admin',
    meta: {
        requiresAuth: true,
        roles: ['super_admin', 'manager']
    }
}
```

---

## 🔗 API Endpoints

### Publiques

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/register` | Inscription |
| POST | `/api/auth/login` | Connexion |

### Protégées (Bearer Token requis)

| Méthode | Endpoint | Description |
|---------|----------|-------------|
| POST | `/api/auth/logout` | Déconnexion |
| GET | `/api/auth/me` | Infos utilisateur |
| POST | `/api/auth/refresh` | Rafraîchir le token |
| POST | `/api/auth/verify-email` | Vérifier l'email |

### Exemple de Requête

**Login:**
```bash
curl -X POST http://localhost:8000/api/auth/login \
  -H "Content-Type: application/json" \
  -d '{
    "email": "admin@worktracking.com",
    "password": "password",
    "remember": true
  }'
```

**Réponse:**
```json
{
  "message": "Login successful",
  "data": {
    "user": {
      "id": 1,
      "name": "Super Admin",
      "email": "admin@worktracking.com",
      "roles": [...]
    },
    "token": "1|abc123...",
    "token_type": "Bearer",
    "expires_at": "2025-10-11T00:00:00.000000Z"
  }
}
```

---

## 🧪 Tests

### Backend
```bash
php artisan test
```

### Frontend
```bash
npm run test
```

---

## 📚 Documentation Complète

- [INSTALLATION.md](./INSTALLATION.md) - Guide d'installation détaillé
- [authentication_implementation.md](./authentication_implementation.md) - Spécifications complètes
- [modules_plan.md](./modules_plan.md) - Plan des modules
- [project_overview.md](./project_overview.md) - Vue d'ensemble du projet

---

## 🛠️ Commandes Utiles

```bash
# Créer un nouvel utilisateur
php artisan tinker
>>> $user = User::create(['name' => 'Test', 'email' => 'test@example.com', 'password' => Hash::make('password')]);
>>> $user->assignRole('cadre');

# Lister les permissions d'un rôle
php artisan tinker
>>> Role::findByName('manager')->permissions->pluck('name');

# Vider le cache des permissions
php artisan cache:forget spatie.permission.cache

# Rafraîchir les permissions
php artisan permission:cache-reset
```

---

## 🔄 Workflow de Développement

1. **Créer une nouvelle permission**
```php
Permission::create(['name' => 'nouvelle.permission']);
```

2. **Assigner à un rôle**
```php
$role = Role::findByName('manager');
$role->givePermissionTo('nouvelle.permission');
```

3. **Utiliser dans le code**
```php
// Controller
if (auth()->user()->can('nouvelle.permission')) {
    // Code
}
```

4. **Frontend**
```javascript
// Composant Vue
if (hasPermission('nouvelle.permission')) {
    // Afficher l'UI
}
```

---

## 🚨 Sécurité en Production

- [ ] Changer tous les mots de passe par défaut
- [ ] Configurer HTTPS
- [ ] Configurer un vrai serveur SMTP
- [ ] Activer le rate limiting
- [ ] Configurer les CORS correctement
- [ ] Utiliser Redis pour les sessions
- [ ] Mettre en place des backups automatiques
- [ ] Activer les logs d'activité
- [ ] Configurer la 2FA pour les admins
- [ ] Audit de sécurité régulier

---

## 🤝 Contribution

Ce système d'authentification est prêt pour l'extension. Vous pouvez :
- Ajouter de nouveaux rôles dans `app/Enums/Role.php`
- Créer de nouvelles permissions dans le seeder
- Ajouter des fonctionnalités (OAuth, SSO, etc.)

---

## 📞 Support

Pour toute question ou problème :
1. Consultez [INSTALLATION.md](./INSTALLATION.md)
2. Vérifiez les logs : `storage/logs/laravel.log`
3. Consultez la documentation de dépannage

---

**Status**: ✅ Système d'authentification complet et fonctionnel
