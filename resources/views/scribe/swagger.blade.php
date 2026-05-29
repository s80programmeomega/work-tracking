<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>{{ config('app.name') }} — Swagger UI</title>
    <link rel="stylesheet" href="https://unpkg.com/swagger-ui-dist@5/swagger-ui.css" />
    <style>
        body  { margin: 0; font-family: sans-serif; }
        .doc-nav {
            background: #1b1b1b;
            padding: 10px 20px;
            display: flex;
            gap: 20px;
            align-items: center;
        }
        .doc-nav a {
            color: #ccc;
            text-decoration: none;
            font-size: 14px;
        }
        .doc-nav a:hover { color: #fff; }
        .doc-nav a.active { color: #89bf04; font-weight: bold; }
        /* Masque le topbar Swagger par défaut */
        .swagger-ui .topbar { display: none; }
    </style>
</head>
<body>
<nav class="doc-nav">
    <span style="color:#fff;font-weight:bold">{{ config('app.name') }} API</span>
    <a href="{{ route('scribe') }}">Docs Scribe</a>
    <a href="{{ route('scribe.docs.swagger') }}" class="active">Swagger UI</a>
    <a href="{{ route('scribe.openapi') }}" target="_blank">OpenAPI YAML</a>
    <a href="{{ route('scribe.docs.json') }}" target="_blank">OpenAPI JSON</a>
    <a href="{{ route('scribe.postman') }}" target="_blank">Postman</a>
</nav>

<div id="swagger-ui"></div>

<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-bundle.js"></script>
<script src="https://unpkg.com/swagger-ui-dist@5/swagger-ui-standalone-preset.js"></script>
<script>
    window.onload = function () {
        // Récupère le token Sanctum stocké dans localStorage par le frontend Vue
        const token = localStorage.getItem('auth_token') || '';

        const ui = SwaggerUIBundle({
            url: "{{ route('scribe.docs.json') }}", // OpenAPI JSON endpoint
            dom_id: '#swagger-ui',
            deepLinking: true,
            presets: [
                SwaggerUIBundle.presets.apis,
                SwaggerUIStandalonePreset,
            ],
            plugins: [
                SwaggerUIBundle.plugins.DownloadUrl,
            ],
            layout: 'StandaloneLayout',
            requestInterceptor: function (request) {
                // Injecte automatiquement le token Bearer si présent dans localStorage
                if (token) {
                    request.headers['Authorization'] = 'Bearer ' + token;
                }
                return request;
            },
        });

        // Pré-autorise avec le token si disponible
        if (token) {
            ui.preauthorizeApiKey('bearerAuth', token);
        }

        window.ui = ui;
    };
</script>
</body>
</html>
