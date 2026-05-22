# Task 8b — Web Push Notifications — Testing Guide

## Prérequis

1. Backend : `php artisan serve` (HTTPS pas obligatoire en local — les navigateurs autorisent http://localhost)
2. Frontend : `npm run dev` (le service worker est servi depuis `/sw-webpush.js`)
3. Queue worker : `php artisan queue:work` (les notifications sont queue)
4. Reverb (toujours nécessaire pour Task 8 réel-time) : `php artisan reverb:start`
5. Clés VAPID générées : `php artisan webpush:generate-vapid` → copier dans `.env`
6. DB fraîche avec données démo : `php artisan migrate:fresh --seed`

**Important — clés VAPID :**
- Sans `VAPID_PUBLIC_KEY` / `VAPID_PRIVATE_KEY` dans `.env`, le canal est inerte (le code détecte et logge un warning, mais ne plante pas).
- Le frontend récupère la clé publique via `GET /api/webpush/vapid-key` ; sans config → 503.

---

## Cas 1 — Inscription au push sur un appareil

**Objectif :** un utilisateur authentifié peut autoriser et activer les notifications push depuis la page de préférences.

**Étapes :**
1. Se connecter (n'importe quel rôle suffit).
2. Aller dans **Préférences de notification** (`/notifications/preferences`).
3. Activer le master switch « Notifications push » (s'il ne l'est pas déjà).
4. Sous le toggle, le panneau « Cet appareil » apparaît avec un bouton **Activer**.
5. Cliquer **Activer**.

**Comportement attendu :**
- Un prompt navigateur natif demande la permission de notifier.
- Accorder → la page bascule sur « Inscrit aux notifications push sur ce navigateur ».
- En BDD : nouvelle ligne dans `push_subscriptions` (vérifiable via tinker) :
  ```php
  \App\Models\PushSubscription::latest()->first()
  ```
  → `user_id` correspond, `endpoint` commence par `https://fcm.googleapis.com/...` (Chrome) ou `https://updates.push.services.mozilla.com/...` (Firefox), `active = true`.

**En cas de problème :**
- Refus du prompt → le panneau affiche « Permission refusée. Réactivez les notifications… »
- Si le bouton n'apparaît pas, c'est que le navigateur ne supporte pas Web Push.
- F12 → Application → Service Workers doit montrer `/sw-webpush.js` actif.

---

## Cas 2 — Réception d'une vraie push

**Objectif :** déclencher une notification serveur et la voir apparaître comme notification système.

**Étapes :**
1. Préalable : compléter le Cas 1 (au moins un appareil inscrit pour le user `cadre@worktracking.com`).
2. Dans une autre fenêtre, se connecter en tant que `collaborateur@worktracking.com`.
3. Soumettre un résultat sur une tâche dont `cadre@` est responsable N0.
4. Côté `cadre@`, sans fenêtre active, la notification système devrait apparaître dans les ~secondes qui suivent.

**Comportement attendu :**
- Notification système avec le titre de la tâche.
- Clic → ouvre l'onglet du navigateur sur la page de la tâche.

**Pourquoi cet enchaînement :**
- `submit_result` déclenche `ResultatSoumisN0Notification`
- `via()` appelle `channelsFor(notifiable, 'soumis_n0')` → renvoye_n0 est high-signal donc inclut `webpush`
- Wait — pour `soumis_n0`, on est en low signal côté push. Pour tester réellement le push, déclencher un cas haut-signal :
  - N0 renvoie un résultat → `ResultatRenvoyeNotification` → push ✓
  - Bypass activé → `BypassActivatedNotification` → push ✓
  - Timeout 48h N0 → `ResultatTransmisAutoNotification` → push ✓

---

## Cas 3 — Désabonnement

**Objectif :** retirer cet appareil des destinataires de push.

**Étapes :**
1. Page Préférences → panneau « Cet appareil » → bouton **Désactiver**.
2. En BDD, la ligne `push_subscriptions` correspondante a `active = false` (préservée pour audit, pas supprimée).
3. Le navigateur ne reçoit plus de notifications push.

---

## Cas 4 — Désabonnement automatique sur endpoint mort (410 Gone)

**Objectif :** vérifier qu'une souscription expirée côté push service est automatiquement désactivée côté serveur.

**Difficile à reproduire manuellement** — on dépend du comportement du push service.

**Méthode pratique :**
1. Souscrire un navigateur en local.
2. Désinstaller le service worker depuis F12 → Application → Service Workers → Unregister.
3. Côté serveur, déclencher un push (ex. via tinker : `$user->notify(new ResultatRenvoyeNotification(...))`).
4. Vérifier les logs : `tail -f storage/logs/laravel.log` → on devrait voir `'Souscription Web Push désactivée — endpoint expiré'` avec `status_code: 410` ou `404`.
5. En BDD : `active = false` sur la ligne.

---

## Cas 5 — Permission de l'utilisateur (push_enabled = false)

**Objectif :** si l'utilisateur désactive le master switch, plus aucun push n'est envoyé même s'il a une souscription active.

**Étapes :**
1. Avoir une souscription active (Cas 1).
2. Page Préférences → désactiver le toggle « Notifications push ».
3. Déclencher un événement high-signal (renvoye_n0, bypass…).
4. **Aucune notification push** ne doit apparaître (la souscription reste en BDD active, mais `channelsFor()` n'ajoute pas le canal `webpush`).

**Vérification rapide en tinker :**
```php
$user = \App\Models\User::find(ID);
app(\App\Services\NotificationService::class)->channelsFor($user, 'renvoye_n0');
// Si push_enabled = false → ['database', 'broadcast', 'mail'] (pas de WebPushChannel)
```

---

## Cas 6 — Plusieurs appareils

**Objectif :** un même utilisateur peut être inscrit sur plusieurs navigateurs/appareils, et tous reçoivent le push.

**Étapes :**
1. Se connecter en tant que `cadre@worktracking.com` sur Chrome → inscrire (Cas 1).
2. Se connecter sur Firefox (ou un autre navigateur/incognito) → inscrire à nouveau.
3. Vérifier que `GET /api/webpush/subscriptions` retourne 2 lignes (Chrome + Firefox).
4. Déclencher un événement high-signal → les deux navigateurs doivent recevoir la notification.

---

## Couverture automatique

- **`tests/Feature/WebPushSubscriptionTest.php`** — 13 tests :
  - `vapid-key` retourne / 503 / 401
  - `subscribe` crée / upsert (mêmes endpoint = update, pas duplicate)
  - `subscribe` valide les champs requis
  - `unsubscribe` désactive (vs supprime)
  - `unsubscribe` est no-op sur endpoint inconnu
  - `index` ne liste que les souscriptions actives de l'utilisateur
  - `channelsFor()` : exclut webpush sans souscription active
  - `channelsFor()` : inclut webpush avec souscription + high signal
  - `channelsFor()` : exclut webpush si `push_enabled = false`
  - `channelsFor()` : exclut webpush pour les low-signal events (score_updated, approuve_n0)

- **Pas de Dusk** : tester une vraie inscription Web Push automatiquement nécessite un push service externe, hors scope du test runner. Les tests manuels Cas 1-6 couvrent la chaîne complète.

---

## Sécurité — rappel

- `VAPID_PRIVATE_KEY` ne doit JAMAIS être committée. Vérifier que `.env` est bien dans `.gitignore`.
- En cas de fuite : régénérer (`php artisan webpush:generate-vapid --force`), redéployer, et accepter que toutes les souscriptions existantes sont invalidées (les navigateurs devront se réabonner).
- La clé publique VAPID est exposée par design — c'est elle qui s'affiche au push service pour identifier notre serveur. Pas de risque à la diffuser.
