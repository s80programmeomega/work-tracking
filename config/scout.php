<?php

use App\Models\Activite;
use App\Models\Document;
use App\Models\Projet;
use App\Models\Tache;
use App\Models\TeamMessage;
use App\Models\User;

return [

    /*
    |--------------------------------------------------------------------------
    | Default Search Engine
    |--------------------------------------------------------------------------
    |
    | This option controls the default search connection that gets used while
    | using Laravel Scout. This connection is used when syncing all models
    | to the search service. You should adjust this based on your needs.
    |
    | Supported: "algolia", "meilisearch", "typesense",
    |            "database", "collection", "null"
    |
    */

    'driver' => env('SCOUT_DRIVER', 'collection'),

    /*
    |--------------------------------------------------------------------------
    | Index Prefix
    |--------------------------------------------------------------------------
    |
    | Here you may specify a prefix that will be applied to all search index
    | names used by Scout. This prefix may be useful if you have multiple
    | "tenants" or applications sharing the same search infrastructure.
    |
    */

    'prefix' => env('SCOUT_PREFIX', ''),

    /*
    |--------------------------------------------------------------------------
    | Queue Data Syncing
    |--------------------------------------------------------------------------
    |
    | This option allows you to control if the operations that sync your data
    | with your search engines are queued. When this is set to "true" then
    | all automatic data syncing will get queued for better performance.
    |
    */

    'queue' => env('SCOUT_QUEUE', false),

    /*
    |--------------------------------------------------------------------------
    | Database Transactions
    |--------------------------------------------------------------------------
    |
    | This configuration option determines if your data will only be synced
    | with your search indexes after every open database transaction has
    | been committed, thus preventing any discarded data from syncing.
    |
    */

    'after_commit' => false,

    /*
    |--------------------------------------------------------------------------
    | Chunk Sizes
    |--------------------------------------------------------------------------
    |
    | These options allow you to control the maximum chunk size when you are
    | mass importing data into the search engine. This allows you to fine
    | tune each of these chunk sizes based on the power of the servers.
    |
    */

    'chunk' => [
        'searchable' => 500,
        'unsearchable' => 500,
    ],

    /*
    |--------------------------------------------------------------------------
    | Soft Deletes
    |--------------------------------------------------------------------------
    |
    | This option allows to control whether to keep soft deleted records in
    | the search indexes. Maintaining soft deleted records can be useful
    | if your application still needs to search for the records later.
    |
    */

    'soft_delete' => false,

    /*
    |--------------------------------------------------------------------------
    | Identify User
    |--------------------------------------------------------------------------
    |
    | This option allows you to control whether to notify the search engine
    | of the user performing the search. This is sometimes useful if the
    | engine supports any analytics based on this application's users.
    |
    | Supported engines: "algolia"
    |
    */

    'identify' => env('SCOUT_IDENTIFY', false),

    /*
    |--------------------------------------------------------------------------
    | Algolia Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Algolia settings. Algolia is a cloud hosted
    | search engine which works great with Scout out of the box. Just plug
    | in your application ID and admin API key to get started searching.
    |
    */

    'algolia' => [
        'id' => env('ALGOLIA_APP_ID', ''),
        'secret' => env('ALGOLIA_SECRET', ''),
        'index-settings' => [
            // 'users' => [
            //     'searchableAttributes' => ['id', 'name', 'email'],
            //     'attributesForFaceting'=> ['filterOnly(email)'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Meilisearch Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Meilisearch settings. Meilisearch is an open
    | source search engine with minimal configuration. Below, you can state
    | the host and key information for your own Meilisearch installation.
    |
    | See: https://www.meilisearch.com/docs/learn/configuration/instance_options#all-instance-options
    |
    */

    'meilisearch' => [
        'host' => env('MEILISEARCH_HOST', 'http://localhost:7700'),
        'key' => env('MEILISEARCH_KEY'),
        'index-settings' => [
            // 'users' => [
            //     'filterableAttributes'=> ['id', 'name', 'email'],
            // ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Typesense Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your Typesense settings. Typesense is an open
    | source search engine using minimal configuration. Below, you will
    | state the host, key, and schema configuration for the instance.
    |
    */

    'typesense' => [
        'client-settings' => [
            'api_key' => env('TYPESENSE_API_KEY', 'xyz'),
            'nodes' => [
                [
                    'host' => env('TYPESENSE_HOST', 'localhost'),
                    'port' => env('TYPESENSE_PORT', '8108'),
                    'path' => env('TYPESENSE_PATH', ''),
                    'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
                ],
            ],
            'nearest_node' => [
                'host' => env('TYPESENSE_HOST', 'localhost'),
                'port' => env('TYPESENSE_PORT', '8108'),
                'path' => env('TYPESENSE_PATH', ''),
                'protocol' => env('TYPESENSE_PROTOCOL', 'http'),
            ],
            'connection_timeout_seconds' => env('TYPESENSE_CONNECTION_TIMEOUT_SECONDS', 2),
            'healthcheck_interval_seconds' => env('TYPESENSE_HEALTHCHECK_INTERVAL_SECONDS', 30),
            'num_retries' => env('TYPESENSE_NUM_RETRIES', 3),
            'retry_interval_seconds' => env('TYPESENSE_RETRY_INTERVAL_SECONDS', 1),
        ],

        'model-settings' => [

            Projet::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'nom', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'code', 'type' => 'string', 'optional' => true],
                        ['name' => 'statut', 'type' => 'string', 'optional' => true],
                        ['name' => 'date_debut', 'type' => 'string', 'optional' => true],
                        ['name' => 'date_fin', 'type' => 'string', 'optional' => true],
                        ['name' => 'workspace_id', 'type' => 'int32'],
                        ['name' => 'workspace_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'responsable_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'nom,description,code,responsable_nom'],
            ],

            Activite::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'nom', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'date_debut', 'type' => 'string', 'optional' => true],
                        ['name' => 'date_fin', 'type' => 'string', 'optional' => true],
                        ['name' => 'workspace_id', 'type' => 'int32'],
                        ['name' => 'workspace_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'projet_id', 'type' => 'int32'],
                        ['name' => 'projet_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'responsable_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'nom,description,projet_nom,responsable_nom'],
            ],

            Tache::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'titre', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'objectif', 'type' => 'string', 'optional' => true],
                        ['name' => 'indicateurs_resultats', 'type' => 'string', 'optional' => true],
                        ['name' => 'commentaire', 'type' => 'string', 'optional' => true],
                        ['name' => 'code', 'type' => 'string', 'optional' => true],
                        ['name' => 'statut', 'type' => 'string'],
                        ['name' => 'priorite', 'type' => 'string'],
                        ['name' => 'echeance', 'type' => 'string', 'optional' => true],
                        ['name' => 'workspace_id', 'type' => 'int32'],
                        ['name' => 'workspace_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'activite_id', 'type' => 'int32'],
                        ['name' => 'activite_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'projet_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'assignees_noms', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'titre,description,objectif,indicateurs_resultats,commentaire,assignees_noms,projet_nom,activite_nom'],
            ],

            Document::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'nom', 'type' => 'string'],
                        ['name' => 'description', 'type' => 'string', 'optional' => true],
                        ['name' => 'content_text', 'type' => 'string', 'optional' => true],
                        ['name' => 'type', 'type' => 'string', 'optional' => true],
                        ['name' => 'mime_type', 'type' => 'string', 'optional' => true],
                        ['name' => 'workspace_id', 'type' => 'int32'],
                        ['name' => 'workspace_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'uploader_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'projet_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'nom,description,content_text,uploader_nom,projet_nom'],
            ],

            User::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'nom', 'type' => 'string'],
                        ['name' => 'prenom', 'type' => 'string', 'optional' => true],
                        ['name' => 'nom_complet', 'type' => 'string', 'optional' => true],
                        ['name' => 'email', 'type' => 'string'],
                        ['name' => 'fonction', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'nom,prenom,nom_complet,email,fonction'],
            ],

            TeamMessage::class => [
                'collection-schema' => [
                    'fields' => [
                        ['name' => 'id', 'type' => 'string'],
                        ['name' => 'uuid', 'type' => 'string'],
                        ['name' => 'content', 'type' => 'string'],
                        ['name' => 'user_nom', 'type' => 'string', 'optional' => true],
                        ['name' => 'team_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'team_uuid', 'type' => 'string', 'optional' => true],
                        ['name' => 'workspace_id', 'type' => 'int32'],
                        ['name' => 'workspace_name', 'type' => 'string', 'optional' => true],
                        ['name' => 'reply_to_snippet', 'type' => 'string', 'optional' => true],
                        ['name' => 'created_at', 'type' => 'int64'],
                    ],
                    'default_sorting_field' => 'created_at',
                ],
                'search-parameters' => ['query_by' => 'content,user_nom,team_name'],
            ],

        ],

        'import_action' => env('TYPESENSE_IMPORT_ACTION', 'upsert'),
    ],

];
