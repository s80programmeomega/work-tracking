<?php

use Knuckles\Scribe\Config\AuthIn;
use Knuckles\Scribe\Config\Defaults;
use Knuckles\Scribe\Extracting\Strategies;

use function Knuckles\Scribe\Config\configureStrategy;

return [
    'title' => config('app.name').' — Documentation API',

    'description' => 'API REST du système de suivi de travail. Hiérarchie : Projet → Activité → Tâche.',

    'intro_text' => <<<'INTRO'
        Cette documentation couvre tous les endpoints de l'API.

        <aside>Tous les endpoints (sauf login/register) requièrent un token Bearer Sanctum.
        Obtenez-le via <code>POST /api/auth/login</code>.</aside>
        INTRO,

    'base_url' => config('app.url'),

    'routes' => [
        [
            'match' => [
                'prefixes' => ['api/*'],
                'domains' => ['*'],
            ],
            // Exclure les routes internes et de broadcasting
            'exclude' => [
                'api/broadcasting/auth',
                '_laravel-brain/*',
            ],
        ],
    ],

    // "laravel" : docs servies via Blade + routes Laravel (auth middleware possible)
    'type' => 'laravel',

    'theme' => 'default',

    'static' => [
        'output_path' => 'public/docs',
    ],

    'laravel' => [
        // Désactivé — on enregistre les routes manuellement dans ScribeServiceProvider
        'add_routes' => false,

        'docs_url' => '/api/docs',

        'assets_directory' => null,

        'middleware' => ['auth:sanctum'],
    ],

    'external' => [
        'html_attributes' => [],
    ],

    'try_it_out' => [
        'enabled' => true,
        'base_url' => null,
        // Sanctum CSRF pour les appels Try It Out
        'use_csrf' => true,
        'csrf_url' => '/sanctum/csrf-cookie',
    ],

    'auth' => [
        'enabled' => true,
        'default' => true,
        'in' => AuthIn::BEARER->value,
        'name' => 'Authorization',
        'use_value' => env('SCRIBE_AUTH_KEY'),
        'placeholder' => '{VOTRE_TOKEN_SANCTUM}',
        'extra_info' => 'Obtenez votre token via <code>POST /api/auth/login</code>. Passez-le comme <code>Authorization: Bearer {token}</code>.',
    ],

    'example_languages' => [
        'bash',
        'javascript',
        'php',
    ],

    'postman' => [
        'enabled' => true,
    ],

    'openapi' => [
        'enabled' => true,
        'version' => '3.0.3',
        'overrides' => [
            'info.version' => '1.0.0',
        ],
        'generators' => [],
    ],

    'groups' => [
        'default' => 'Divers',
        'order' => [
            'Authentification',
            'Workspaces',
            'Projets',
            'Activités',
            'Tâches',
            'Sous-tâches',
            'Résultats',
            'Évaluations',
            'Documents',
            'Commentaires',
            'Notifications',
            'Tableau de bord',
            'Administration',
        ],
    ],

    'logo' => false,

    'last_updated' => 'Dernière mise à jour : {date:d F Y}',

    'examples' => [
        'faker_seed' => 1234,
        'models_source' => ['factoryCreate', 'factoryMake', 'databaseFirst'],
    ],

    'strategies' => [
        'metadata' => [
            ...Defaults::METADATA_STRATEGIES,
        ],
        'headers' => [
            ...Defaults::HEADERS_STRATEGIES,
            Strategies\StaticData::withSettings(data: [
                'Content-Type' => 'application/json',
                'Accept' => 'application/json',
            ]),
        ],
        'urlParameters' => [
            ...Defaults::URL_PARAMETERS_STRATEGIES,
        ],
        'queryParameters' => [
            ...Defaults::QUERY_PARAMETERS_STRATEGIES,
        ],
        'bodyParameters' => [
            ...Defaults::BODY_PARAMETERS_STRATEGIES,
        ],
        'responses' => configureStrategy(
            Defaults::RESPONSES_STRATEGIES,
            Strategies\Responses\ResponseCalls::withSettings(
                only: ['GET *'],
                config: ['app.debug' => false]
            )
        ),
        'responseFields' => [
            ...Defaults::RESPONSE_FIELDS_STRATEGIES,
        ],
    ],

    'database_connections_to_transact' => [config('database.default')],

    'fractal' => [
        'serializer' => null,
    ],
];
