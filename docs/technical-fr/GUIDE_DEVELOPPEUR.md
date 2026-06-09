# Work Tracking — Guide du développeur

> **Public :** développeurs rejoignant le projet (équipe / Team-TDR-Consulting).
> **Objectif :** être productif rapidement — installation, environnement local,
> conventions, ajout d'une fonctionnalité, tests et workflow documentaire.

> Pour l'architecture et la référence technique, voir
> [ARCHITECTURE.md](ARCHITECTURE.md).

---

## 1. Prérequis

| Outil | Version | Note |
|---|---|---|
| **PHP** | 8.1+ (8.4 utilisé) | extensions usuelles + **`phpredis`**, `gmp` (Web Push). |
| **Composer** | 2.x | dépendances PHP. |
| **Node.js** | 20+ (24 utilisé) | build frontend. |
| **MySQL** | 8.x | base principale (`work-tracking`) + base de test (`work-tracking-test`). |
| **Redis** | 6+ (8.x ok) | cache, sessions, files, rate-limiting. |
| **Typesense** | 27 | recherche full-text. |

Installation des services système (Debian/Parrot) :
```bash
sudo apt-get update
sudo apt-get install -y redis-server php8.4-redis   # serveur Redis + extension PHP
sudo systemctl enable --now redis-server
redis-cli ping        # → PONG
php -m | grep redis   # → redis
```

Typesense (via conteneur, comme en dev) :
```bash
podman run -d -p 8108:8108 -v ./typesense-data:/data \
  typesense/typesense:27 --data-dir /data --api-key=<clef> --enable-cors
```

---

## 2. Installation du projet

```bash
git clone <repo> work-tracking && cd work-tracking

composer install
npm install

cp .env.example .env
php artisan key:generate
# Éditer .env : DB_*, REDIS_*, TYPESENSE_*, REVERB_*, MAIL_*…

php artisan migrate --seed       # schéma + données de démo (WorkspaceSeeder…)
php artisan storage:link         # sert avatars/documents via /storage

# Index de recherche (si Typesense tourne)
php artisan scout:import "App\Models\Tache"   # idem pour les autres modèles Searchable
```

> **Important — pas d'utilisateur sans workspace.** Le SPA redirige tout
> utilisateur sans `current_workspace_id` vers `/workspaces/create`. Les seeders
> et factories garantissent un workspace ; utiliser `User::factory()->withWorkspace()`
> pour créer un utilisateur complet dans le code de test.

---

## 3. Lancer l'environnement de développement

Quatre processus (terminaux séparés, ou supervisés) :

```bash
php artisan serve          # API + SPA servie   → http://127.0.0.1:8000
npm run dev                # Vite (hot reload)
php artisan horizon        # worker de files Redis (NE PAS lancer aussi queue:work)
php artisan reverb:start   # WebSocket temps réel (port 8080)
```

Points de vérification :
- File d'attente : `php artisan horizon:status` → « Horizon is running ». Dashboard
  `/horizon` (ouvert en local ; super-admin hors local).
- Recherche : `curl http://localhost:8108/health` → `{"ok":true}`.
- Après une modification de `.env` : **`php artisan config:clear`**.

> **Mode dev sans dépendances externes** : `MAIL_MAILER=log` (les e-mails vont
> dans `storage/logs/`), `PAYMENT_FAKE=true` (flux de paiement simulé, **bloqué
> en production**).

---

## 4. Conventions de code (à respecter)

Ces règles sont vérifiées en revue et par les outils :

- **Langue** : le code (variables, méthodes) est en **anglais** ; les
  **commentaires et messages de log** sont en **français**.
- **Validation** : toujours via une **Form Request** (`app/Http/Requests/…`),
  jamais en ligne dans le contrôleur. Vérifier l'autorisation dans `authorize()`.
- **`env()`** uniquement dans `config/*` ; partout ailleurs `config('clé')`.
- **Eloquent** : privilégier les relations + eager-loading (`with`, `withCount`)
  pour éviter les N+1 ; éviter `DB::` brut.
- **Types** : types de retour et de paramètres explicites (PHP 8) ; PHPDoc pour
  les formes de tableaux.
- **PHP** : accolades obligatoires même sur une ligne.
- **Frontend** : réutiliser les composables (`useWorkspace`, `useAuth`,
  `useUsers`…), l'animation `collapse`/`useStagger`, et **vue-i18n** (clés fr+en).
  Les listes/grilles utilisent `useStagger`.
- **Multi-tenant** : toute requête de données doit être **scopée au workspace** ;
  ne jamais exposer de données inter-workspaces (risque de fuite).

---

## 5. Outils qualité (avant chaque commit)

Deux portes obligatoires, dans l'ordre :

```bash
# 1) Formatage (Pint, format agent)
vendor/bin/pint --dirty --format agent

# 2) Analyse statique (Larastan via phpstan.neon)
php artisan clear-compiled && php -d memory_limit=1500M \
  vendor/bin/phpstan analyse --memory-limit=1500M
```

Les deux doivent passer **sans erreur**. Pour le frontend, `npm run build` doit
réussir.

---

## 6. Tests

```bash
php artisan test --compact                                  # toute la suite
php artisan test --compact tests/Feature/Mfa                # un dossier
php artisan test --compact --filter=test_nom                # un test
```

- **PHPUnit** (pas Pest). Les tests de fonctionnalité priment ; couvrir chemins
  heureux, d'échec et limites.
- La base de test est **`work-tracking-test`** (MySQL) ; `RefreshDatabase` rejoue
  les migrations à chaque test.
- L'environnement de test isole les services : `CACHE_DRIVER=array`,
  `QUEUE_CONNECTION=sync`, `SESSION_DRIVER=array`, `SCOUT_DRIVER=collection`,
  broadcast `log` (voir `phpunit.xml`). **Les tests ne dépendent ni de Redis ni
  de Typesense.**
- Créer un test : `php artisan make:test --phpunit NomTest` (`--unit` pour unitaire).
- Utiliser les **factories** + leurs états (`Workspace::factory()->paid()`,
  `User::factory()->withWorkspace()`, `Payment::factory()->succeeded()`…).
- Pour les appels HTTP sortants (paiement), utiliser **`Http::fake()`** — aucun
  appel réseau réel en test.

---

## 7. Ajouter une fonctionnalité (parcours type)

Exemple : un nouveau endpoint API métier.

1. **Migration & modèle** : `php artisan make:model Chose -m` ; définir colonnes,
   `$fillable`, `$casts`, relations, factory + seeder si pertinent.
2. **Form Request** : `php artisan make:request Chose/StoreChoseRequest` (règles +
   `authorize()` ; messages personnalisés au besoin).
3. **Service** (si logique métier) : `app/Services/ChoseService.php`.
4. **Controller** : `php artisan make:controller Api/ChoseController` — fin,
   délègue au service, scope au workspace courant.
5. **Resource** : `app/Http/Resources/ChoseResource.php` (ne jamais exposer de
   secrets ; exposer des noms lisibles plutôt que des FK).
6. **Route** : ajouter sous le groupe `auth:sanctum` dans `routes/api.php`
   (rate-limit dédié si endpoint sensible).
7. **Permission** (si besoin) : suivre le flux complet rôles/permissions
   (`Permission.php` + `forRole()` + côté front `Permission.js` /
   `useWorkspacePermissions` + `WorkspaceController.user_permissions`).
8. **Frontend** : page/composant Vue + route lazy dans `router/index.ts` + clés
   i18n (fr/en) + composable d'appel API.
9. **Tests** : feature tests (happy/échec/limites).
10. **Asynchrone** : opérations longues → Job `ShouldQueue` (file Redis).
11. **Portes** : Pint + Larastan + `npm run build` + tests verts.

---

## 8. Workflow Git & documentaire

### Branches & remotes
- Branche principale d'intégration : **`jonas`** (jamais `main`).
- Travailler sur une branche de fonctionnalité, puis merge `--no-ff` dans `jonas`.
- Deux remotes : **`origin`** (s80programmeomega) et **`client`**
  (Team-TDR-Consulting) — **pousser sur les deux**, toujours en **HTTPS**.
- **Ne jamais** committer/pousser sans instruction explicite. Aucune référence à
  l'IA dans les messages de commit.

### Workflow doc (à la fin d'une tâche)
Mettre à jour la « doc-trail » :
- `docs/SESSION_STATE.md` (tâche courante, branche, prochaine étape) ;
- `docs/PROGRESSION.md` (ou `docs/extended-features/PROGRESSION.md`) ;
- un guide de test `docs/testing/TASK_{N}_TESTING.md` ou
  `docs/extended-features/testing/…` pour les fonctionnalités étendues.

Détail des conventions : `docs/WORKING_GUIDELINES.md`.

---

## 9. Dépannage (problèmes fréquents)

| Symptôme | Cause / solution |
|---|---|
| « Vite manifest not found » | Lancer `npm run dev` ou `npm run build`. |
| Modif `.env` sans effet | `php artisan config:clear` (et redémarrer `serve`). |
| Files non traitées / Horizon vide | Lancer **un seul** worker : `php artisan horizon` (pas aussi `queue:work`). |
| « Too many attempts » au login | Rate-limit `login` (10/min). Réinitialiser en dev : `php artisan cache:clear`. |
| Recherche vide | Typesense up + `php artisan scout:import` des modèles. |
| Avatar/upload qui « revient » | Vérifier `php artisan storage:link` ; l'upload passe en POST (spoof `_method=PUT`). |
| Redirigé en boucle vers `/workspaces/create` | L'utilisateur n'a pas de workspace ; `User::ensureCurrentWorkspace()` le corrige au login/`/auth/me`. |
| `Unknown column …` à l'update profil | Migration manquante — vérifier `php artisan migrate`. |

---

## 10. Commandes artisan utiles (spécifiques au projet)

```bash
php artisan webpush:generate-vapid       # clés VAPID (Web Push)
php artisan momo:provision-sandbox <key> # provisionne un API user/key MTN sandbox
php artisan documents:extract-text       # backfill extraction texte des documents
php artisan notifications:send-digest    # digest quotidien (planifié)
php artisan db:seed --class=PlanSeeder   # plans d'abonnement
```

---

## 11. Références

- **Architecture & référence technique :** [ARCHITECTURE.md](ARCHITECTURE.md)
- **Conventions complètes :** `docs/WORKING_GUIDELINES.md`
- **Onboarding (EN, historique) :** `docs/ONBOARDING.md`
- **Rôles & permissions :** `docs/ROLES_AND_PERMISSIONS.md`, `docs/PERMISSIONS_MATRIX.md`
- **Fonctionnalités étendues + tests :** `docs/extended-features/INDEX.md`
