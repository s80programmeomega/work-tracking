<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name') }} — Documentation API</title>
    <style>
        *, *::before, *::after { box-sizing: border-box; margin: 0; padding: 0; }

        :root {
            --bg:       #0f172a;
            --surface:  #1e293b;
            --border:   #334155;
            --accent:   #38bdf8;
            --accent2:  #818cf8;
            --text:     #e2e8f0;
            --muted:    #94a3b8;
            --danger:   #f87171;
        }

        body {
            font-family: ui-sans-serif, system-ui, sans-serif;
            background: var(--bg);
            color: var(--text);
            height: 100vh;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        /* ── Barre du haut ─────────────────────────────────────── */
        header {
            background: var(--surface);
            border-bottom: 1px solid var(--border);
            padding: 0 24px;
            height: 52px;
            display: flex;
            align-items: center;
            gap: 24px;
            flex-shrink: 0;
        }

        .logo {
            font-weight: 700;
            font-size: 15px;
            color: var(--text);
            white-space: nowrap;
        }

        .logo span { color: var(--accent); }

        nav { display: flex; gap: 4px; flex: 1; }

        nav button {
            background: none;
            border: none;
            color: var(--muted);
            font-size: 13px;
            font-weight: 500;
            padding: 6px 14px;
            border-radius: 6px;
            cursor: pointer;
            transition: background .15s, color .15s;
        }

        nav button:hover { background: rgba(255,255,255,.06); color: var(--text); }
        nav button.active { background: rgba(56,189,248,.12); color: var(--accent); }

        .header-right {
            display: flex;
            align-items: center;
            gap: 12px;
            flex-shrink: 0;
        }

        .badge {
            font-size: 11px;
            padding: 2px 8px;
            border-radius: 99px;
            background: rgba(56,189,248,.15);
            color: var(--accent);
            border: 1px solid rgba(56,189,248,.3);
        }

        .dl-links { display: flex; gap: 8px; }

        .dl-links a {
            font-size: 12px;
            color: var(--muted);
            text-decoration: none;
            padding: 4px 10px;
            border: 1px solid var(--border);
            border-radius: 5px;
            transition: border-color .15s, color .15s;
        }

        .dl-links a:hover { border-color: var(--accent); color: var(--accent); }

        /* ── État d'authentification ───────────────────────────── */
        #auth-banner {
            display: none;
            background: rgba(248,113,113,.12);
            border-bottom: 1px solid rgba(248,113,113,.3);
            color: var(--danger);
            font-size: 13px;
            text-align: center;
            padding: 8px 16px;
            flex-shrink: 0;
        }

        #auth-banner.visible { display: block; }

        #auth-banner a { color: var(--danger); font-weight: 600; }

        /* ── Zone de contenu ───────────────────────────────────── */
        main {
            flex: 1;
            overflow: hidden;
            position: relative;
        }

        .panel {
            position: absolute;
            inset: 0;
            display: none;
        }

        .panel.active { display: flex; flex-direction: column; }

        /* ── Iframe Scribe ─────────────────────────────────────── */
        #panel-scribe iframe {
            flex: 1;
            border: none;
            width: 100%;
        }

        /* ── Swagger UI ────────────────────────────────────────── */
        #panel-swagger {
            overflow-y: auto;
            background: #fff;
        }

        #swagger-ui .topbar { display: none; }

        /* ── Panneau de chargement / erreur ────────────────────── */
        .loading-overlay {
            position: absolute;
            inset: 0;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            gap: 12px;
            background: var(--bg);
            z-index: 10;
            font-size: 14px;
            color: var(--muted);
        }

        .loading-overlay.hidden { display: none; }

        .spinner {
            width: 32px; height: 32px;
            border: 3px solid var(--border);
            border-top-color: var(--accent);
            border-radius: 50%;
            animation: spin .7s linear infinite;
        }

        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
</head>
<body>

<header>
    <div class="logo">{{ config('app.name') }} <span>API</span></div>

    <nav>
        <button class="active" data-panel="scribe">Scribe</button>
        <button data-panel="swagger">Swagger UI</button>
    </nav>

    <div class="header-right">
        <span class="badge" id="version-badge">OpenAPI 3.0.3</span>
        <div class="dl-links">
            <a id="link-yaml" href="#" data-dl="yaml">YAML</a>
            <a id="link-json" href="#" data-dl="json">JSON</a>
            <a id="link-postman" href="#" data-dl="postman">Postman</a>
        </div>
    </div>
</header>

<div id="auth-banner">
    Token introuvable dans localStorage. <a href="/">Connectez-vous</a> depuis l'application pour accéder à la documentation.
</div>

<main>
    {{-- Panneau Scribe ------------------------------------------------- --}}
    <div class="panel active" id="panel-scribe">
        <div class="loading-overlay" id="scribe-loading">
            <div class="spinner"></div>
            <span>Chargement de la documentation…</span>
        </div>
        <iframe id="scribe-frame" title="Documentation Scribe"></iframe>
    </div>

    {{-- Panneau Swagger UI -------------------------------------------- --}}
    <div class="panel" id="panel-swagger">
        <div id="swagger-ui"></div>
    </div>
</main>

{{-- Swagger UI assets -------------------------------------------------- --}}
<link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>

<script>
(function () {
    const TOKEN_KEY  = 'auth_token';
    const SCRIBE_URL = '{{ route("scribe") }}';
    const JSON_URL   = '{{ route("scribe.docs.json") }}';
    const YAML_URL   = '{{ route("scribe.openapi") }}';
    const POSTMAN_URL= '{{ route("scribe.postman") }}';

    // ── Récupère le token depuis localStorage ───────────────────────────
    const token = localStorage.getItem(TOKEN_KEY);

    if (! token) {
        document.getElementById('auth-banner').classList.add('visible');
    }

    // ── Téléchargements — fetch avec token puis blob download ──────────
    // Les endpoints /api/docs.* sont protégés ; une navigation directe
    // retournerait 401. On fetch() avec le header Authorization et on
    // déclenche le téléchargement via un blob URL temporaire.
    const downloads = {
        yaml:    { url: YAML_URL,    mime: 'application/yaml',       file: 'openapi.yaml' },
        json:    { url: JSON_URL,    mime: 'application/json',        file: 'openapi.json' },
        postman: { url: POSTMAN_URL, mime: 'application/json',        file: 'postman.json' },
    };

    document.querySelectorAll('[data-dl]').forEach(function (link) {
        link.addEventListener('click', function (e) {
            e.preventDefault();
            if (! token) { return; }

            const { url, mime, file } = downloads[link.dataset.dl];

            fetch(url, { headers: { 'Authorization': 'Bearer ' + token } })
                .then(function (res) {
                    if (! res.ok) { throw new Error('HTTP ' + res.status); }
                    return res.blob();
                })
                .then(function (blob) {
                    const blobUrl = URL.createObjectURL(new Blob([blob], { type: mime }));
                    const a = document.createElement('a');
                    a.href = blobUrl;
                    a.download = file;
                    a.click();
                    URL.revokeObjectURL(blobUrl);
                })
                .catch(function (err) {
                    alert('Téléchargement échoué : ' + err.message);
                });
        });
    });

    // ── Navigation par onglets ──────────────────────────────────────────
    let swaggerInitialised = false;

    document.querySelectorAll('nav button').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const target = btn.dataset.panel;

            document.querySelectorAll('nav button').forEach(b => b.classList.remove('active'));
            document.querySelectorAll('.panel').forEach(p => p.classList.remove('active'));

            btn.classList.add('active');
            document.getElementById('panel-' + target).classList.add('active');

            if (target === 'swagger' && ! swaggerInitialised) {
                initSwagger();
                swaggerInitialised = true;
            }
        });
    });

    // ── Scribe dans un iframe avec le token injecté ─────────────────────
    // On construit l'URL de l'iframe en passant une query ?_token= pour
    // un middleware côté serveur qui lit ce paramètre et autorise la requête.
    // En l'absence de ce mécanisme, on charge via fetch + srcdoc.
    (function loadScribe() {
        if (! token) return;

        const loading = document.getElementById('scribe-loading');
        const frame   = document.getElementById('scribe-frame');

        fetch(SCRIBE_URL, {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'text/html',
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
        .then(function (res) {
            if (! res.ok) {
                throw new Error('HTTP ' + res.status);
            }
            return res.text();
        })
        .then(function (html) {
            // Réécrit les chemins relatifs des assets Scribe pour pointer
            // vers l'origine correcte (les assets sont dans /vendor/scribe/).
            const rewritten = html
                .replace(/(src|href)="(?!https?:\/\/|\/\/)([^"]+)"/g, function (match, attr, path) {
                    if (path.startsWith('/')) return match;
                    return attr + '="/vendor/scribe/' + path + '"';
                });

            frame.srcdoc = rewritten;
            loading.classList.add('hidden');
        })
        .catch(function (err) {
            loading.innerHTML =
                '<span style="color:var(--danger)">Impossible de charger la documentation (' + err.message + ').</span>';
        });
    }());

    // ── Swagger UI ──────────────────────────────────────────────────────
    // On récupère la spec OpenAPI avec le token Bearer, puis on la passe
    // directement à Swagger via `spec:` — évite que Swagger UI fasse lui-même
    // un fetch() sans en-tête, ce qui retournerait un 401.
    function initSwagger() {
        const container = document.getElementById('panel-swagger');

        // Indicateur de chargement temporaire
        container.insertAdjacentHTML('afterbegin',
            '<div id="swagger-loading" style="display:flex;align-items:center;justify-content:center;height:200px;gap:12px;font-size:14px;color:#64748b">' +
            '<div style="width:28px;height:28px;border:3px solid #e2e8f0;border-top-color:#38bdf8;border-radius:50%;animation:spin .7s linear infinite"></div>' +
            'Chargement de la spécification OpenAPI…</div>'
        );

        fetch(JSON_URL, {
            headers: {
                'Authorization': 'Bearer ' + token,
                'Accept': 'application/json',
            },
        })
        .then(function (res) {
            if (! res.ok) { throw new Error('HTTP ' + res.status); }
            return res.json();
        })
        .then(function (spec) {
            const loader = document.getElementById('swagger-loading');
            if (loader) { loader.remove(); }

            SwaggerUIBundle({
                spec: spec,
                dom_id: '#swagger-ui',
                deepLinking: true,
                presets: [
                    SwaggerUIBundle.presets.apis,
                    SwaggerUIStandalonePreset,
                ],
                plugins: [SwaggerUIBundle.plugins.DownloadUrl],
                layout: 'StandaloneLayout',
                // Injecte le token sur chaque appel "Try it out"
                requestInterceptor: function (request) {
                    if (token) {
                        request.headers['Authorization'] = 'Bearer ' + token;
                    }
                    return request;
                },
            });
        })
        .catch(function (err) {
            const loader = document.getElementById('swagger-loading');
            if (loader) {
                loader.innerHTML = '<span style="color:#f87171">Impossible de charger la spécification (' + err.message + ').</span>';
            }
        });
    }
}());
</script>
</body>
</html>
