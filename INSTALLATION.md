# Installation et Configuration - Système d'Authentification

## ✅ Étapes Complétées

### Backend
- ✅ Packages Spatie installés (laravel-permission, laravel-activitylog)
- ✅ Configurations publiées
- ✅ Migration pour les champs d'authentification créée
- ✅ Seeder pour rôles et permissions créé
- ✅ Models, Services, Controllers, Routes configurés

### Frontend
- ✅ Pinia installé
- ✅ Stores d'authentification créés
- ✅ API layer configurée (Axios avec intercepteurs)
- ✅ Pages d'authentification intégrées (Login, Register, ForgotPassword, ResetPassword)
- ✅ Router guards configurés
- ✅ Variable d'environnement VITE_API_URL ajoutée

---

## 🔧 Étapes Restantes

### 1. Démarrer MySQL

Vous devez démarrer votre serveur MySQL. Voici les commandes selon votre système :

**Linux (Ubuntu/Debian):**
```bash
sudo service mysql start
# ou
sudo systemctl start mysql
```

**macOS:**
```bash
brew services start mysql
# ou
mysql.server start
```

**Windows:**
```bash
net start MySQL
# ou via XAMPP/WAMP/MAMP
```

### 2. Créer la base de données

Connectez-vous à MySQL et créez la base de données :

```bash
mysql -u root -p
```

Puis dans MySQL :
```sql
CREATE DATABASE Work_Tracking CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
EXIT;
```

### 3. Exécuter les migrations

```bash
php artisan migrate
```

### 4. Exécuter le seeder

```bash
php artisan db:seed --class=RolePermissionSeeder
```

Cela créera :
- Tous les rôles (super_admin, manager, responsable_n1, responsable_n2, cadre, stagiaire)
- Toutes les permissions
- 3 utilisateurs de test :
  - **admin@worktracking.com** (password: password) - Super Admin
  - **manager@worktracking.com** (password: password) - Manager
  - **cadre@worktracking.com** (password: password) - Cadre

### 5. Démarrer les serveurs

**Terminal 1 - Backend Laravel:**
```bash
php artisan serve
```
Votre API sera disponible sur : http://localhost:8000

**Terminal 2 - Frontend Vite:**
```bash
npm run dev
```
Votre frontend sera disponible sur : http://localhost:5173

---

## 🧪 Tester l'Application

1. Ouvrez votre navigateur sur http://localhost:5173
2. Vous serez redirigé vers `/signin`
3. Connectez-vous avec :
   - Email : `admin@worktracking.com`
   - Password : `password`

---

## 📁 Structure des Fichiers Créés

### Backend
```
app/
├── Enums/
│   └── Role.php
├── Services/
│   └── AuthService.php
├── Http/
│   ├── Controllers/Api/
│   │   └── AuthController.php
│   ├── Requests/Auth/
│   │   ├── LoginRequest.php
│   │   └── RegisterRequest.php
│   └── Resources/
│       └── UserResource.php
└── Models/
    └── User.php (modifié)

routes/
└── api.php (modifié)

database/
├── migrations/
│   └── 2025_10_04_153557_add_auth_fields_to_users_table.php
└── seeders/
    └── RolePermissionSeeder.php
```

### Frontend
```
resources/js/
├── api/
│   ├── axios.js
│   └── auth.js
├── stores/
│   └── authStore.js
├── composables/
│   └── useAuth.js
├── pages/Auth/
│   ├── Signin.vue (modifié)
│   ├── Signup.vue (modifié)
│   ├── ForgotPassword.vue
│   └── ResetPassword.vue
├── router/
│   └── index.ts (modifié)
└── app.js (modifié - Pinia ajouté)
```

---

## 🔐 Endpoints API Disponibles

### Routes Publiques
- `POST /api/auth/register` - Inscription
- `POST /api/auth/login` - Connexion

### Routes Protégées (nécessite token Bearer)
- `POST /api/auth/logout` - Déconnexion
- `GET /api/auth/me` - Informations utilisateur
- `POST /api/auth/refresh` - Rafraîchir le token
- `POST /api/auth/verify-email` - Vérifier l'email
- `POST /api/auth/2fa/enable` - Activer 2FA
- `POST /api/auth/2fa/confirm` - Confirmer 2FA
- `DELETE /api/auth/2fa/disable` - Désactiver 2FA

---

## 🎯 Rôles et Permissions

### Rôles Disponibles
1. **super_admin** - Accès complet
2. **manager** - Gestion projets, activités, tâches, utilisateurs
3. **responsable_n1** - Gestion activités et tâches
4. **responsable_n2** - Supervision tâches
5. **cadre** - Utilisateur standard
6. **stagiaire** - Accès limité

### Permissions par Catégorie
- **Projets**: view, create, update, delete
- **Activités**: view, create, update, delete
- **Tâches**: view, create, update, delete, validate, comment
- **Users**: view, create, update, delete, assign
- **Reports**: view, create

---

## 🚨 Dépannage

### Erreur "Connection refused" lors des migrations
→ MySQL n'est pas démarré. Voir section 1.

### Erreur 401 lors du login
→ Vérifiez que les routes API sont bien configurées et que le serveur Laravel est démarré.

### Token expiré automatiquement
→ Les tokens expirent après 7 jours. Utilisez l'endpoint `/auth/refresh` pour renouveler.

### CORS errors
→ Vérifiez que `config/cors.php` inclut votre URL frontend dans `allowed_origins`.

---

## 📚 Prochaines Étapes

Une fois l'authentification fonctionnelle, vous pouvez :
1. Créer les modules Projet, Activité, Tâche
2. Implémenter les permissions sur les routes
3. Ajouter la gestion des fichiers
4. Configurer les notifications
5. Ajouter le système de commentaires

---

## 💡 Conseils

- Changez les mots de passe par défaut en production
- Configurez un vrai serveur SMTP pour les emails
- Activez HTTPS en production
- Configurez Redis pour les sessions et le cache
- Mettez en place une stratégie de backup de la base de données


🧠 4️⃣ Résumé clair
Contexte	allowed_origins	supports_credentials	Explication
Local	['http://localhost:8000', 'http://127.0.0.1:8000']	true	Permet à Vue (localhost) d’accéder à Laravel (127.0.0.1)
Production	['https://app.worktracking.com']	true	Permet uniquement au vrai domaine front d’accéder à l’API
Jamais faire	['*'] avec supports_credentials: true	❌	Bloqué par le navigateur, dangereux en sécurité