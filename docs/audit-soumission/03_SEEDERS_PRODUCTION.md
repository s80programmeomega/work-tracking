# Seeders — Ordre et usage en production

> **Objet :** Documentation de chaque seeder, son rôle, son caractère obligatoire ou optionnel en production, et l'ordre d'exécution.
> **Établi le :** 2026-06-11

---

## Commande de référence

```bash
# Migration complète + seeding (installation initiale ou reset de démo)
php artisan migrate:fresh --seed

# Seeding seul sans re-migrer (mise à jour en production)
php artisan db:seed
```

---

## Ordre d'exécution (DatabaseSeeder.php)

```
1. RolePermissionSeeder    ← OBLIGATOIRE (socle)
2. PlanSeeder              ← OBLIGATOIRE (plans d'abonnement)
3. WorkspaceSeeder         ← DÉMO SEULEMENT
4. NotificationDemoSeeder  ← DÉMO SEULEMENT (dépend de WorkspaceSeeder)
5. LabelSeeder             ← OPTIONNEL (étiquettes de démo)
```

---

## Détail par seeder

### 1. `RolePermissionSeeder` — **OBLIGATOIRE EN PRODUCTION**

**Fichier :** `database/seeders/RolePermissionSeeder.php`

**Ce qu'il fait :**
- Crée toutes les permissions à partir de `App\Permissions\Permission::all()` (source unique de vérité).
- Crée tous les rôles Spatie globaux : `super_admin`, `directeur`, `utilisateur`.
- Crée tous les rôles contextuels (pivot) : `owner`, `manager`, `cadre`, `collaborateur`, `stagiaire`, `observateur`, `task_responsable`.
- Associe les permissions aux rôles via `syncPermissions()`.
- Crée les **comptes de test** avec des identifiants prédéfinis (voir ci-dessous).

**⚠️ Doit être exécuté :**
- À l'installation initiale en production.
- Après chaque ajout de permission ou de rôle dans le code.
- Est **idempotent** — utilise `firstOrCreate` et `syncPermissions`, sans risque de doublon.

**Comptes créés :**

| Email | Mot de passe | Rôle global |
|---|---|---|
| `superadmin@worktracking.com` | `password` | `super_admin` |
| `directeur@worktracking.com` | `password` | `directeur` |
| `manager@worktracking.com` | `password` | `utilisateur` |
| `cadre@worktracking.com` | `password` | `utilisateur` |
| `collaborateur@worktracking.com` | `password` | `utilisateur` |
| `stagiaire@worktracking.com` | `password` | `utilisateur` |
| `observateur@worktracking.com` | `password` | `utilisateur` |
| `utilisateur@worktracking.com` | `password` | `utilisateur` |

> **En production réelle :** changer les mots de passe de ces comptes immédiatement après le déploiement, ou supprimer les comptes de test si non nécessaires. Le compte `superadmin@worktracking.com` est le seul strictement requis pour l'accès admin initial.

---

### 2. `PlanSeeder` — **OBLIGATOIRE EN PRODUCTION**

**Fichier :** `database/seeders/PlanSeeder.php`

**Ce qu'il fait :**
- Crée/met à jour les 3 plans d'abonnement : `free`, `starter`, `pro`.
- Utilise `updateOrCreate` sur le `slug` — idempotent.

**Données insérées :**

| Slug | Nom FR | Prix (XAF/mois) | Membres | Stockage | Taille max fichier |
|---|---|---|---|---|---|
| `free` | Gratuit | 0 | 5 | 100 Mo | 2 Mo |
| `starter` | Starter | 15 000 | 25 | 5 Go | 25 Mo |
| `pro` | Pro | 50 000 | Illimité | Illimité | 100 Mo |

**⚠️ Doit être exécuté :**
- À l'installation initiale.
- Quand les plans tarifaires changent — le seeder remet à jour via `updateOrCreate`.
- Sans les plans, le middleware `CheckSubscriptionStatus` ne peut pas résoudre `Plan::free()` (workspace sans plan → erreur au login).

---

### 3. `WorkspaceSeeder` — **DÉMO / DÉVELOPPEMENT SEULEMENT**

**Fichier :** `database/seeders/WorkspaceSeeder.php`

**Ce qu'il fait :**
- Crée ~13 utilisateurs de démo avec des noms réalistes (Kouassi Éric, Traoré Aïcha, etc.).
- Crée 2 workspaces de démo riches en données : « Direction Générale » (WS1) et « Bureau Régional » (WS2).
- Peuple ces workspaces avec des projets, activités, tâches, résultats, commentaires, labels, équipes, membres.

**⚠️ À NE PAS EXÉCUTER directement en production** — il crée des données fictives. Supprimer cet appel de `DatabaseSeeder` avant le déploiement ou le conditionner sur `APP_ENV`:

```php
// Dans DatabaseSeeder.php, option 1 : supprimer la ligne
// WorkspaceSeeder::class,

// Option 2 : conditionner sur l'environnement
if (app()->environment('local', 'staging')) {
    $this->call(WorkspaceSeeder::class);
}
```

---

### 4. `NotificationDemoSeeder` — **DÉMO / DÉVELOPPEMENT SEULEMENT**

**Fichier :** `database/seeders/NotificationDemoSeeder.php`

**Ce qu'il fait :**
- Crée des lignes `notification_preferences` pour chaque utilisateur de démo.
- Crée des souscriptions Web Push fictives pour cadre et manager (test de la fonctionnalité push).
- Dépend de `WorkspaceSeeder` (les utilisateurs doivent exister).

**⚠️ À NE PAS EXÉCUTER en production** — peuple des souscriptions push fictives. Désactiver en même temps que `WorkspaceSeeder`.

---

### 5. `LabelSeeder` — **OPTIONNEL**

**Fichier :** `database/seeders/LabelSeeder.php`

**Ce qu'il fait :**
- Crée des étiquettes de démonstration au niveau workspace (couleurs, noms).

**En production :** Les utilisateurs créent leurs propres étiquettes via l'interface. Ce seeder peut être conservé pour une installation « démo » ou supprimé pour une installation vierge.

---

## Seeders non appelés dans `DatabaseSeeder` (isolés)

Ces seeders existent mais **ne sont pas dans le pipeline par défaut**. Ils sont utilisés manuellement ou dans des tests.

| Seeder | Usage |
|---|---|
| `ActiviteSeeder.php` | Jeu de données activités (développement) |
| `ProjetSeeder.php` | Jeu de données projets (développement) |
| `TacheSeeder.php` | Jeu de données tâches (développement) |
| `TeamSeeder.php` | Jeu de données équipes (développement) |
| `UserSeeder.php` | Utilisateurs supplémentaires (développement) |
| `SousTacheSeeder.php` | Sous-tâches de démo (appelé dans DatabaseSeeder actuellement) |
| `SupportTicketSeeder.php` | Tickets de support de démo |

---

## Procédure de déploiement recommandée

### Installation initiale production

```bash
# 1. Migrations
php artisan migrate --force

# 2. Seeders obligatoires seulement
php artisan db:seed --class=RolePermissionSeeder --force
php artisan db:seed --class=PlanSeeder --force

# 3. Vider les caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear

# 4. Optimiser pour la production
php artisan optimize
```

### Mise à jour des permissions (après ajout d'une permission dans le code)

```bash
php artisan db:seed --class=RolePermissionSeeder --force
php artisan cache:clear
```

### Mise à jour des plans tarifaires

```bash
php artisan db:seed --class=PlanSeeder --force
```

---

## Modifications recommandées avant le premier déploiement production

1. **`DatabaseSeeder.php`** — retirer `WorkspaceSeeder`, `NotificationDemoSeeder`, `LabelSeeder`, `SousTacheSeeder` ou les conditionner sur `APP_ENV !== 'production'`.
2. **Mots de passe des comptes de test** — les changer ou désactiver les comptes `manager@worktracking.com`, `cadre@worktracking.com`, etc. Seul `superadmin@worktracking.com` est nécessaire pour l'accès initial.
3. **`.env` production** — s'assurer que `APP_DEBUG=false`, `APP_ENV=production`, et que toutes les variables sensibles (clés VAPID, credentials MoMo/Orange, `APP_KEY`, `CORS_ALLOWED_ORIGINS`) sont renseignées.
