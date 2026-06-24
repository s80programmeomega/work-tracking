# Liste de Vérification — Démo Saisie en Direct
**Script :** `DEMO_SCRIPT_LIVE_FR.md`
**À exécuter après :** `php artisan migrate:fresh --seed --class=RolePermissionSeeder && php artisan db:seed --class=PlanSeeder`

---

## Préparation avant la présentation

```bash
# Seed minimal — rôles, permissions et plans uniquement (aucune donnée métier)
php artisan migrate:fresh --seed --class=RolePermissionSeeder
php artisan db:seed --class=PlanSeeder

# Démarrer les 4 serveurs
php artisan serve
npm run dev
php artisan queue:work
php artisan reverb:start
```

---

## ✅ Fonctionnalités confirmées — Tous les formulaires de création

| Fonctionnalité | Fichier | Statut |
|----------------|---------|--------|
| Formulaire d'inscription (5 champs, sans vérification email) | `Signup.vue` | ✅ |
| Formulaire de création de workspace (nom, description, visibilité, permissions) | `workspaces/Create.vue` | ✅ |
| Modal d'invitation de membre (email, rôle, message, multi-email) | `InviteMemberModal.vue` | ✅ |
| Invitation en attente détectée automatiquement à l'inscription | `Signup.vue` + backend | ✅ |
| Formulaire de création de projet (4 sections) | `ProjetForm.vue` | ✅ |
| Modal de création d'activité (3 sections) | `ActivityForm.vue` | ✅ |
| Assistant de création de tâche en 4 étapes | `TacheCreateWizard.vue` | ✅ |
| Notification temps réel déclenchée à l'assignation d'une tâche | `useLiveNotifications.js` | ✅ |
| Création de sous-tâches + recalcul automatique de la progression pondérée | `SousTacheList.vue` | ✅ |
| Formulaire de soumission de résultat (6 champs dont curseur de complétion) | `SubmitResultModal.vue` | ✅ |
| Modification du rôle d'un membre dans le workspace | `WorkspaceUsers.vue` | ✅ |
| Blocage serveur sur URL restreinte → page 403 | `router/index.ts` + backend | ✅ |
| Formulaire de création d'équipe (nom, description, responsable) | `TeamModal.vue` | ✅ |
| Chat d'équipe temps réel entre deux onglets navigateur | `Teams/Show.vue` | ✅ |
| Formulaire de ticket de support (catégorie, sujet, message, pièces jointes) | `Support.vue` | ✅ |
| Création d'article d'aide avec éditeur riche + auto-sauvegarde de brouillon | `HelpArticleForm.vue` | ✅ |
| Configuration MFA / TOTP (QR code + codes de récupération) | `TwoFactorSettings.vue` | ✅ |
| Cartes de plans d'abonnement avec limites dynamiques | `Plans.vue` | ✅ |
| Modal de paiement : sélection MTN / Orange Money | `Plans.vue` | ✅ |
| Application des limites d'abonnement (invitation au-delà du maximum du plan) | `CheckSubscriptionLimits.php` | ✅ |
| Admin Dashboard reflète les données créées en temps réel | `AdminDashboard.vue` | ✅ |
| Badge cloche déclenché à l'assignation d'une tâche / soumission d'un résultat | `NotificationMenu.vue` | ✅ |
| Recherche Cmd+K indexe les données créées immédiatement | `SearchBar.vue` | ✅ |

---

## ⚠️ Points d'attention pendant la saisie en direct

### L'autocomplétion des @mentions n'existe pas
Taper `@Éric Kouassi` en texte brut dans la zone de saisie du chat. Le backend déclenchera quand même `ChatMentionNotification`. Ne pas prétendre qu'un menu déroulant existe — dire :
> "Les membres mentionnent leurs collègues par leur nom. Le système les notifie instantanément."

### L'email d'invitation peut mettre quelques secondes
Si le worker de queue est actif, l'email d'invitation arrive en quelques secondes. Sinon, l'utilisateur invité peut quand même s'inscrire avec le même email — le système détectera l'invitation en attente automatiquement, sans avoir besoin de l'email.

### Onglets de navigation privée pour les flux multi-utilisateurs
Utiliser **Ctrl+Shift+N** (Chrome/Firefox) pour ouvrir un onglet privé. Cela permet d'être connecté simultanément avec deux comptes différents sans conflit.

### L'auto-sauvegarde du brouillon nécessite 6 secondes d'attente
À l'Acte 17 (article d'aide), après avoir tapé du contenu dans l'éditeur, attendre 6 secondes sans cliquer. Le message "Brouillon enregistré à HH:MM" apparaît automatiquement. Ne pas cliquer sur Sauvegarder — laisser l'auto-sauvegarde se déclencher.

### Les limites d'abonnement nécessitent le plan Gratuit assigné
Après le seed minimal, le workspace créé pendant la démo n'a pas encore de plan assigné. La démonstration des limites (Acte 15) ne fonctionne que lorsqu'il y a 5+ membres dans le workspace ET que le plan Gratuit est assigné.

**Vérifier en cours de démo via tinker si nécessaire :**
```php
$ws = Workspace::first();
$ws->plan_id = Plan::where('slug', 'free')->value('id');
$ws->save();
```

---

## Vérification rapide de la base de données (seed minimal)

Exécuter dans `php artisan tinker` après le seed minimal :

```php
// Seuls les rôles et plans doivent exister — aucun utilisateur, workspace ou projet
User::count();          // attendu : 2 (superadmin + directeur créés par RolePermissionSeeder)
Workspace::count();     // attendu : 0
Projet::count();        // attendu : 0
Plan::count();          // attendu : 3 (free / starter / pro)
```

---

## Configuration des onglets navigateur

| Onglet | Compte | Mot de passe | Rôle |
|--------|--------|--------------|------|
| Onglet 1 | *(créé en direct pendant la démo)* | — | Directeur — créé devant le public |
| Onglet 2 | `superadmin@worktracking.com` | `password` | Administration plateforme |

Les autres comptes (Kofi, etc.) sont créés en direct via des **onglets de navigation privée**.

---

## Éléments à préparer avant la présentation

| Élément | Utilisé dans |
|---------|-------------|
| Un fichier PDF de test sur le bureau | Acte 10 (soumission de résultat) + Acte 18 (ticket de support) |
| Votre téléphone avec Google Authenticator | Acte 13 (MFA) — optionnel mais plus impressionnant |
| Ce script ouvert sur un second écran ou imprimé | Référence pour les valeurs à saisir |
| Une URL quelconque copiée dans le presse-papier | Acte 7 Étape 3 (lien externe sur la tâche) |

---

## Récapitulatif

| Priorité | Élément | Action |
|----------|---------|--------|
| ✅ Prêt | Tous les formulaires de création | Vérifiés et fonctionnels |
| ✅ Prêt | Notifications temps réel | Vérifiées — Reverb doit être actif |
| ✅ Prêt | Chat temps réel | Vérifié — Reverb doit être actif |
| ✅ Prêt | Auto-sauvegarde du brouillon | Attendre 6 sec après la saisie — se déclenche automatiquement |
| ⚠️ En cours de démo | Limites d'abonnement (Acte 15) | Assigner le plan Gratuit via tinker si la limite ne se déclenche pas |
| ⚠️ À noter | Autocomplétion @mention | N'existe pas — taper @nom en texte brut |
| ⚠️ À noter | Délai de l'email d'invitation | Le worker de queue doit être actif pour une livraison rapide |
