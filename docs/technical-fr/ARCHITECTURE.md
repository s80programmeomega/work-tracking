# Work Tracking — Documentation technique (Architecture & Référence)

> **Public :** équipe technique / client (Team-TDR-Consulting).
> **Version applicative :** Laravel 10 · Vue 3 · PHP 8.4 (contrainte `^8.1`).
> **Dernière mise à jour :** 2026-06-09.

> Ce document décrit le **système tel qu'il est implémenté**. Pour mettre en place
> un environnement et contribuer, voir [GUIDE_DEVELOPPEUR.md](GUIDE_DEVELOPPEUR.md).

---

## 1. Vue d'ensemble

**Work Tracking** est une application de gestion et de suivi de travaux organisée
autour d'une hiérarchie stricte :

```
Workspace → Projet → Activité → Tâche → Sous-tâche
```

Elle est multi-tenant : chaque **Workspace** (espace de travail) isole ses
projets, membres et données. Un utilisateur appartient à un ou plusieurs
workspaces avec un rôle contextuel, et possède un « workspace courant »
(`current_workspace_id`).

Fonctionnalités principales :

- Gestion de projets/activités/tâches avec sous-tâches pondérées et progression
  automatique.
- Circuit de **validation** des résultats de tâches (N0 → N1) avec minuterie 48 h,
  bypass anti-sabotage, et journal d'audit immuable.
- **Évaluation** des intervenants (scores, pénalités/bonus, tableaux de bord).
- **Collaboration** : équipes, messagerie temps réel, @mentions, annonces,
  événements, ressources.
- **Documents** : upload, versions, permissions, partage, extraction de texte
  pour la recherche.
- **Notifications** multi-canal (base de données, e-mail, broadcast temps réel,
  Web Push) + digest quotidien.
- **Recherche globale** (Typesense) sur 8+ entités, avec portée selon le rôle.
- **Centre d'aide** bilingue (articles/catégories, éditeur WYSIWYG).
- **Abonnements** (plans free/starter/pro) + **passerelle de paiement** mobile
  money (MTN MoMo, Orange Money).
- **MFA** (TOTP + OTP e-mail + codes de récupération), authentification sociale
  (Google).

---

## 2. Stack technique

### Backend
| Composant | Rôle |
|---|---|
| **Laravel 10** | Framework applicatif (PHP 8.1+). |
| **Laravel Fortify** | Flux d'authentification (login, MFA/2FA). |
| **Laravel Sanctum** | Authentification API par token (SPA). |
| **spatie/laravel-permission** | Rôles & permissions (guard `web`). |
| **spatie/laravel-activitylog** | Journalisation d'activité. |
| **Laravel Scout + typesense/typesense-php** | Recherche full-text (Typesense). |
| **Laravel Horizon** | Supervision des files Redis. |
| **Laravel Reverb** | WebSockets (broadcasting temps réel). |
| **Laravel Pulse** | Métriques applicatives. |
| **maatwebsite/excel** | Exports Excel. |
| **barryvdh/laravel-dompdf** | Exports PDF. |
| **mews/purifier** | Assainissement HTML (centre d'aide). |
| **minishlink/web-push** | Notifications Web Push (VAPID). |
| **laravel/socialite** | OAuth Google. |

### Frontend
- **Vue.js 3** (SPA, Composition API) — 88 pages, 227 composants.
- **Vue Router** (routes lazy-loaded), **Pinia** (état, dont `authStore`).
- **TailwindCSS v4** + AdminLTE (CSS).
- **Vite** (build/bundling), **vue-i18n** (fr/en), **vue-toastification**.
- **ApexCharts** (graphiques — librairie unique), **Tiptap** (éditeur aide),
  **Laravel Echo** + Pusher protocol (Reverb) pour le temps réel.

### Infrastructure (services externes)
| Service | Usage | Driver |
|---|---|---|
| **MySQL** | Base de données principale. | `mysql` |
| **Redis** | Cache, sessions, files d'attente, rate-limiting. | `phpredis` |
| **Typesense** | Index de recherche full-text. | Scout |
| **Reverb** | Serveur WebSocket (port 8080). | broadcasting |

Configuration runtime confirmée : `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`,
`QUEUE_CONNECTION=redis`, `BROADCAST_DRIVER=reverb`, `SCOUT_DRIVER=typesense`.

---

## 3. Architecture applicative

### 3.1 Schéma général

```
                 Navigateur (SPA Vue 3)
                        │  HTTPS (token Sanctum)
                        ▼
        ┌───────────────────────────────────┐
        │        API Laravel (routes/api)    │
        │  Middlewares: auth:sanctum,         │
        │  subscription.status, throttle…     │
        │  Controllers → Services → Models    │
        └───────────────────────────────────┘
            │         │          │        │
            ▼         ▼          ▼        ▼
         MySQL     Redis     Typesense   Reverb
        (données) (cache/    (recherche) (WebSocket
                  queue/                  temps réel)
                  session)
                        │
                        ▼
                Horizon (workers de file Redis)
                  → jobs : indexation Scout,
                    notifications, digests,
                    extraction de texte, paiements
```

### 3.2 Couches et responsabilités

- **Controllers** (`app/Http/Controllers/Api/`, 42) : validation via **Form
  Requests**, autorisation, orchestration. Pas de logique métier lourde.
- **Services** (`app/Services/`, 30) : logique métier réutilisable
  (`SubscriptionService`, `TacheResultatService`, `EvaluationScoreService`,
  `DocumentService`, `MfaService`, `Payment/*`, etc.).
- **Models** (`app/Models/`, 46) : Eloquent + relations + scopes + accesseurs.
- **API Resources** : sérialisation JSON (ex. `UserResource`).
- **Enums** (`app/Enums/`) : `TacheStatut`, `TachePriorite`, `ProjetStatus`,
  `ProjetVisibility`, `Role`.
- **Jobs** (`app/Jobs/`) : travaux asynchrones (file Redis).
- **Notifications** (`app/Notifications/`, 61) : multi-canal.

### 3.3 Conventions

- **Validation** : toujours via Form Request (`app/Http/Requests/…`), jamais en
  ligne dans le contrôleur.
- **Langue** : code en anglais ; **commentaires et messages de log en français**.
- **env()** uniquement dans les fichiers `config/*` ; ailleurs, `config('…')`.
- Préférer les relations Eloquent + eager-loading (éviter les N+1).

---

## 4. Modèle de données

### 4.1 Hiérarchie cœur

| Entité | Description | Relations clés |
|---|---|---|
| **Workspace** | Espace de travail (tenant). | `owner` (User), `members` (n‑n via `workspace_members`), `projets` (1‑n). |
| **Projet** | Projet d'un workspace. | `workspace`, `responsable`, `activites` (1‑n), `members`. |
| **Activite** | Activité d'un projet. | `projet`, `responsable`, `taches` (1‑n), `members`. |
| **Tache** | Tâche d'une activité. | `activite`, `assignees` (n‑n `tache_user`), `sousTaches`, `labels`, `commentaires`, `documents`. |
| **SousTache** | Sous-tâche pondérée. | `tache`, intervenants. Recalcule la progression du parent. |

### 4.2 Validation & évaluation

| Entité | Rôle |
|---|---|
| **TacheResultat** | Résultat soumis pour validation (circuit N0/N1, bypass). |
| **TacheValidation** | Décision de validation. |
| **ValidationAuditLog** | Journal **immuable** des actions de validation (R6). |
| **EvaluationScore** | Scores d'intervenants (pénalités/bonus). |

### 4.3 Collaboration (équipes)

`Team`, `TeamMember`, `TeamMessage`, `TeamMessageReaction`, `TeamPresence`,
`TeamAnnouncement`, `TeamEvent`, `TeamResource`, `TeamActivity`. Une équipe est
rattachée à un workspace (`workspace_id`) ; la messagerie est temps réel via
Reverb + `last_read_at` pour les non-lus.

### 4.4 Documents

`Document`, `DocumentPermission`, `DocumentDownload`. Stockage disque `public`
(lien symbolique `public/storage`). Colonne `content_text` pour l'extraction
texte (PDF/docx/xlsx) indexée par Scout.

### 4.5 Abonnement & paiement

| Entité | Rôle |
|---|---|
| **Plan** | Plans free/starter/pro (prix, limites `-1 = illimité`). |
| **Payment** | Transaction de paiement (`reference` UUID = clé d'idempotence). |

`Workspace` porte `plan_id`, `subscription_status`
(trial/active/lapsed/locked/free/pending), `subscription_mode`,
`subscription_ends_at`.

### 4.6 Aide, support, notifications, MFA

`HelpArticle`, `HelpCategory`, `HelpArticleImage` · `SupportTicket`,
`SupportTicketReply`, `SupportTicketAttachment` · `Notification`,
`NotificationPreference`, `PushSubscription` · `TwoFactorEmailCode`,
`WorkspaceInvitation`, `ProjetInvitation`.

### 4.7 Énumérations

- **TacheStatut** : `a_faire`, `en_cours`, `en_attente`, `termine`, `annule`,
  `en_retard`, `a_refaire`.
- **TachePriorite** : `faible`, `normale`, `moyenne`, `elevee`, `critique`.

> Le schéma complet est défini par 102 migrations sous `database/migrations/`.

---

## 5. Sécurité, rôles & permissions

### 5.1 Authentification

- **SPA + Sanctum** : token Bearer (durée 7 jours, `sanctum:prune-expired`
  planifié). Le front rafraîchit le token avant expiration.
- **MFA** : TOTP (Fortify) + **OTP e-mail** (`email_otp_enabled`) + **codes de
  récupération**. Challenge post-login via jeton provisoire.
- **OAuth Google** (Socialite) : liaison sur e-mail vérifié uniquement.

### 5.2 Rôles (guard `web`, 10 rôles)

`super_admin`, `owner`, `directeur`, `manager`, `task_responsable`, `cadre`,
`collaborateur`, `stagiaire`, `observateur`, `utilisateur`.

Les rôles sont **contextuels au workspace** : stockés dans les tables pivot
(`workspace_members.role_id`, etc.). **60 permissions** régissent les actions
(voir `docs/PERMISSIONS_MATRIX.md` et `docs/ROLES_AND_PERMISSIONS.md`).

### 5.3 Garde d'abonnement (middleware global)

`subscription.status` s'exécute sur **chaque requête authentifiée** :
- workspace `locked` → **HTTP 402** ;
- préfixes exemptés : `auth`, `subscription`, `payment`, `webhooks`, `user`
  (un workspace verrouillé doit pouvoir payer / se déconnecter) ;
- `super_admin` contourne.

### 5.4 Limitation de débit (rate limiting)

- Global API : `throttle:60,1` (par utilisateur/IP).
- **`login`** : limiteur nommé **10/min par (email + IP)** — protège du
  brute-force sans piéger plusieurs utilisateurs derrière une même IP.
- `register` 10/min, `refresh` 10/min, 2FA challenge 10/min, envoi OTP e-mail
  3/min, `payment/initiate` 6/min.

### 5.5 CORS

`config/cors.php` : origines **pilotées par `CORS_ALLOWED_ORIGINS`** (repli sur
`APP_URL`), jamais `*` avec `supports_credentials=true`.

### 5.6 Paiement — sécurité

- **Confirmation par webhook uniquement** : à réception d'un callback, le serveur
  **re-vérifie l'état auprès de l'API du fournisseur** (`fetchStatus`) ; il ne
  fait jamais confiance au corps du callback.
- **Idempotence** : `PaymentService::confirm()` est sans effet si le paiement
  n'est plus `pending` (rejeu de callback sans danger).
- Secrets dans `.env`/`config()` uniquement ; jamais journalisés.

> Référence sécurité/perf détaillée :
> `docs/extended-features/SECURITY_PERF_BASELINE.md` (findings F1–F4, P1–P5).

---

## 6. Surface API

Toutes les routes sont préfixées `/api`. Authentification par token Sanctum
(sauf routes publiques : invitations, webhooks de paiement, callback OAuth).
**~381 routes** regroupées par domaine fonctionnel :

| Préfixe | Routes | Domaine |
|---|---|---|
| `taches` | 58 | Tâches : CRUD, statut, pièces jointes, liens, validation. |
| `teams` | 41 | Équipes : messagerie, membres, annonces, événements, ressources. |
| `admin` | 33 | Super-admin : stats, workspaces, users, rôles, plans, logs d'audit. |
| `projets` | 30 | Projets + invitations projet. |
| `evaluations` | 24 | Scores, validations en attente, tableaux de bord. |
| `workspaces` | 24 | Workspaces, membres, invitations. |
| `activites` | 23 | Activités. |
| `documents` | 23 | Documents, permissions, partage, recherche. |
| `users` / `user` | 20 | Profil courant, gestion utilisateurs, 2FA. |
| `auth` | 12 | Login, refresh, MFA challenge, OAuth, e-mail OTP. |
| `comments` | 10 | Commentaires + mentions. |
| `labels` / `label-templates` | 18 | Étiquettes et modèles. |
| `notifications` (+prefs) | 10 | Notifications + préférences. |
| `activities` | 7 | Journal d'activité. |
| `subscription` / `payment` / `webhooks` | 7 | Abonnement + paiement + callbacks. |
| `help` | 4 | Centre d'aide (lecture publiée). |
| `search` | 3 | Recherche globale. |
| `dashboard` | 3 | Tableaux de bord. |
| `webpush` | 4 | Souscriptions Web Push. |
| `support` | 4 | Tickets de support. |

> Documentation interactive de l'API : routes `api/docs` (Scribe) —
> `api/docs.json`, `api/docs.openapi`, `api/docs.postman`.

---

## 7. Modules fonctionnels (résumé)

- **Tâches & sous-tâches** : pondération (R2), progression auto, statut parent
  auto (`termine`/`en_retard`), blocage du statut manuel quand sous-tâches.
- **Circuit de validation** : `soumettre → approuverN0/renvoyerN0 →
  transmettreAuN1` (Job 48 h `TransmettreResultatAuN1Job`), bypass anti-sabotage
  (R3/R5), `escalades_abusives` à 3 rejets consécutifs, audit log immuable.
- **Évaluation** : `EvaluationScoreService` (pénalité/bonus/no_impact), tableaux
  de bord N1, scores par période.
- **Équipes / chat** : événements broadcast (Reverb), `@mentions`, présence,
  non-lus (`last_read_at`).
- **Documents** : versions, permissions granulaires, partage par e-mail,
  extraction de texte asynchrone (`ExtractDocumentTextJob`).
- **Recherche** : 8+ modèles `Searchable`, portée par rôle (`search.global` /
  `search.scoped`), surlignage Typesense, export Excel multi-feuilles.
- **Notifications** : `channelsFor()` (database/broadcast/mail/webpush selon le
  niveau de signal), déduplication, digest quotidien planifié.
- **Centre d'aide** : catégories/articles bilingues, FULLTEXT MySQL + inclusion
  recherche globale (publiés uniquement), éditeur Tiptap.
- **Abonnement** : plans, sélection (gratuit immédiat / payant `pending`),
  activation manuelle super-admin, garde 402.
- **Paiement** (Phase 9) : seam agnostique (`PaymentProviderInterface` + MTN +
  Orange + registre), activation `pending → active` confirmée par webhook.

---

## 8. Asynchrone & temps réel

- **Files Redis + Horizon** : superviseur `supervisor-1` sur la connexion
  `redis`, file `default`. Lancer `php artisan horizon` (worker unique — ne pas
  cumuler avec `queue:work`). Dashboard `/horizon` (super-admin hors local).
- **Jobs typiques** : indexation Scout (`SCOUT_QUEUE=true`), notifications,
  digest quotidien, extraction de texte, transmission N1, export de recherche.
- **Planification** (`app/Console/Kernel.php`) : `notifications:send-digest`
  (toutes les 15 min), `sanctum:prune-expired` (quotidien).
- **Temps réel (Reverb)** : canaux privés `App.Models.User.{id}` (notifications)
  et `team.{teamId}` (messagerie). Démarrer `php artisan reverb:start`.

---

## 9. Déploiement (points clés)

Prérequis serveur : PHP 8.1+ (testé 8.4), MySQL, **Redis**, **Typesense**,
Node 20+, et un superviseur de processus (systemd/supervisor).

**Extensions PHP requises** (vérifiées par le préflight de `deploy.sh`) :
`redis` (phpredis — cache/session/queue), `gd` (intervention/image — avatars),
`bcmath` (minishlink/web-push — VAPID ; `gmp` recommandée pour la perf), `zip`
(maatwebsite/excel), `pdo_mysql`, `mbstring`, `curl`, `openssl`, `intl`,
`fileinfo`, `json`, `tokenizer`, `xml`, `ctype`.
```bash
sudo apt-get install -y php8.4-redis php8.4-gd php8.4-bcmath php8.4-zip \
  php8.4-intl php8.4-mbstring php8.4-curl php8.4-xml php8.4-gmp
```
> `deploy.sh` **n'installe pas** ces paquets (besoin de sudo, spécifique à l'OS) :
> il **échoue rapidement** avec la liste des manquants, avant toute mise hors
> ligne, si un prérequis n'est pas satisfait.

Processus longs à superviser en production :
- `php artisan horizon` (files) ;
- `php artisan reverb:start` (WebSocket) ;
- un cron `php artisan schedule:run` (digests, prune).

Variables d'environnement critiques en production (voir `.env.example`) :
- `APP_ENV=production`, `APP_DEBUG=false` ;
- `CACHE_DRIVER=redis`, `SESSION_DRIVER=redis`, `QUEUE_CONNECTION=redis`,
  `REDIS_CLIENT=phpredis` ;
- **`CORS_ALLOWED_ORIGINS`** = domaine(s) réel(s) du frontend (sinon CORS bloque) ;
- Typesense : `TYPESENSE_*` ; Reverb : `REVERB_*` ;
- Paiement : `PAYMENT_*` (identifiants **production** MTN/Orange, base URLs
  production, `PAYMENT_FAKE=false`) — voir
  `docs/extended-features/testing/TASK_PHASE9_TESTING.md` (checklist go-live).

**Script de déploiement.** Le dépôt fournit deux scripts :

- **`deploy.sh`** (racine) — déploiement **VPS / serveur avec shell + systemd** :
  exécute tout le cycle (mode maintenance, `git pull`, `composer --no-dev`,
  `npm run build`, `migrate --force`, `storage:link`, `optimize`/caches,
  réindexation Typesense optionnelle, redémarrage gracieux d'Horizon et de
  Reverb, sortie de maintenance). Fail-fast, idempotent.
  ```bash
  ./deploy.sh                  # déploiement standard de la branche jonas
  REINDEX=1 ./deploy.sh        # + réindexation Typesense (si schéma modifié)
  REVERB_RESTART_CMD="sudo systemctl restart reverb" ./deploy.sh
  ```
  Prérequis côté serveur : Horizon supervisé (systemd/supervisor) et un service
  Reverb ; `deploy.sh` **signale** leur redémarrage mais n'est pas le superviseur.

- **`deploy-hostinger.sh`** + `public/post-deploy.php` — hébergement **mutualisé
  Hostinger** (build front + `composer` + caches ; le lien `storage` se fait via
  `post-deploy.php` faute d'accès shell au symlink).

Étapes manuelles équivalentes (si besoin) :
```bash
php artisan migrate --force
php artisan storage:link            # avatars/documents servis via /storage
php artisan scout:import "App\Models\..."   # (ré)indexation Typesense si besoin
php artisan optimize                # config:cache + route:cache
npm run build
```

---

## 10. Pour aller plus loin

- **Mise en route & contribution :** [GUIDE_DEVELOPPEUR.md](GUIDE_DEVELOPPEUR.md)
- **Rôles & permissions :** `docs/ROLES_AND_PERMISSIONS.md`, `docs/PERMISSIONS_MATRIX.md`
- **Roadmap fonctionnalités étendues :** `docs/extended-features/INDEX.md`
- **Sécurité & performance :** `docs/extended-features/SECURITY_PERF_BASELINE.md`
- **Guides de test par tâche :** `docs/testing/`, `docs/extended-features/testing/`
