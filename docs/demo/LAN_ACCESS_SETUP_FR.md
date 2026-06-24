# Accès LAN — Partager localhost avec d'autres machines sur le même WiFi

Ce document explique comment rendre l'application Work Tracking accessible aux autres laptops sur le même réseau local, utile pour les démos en présentiel.

---

## Prérequis

- Toutes les machines sur le même réseau WiFi
- L'adresse IP locale de votre machine (obtenue avec `hostname -I | awk '{print $1}'`)

---

## Étape 1 — Trouver votre IP locale

```bash
hostname -I | awk '{print $1}'
# exemple de résultat : 10.5.50.95
```

Remplacer `10.5.50.95` par votre propre IP partout dans ce document.

---

## Étape 2 — Mettre à jour `.env`

Changer `APP_URL` de `localhost`/`127.0.0.1` vers votre vraie IP, et définir `VITE_REVERB_HOST` explicitement — il ne doit pas hériter de `0.0.0.0` via `REVERB_HOST`, car les navigateurs ne peuvent pas se connecter à `0.0.0.0` :

```env
APP_URL=http://10.5.50.95:8000

REVERB_HOST=0.0.0.0           # bind serveur — reste 0.0.0.0
VITE_REVERB_HOST=10.5.50.95   # le navigateur se connecte à la vraie IP
```

---

## Étape 3 — Mettre à jour `vite.config.js`

Ajouter un bloc `server` pour que Vite écoute sur toutes les interfaces et écrive la bonne IP dans `public/hot` :

```js
server: {
    host: '0.0.0.0',        // écoute sur toutes les interfaces
    hmr: {
        host: '10.5.50.95', // adresse utilisée par le navigateur pour le HMR
    },
},
```

Sans `hmr.host`, Vite écrit `http://0.0.0.0:5173` dans `public/hot` et la directive Blade `@vite` injecte des URLs d'assets invalides.

---

## Étape 4 — Ouvrir les ports dans le pare-feu

```bash
sudo ufw allow 8000   # Laravel
sudo ufw allow 5173   # Serveur Vite
sudo ufw allow 8080   # WebSocket Reverb
```

À faire une seule fois par machine.

---

## Étape 5 — Démarrer tous les serveurs

```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run dev
php artisan queue:work
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Vérifier que le fichier `public/hot` a été correctement écrit :

```bash
cat public/hot
# attendu : http://10.5.50.95:5173
```

S'il affiche encore `http://0.0.0.0:5173`, le paramètre `hmr.host` dans `vite.config.js` n'a pas été pris en compte — redémarrer `npm run dev`.

---

## Étape 6 — Accéder depuis les autres machines

Tous les laptops sur le même WiFi ouvrent :

```
http://10.5.50.95:8000
```

C'est tout. Aucune configuration requise sur les autres machines.

---

## Revenir en mode local uniquement

Après la démo, restaurer les valeurs d'origine :

```env
APP_URL=http://127.0.0.1:8000
REVERB_HOST=0.0.0.0
VITE_REVERB_HOST=localhost
```

Dans `vite.config.js`, le bloc `server` peut être conservé — il n'affecte pas l'utilisation locale.

---

## Dépannage

| Symptôme | Cause | Solution |
|----------|-------|----------|
| Page blanche sur `10.5.50.95:8000` | `public/hot` contient `0.0.0.0` | Ajouter `hmr.host` dans `vite.config.js`, redémarrer `npm run dev` |
| Page blanche — assets introuvables (404) | `npm run dev` non démarré | Le démarrer, ou exécuter `npm run build` à la place |
| Port inaccessible | Pare-feu bloque | `sudo ufw allow 8000` et `sudo ufw allow 5173` |
| Notifications temps réel inactives | WebSocket se connecte à la mauvaise adresse | Définir `VITE_REVERB_HOST=10.5.50.95` explicitement dans `.env` |
| Fonctionne en local mais pas pour les autres | Laravel lié à `127.0.0.1` | Utiliser `php artisan serve --host=0.0.0.0` |

---

## Pourquoi ne pas utiliser `npm run build` à la place ?

Pour une démo, `npm run build` est plus stable — aucun serveur Vite nécessaire, pas de port 5173, pas de HMR. La contrepartie : il faut reconstruire après chaque modification du code.

```bash
npm run build
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work
php artisan reverb:start
# seulement 3 processus au lieu de 4, et aucune règle pare-feu nécessaire pour le port 5173
```
