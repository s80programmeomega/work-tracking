# LAN Access Setup — Share localhost with other machines on the same WiFi

This documents how to make the Work Tracking app accessible to other laptops on the same local network, useful for live demos.

---

## Prerequisites

- All machines on the same WiFi network
- Your machine's local IP (find it with `hostname -I | awk '{print $1}'`)

---

## Step 1 — Find your local IP

```bash
hostname -I | awk '{print $1}'
# example output: 10.5.50.95
```

Use this IP everywhere below instead of `10.5.50.95`.

---

## Step 2 — Update `.env`

Change `APP_URL` from `localhost`/`127.0.0.1` to your real IP, and set `VITE_REVERB_HOST` explicitly (it must not inherit `0.0.0.0` from `REVERB_HOST` — browsers cannot connect to `0.0.0.0`):

```env
APP_URL=http://10.5.50.95:8000

REVERB_HOST=0.0.0.0        # server bind — stays 0.0.0.0
VITE_REVERB_HOST=10.5.50.95  # browser connects to real IP
```

---

## Step 3 — Update `vite.config.js`

Add a `server` block so Vite binds to all interfaces and writes the correct IP into `public/hot`:

```js
server: {
    host: '0.0.0.0',   // listen on all interfaces
    hmr: {
        host: '10.5.50.95',  // what the browser uses for HMR websocket
    },
},
```

Without `hmr.host`, Vite writes `http://0.0.0.0:5173` into `public/hot` and the `@vite` Blade directive injects broken asset URLs.

---

## Step 4 — Open firewall ports

```bash
sudo ufw allow 8000   # Laravel
sudo ufw allow 5173   # Vite dev server
sudo ufw allow 8080   # Reverb WebSocket
```

Only needs to be done once per machine.

---

## Step 5 — Start all servers

```bash
php artisan serve --host=0.0.0.0 --port=8000
npm run dev
php artisan queue:work
php artisan reverb:start --host=0.0.0.0 --port=8080
```

Verify the `public/hot` file was written correctly:

```bash
cat public/hot
# expected: http://10.5.50.95:5173
```

If it still shows `http://0.0.0.0:5173`, the `hmr.host` in `vite.config.js` was not picked up — restart `npm run dev`.

---

## Step 6 — Access from other machines

Everyone on the same WiFi opens:

```
http://10.5.50.95:8000
```

That's it. No ngrok, no tunnel, no configuration on the other machines.

---

## Reverting to local-only mode

When the demo is over, restore the original values:

```env
APP_URL=http://127.0.0.1:8000
REVERB_HOST=0.0.0.0
VITE_REVERB_HOST=localhost
```

And in `vite.config.js`, remove the `server` block (or keep it — it doesn't affect local-only usage).

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Blank page on `10.5.50.95:8000` | `public/hot` contains `0.0.0.0` | Add `hmr.host` to `vite.config.js`, restart `npm run dev` |
| Blank page — assets 404 | `npm run dev` not running | Start it, or run `npm run build` instead |
| Cannot reach port at all | Firewall blocking | `sudo ufw allow 8000` and `sudo ufw allow 5173` |
| Real-time notifications not working | WebSocket connecting to wrong host | Set `VITE_REVERB_HOST=10.5.50.95` explicitly in `.env` |
| Works for you but not others | Laravel bound to `127.0.0.1` | Use `php artisan serve --host=0.0.0.0` |

---

## Why not `npm run build` instead?

For a demo, `npm run build` is actually more stable — no Vite dev server needed, no port 5173, no HMR. The tradeoff is you must rebuild after every code change.

```bash
npm run build
php artisan serve --host=0.0.0.0 --port=8000
php artisan queue:work
php artisan reverb:start
# only 3 processes instead of 4, and no firewall rule needed for 5173
```
