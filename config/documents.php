<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Storage Disk
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default disk that should be used to store
    | uploaded documents. You can choose from 'local', 's3', 'spaces', etc.
    |
    */

    'default_disk' => env('DOCUMENTS_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Maximum File Size
    |--------------------------------------------------------------------------
    |
    | The maximum file size (in kilobytes) that can be uploaded.
    | Default: 10MB (10240 KB)
    |
    */

    'max_file_size' => env('DOCUMENTS_MAX_SIZE', 10240), // 10MB

    /*
    |--------------------------------------------------------------------------
    | Allowed MIME Types
    |--------------------------------------------------------------------------
    |
    | List of allowed MIME types for document uploads.
    | Set to null to allow all file types.
    |
    */

    'allowed_mime_types' => [
        // Images
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'image/svg+xml',

        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document', // .docx
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet', // .xlsx
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation', // .pptx

        // Text
        'text/plain',
        'text/csv',
        'text/html',

        // Archives
        'application/zip',
        'application/x-rar-compressed',
        'application/x-7z-compressed',

        // Audio
        'audio/mpeg',
        'audio/wav',
        'audio/ogg',

        // Video
        'video/mp4',
        'video/mpeg',
        'video/quicktime',
        'video/webm',
    ],

    /*
    |--------------------------------------------------------------------------
    | Thumbnail Settings
    |--------------------------------------------------------------------------
    |
    | Configuration for image thumbnail generation.
    |
    */

    'thumbnails' => [
        'enabled' => true,
        'width' => 300,
        'height' => 300,
        'quality' => 80,
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    |
    | Base paths for document storage.
    |
    */

    'storage_path' => 'documents',
    'thumbnail_path' => 'documents/thumbnails',

    /*
    |--------------------------------------------------------------------------
    | Storage Paths
    |--------------------------------------------------------------------------
    */
    
    'paths' => [
        'workspaces' => 'workspaces',
        'projets' => 'projets',
        'activites' => 'activites',
        'taches' => 'taches',
        'resultats' => 'resultats',
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Versioning
    |--------------------------------------------------------------------------
    |
    | Enable or disable file versioning.
    |
    */

    'versioning' => [
        'enabled' => true,
        'max_versions' => 10, // Nombre max de versions à conserver
        'auto_cleanup' => true, // Supprimer les anciennes versions automatiquement
    ],

     /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    
    'permissions' => [
        'default_expiry_days' => null, // null = pas d'expiration par défaut
        'allow_share' => true,
        'allow_public_share' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Deduplication
    |--------------------------------------------------------------------------
    |
    | Enable file deduplication using SHA-256 hashing.
    | When enabled, identical files will reference the same storage file.
    |
    */

    'deduplication_enabled' => env('DOCUMENTS_DEDUPLICATION', true),

    /*
    |--------------------------------------------------------------------------
    | Download Tracking
    |--------------------------------------------------------------------------
    |
    | Enable or disable download tracking for analytics.
    |
    */

    'track_downloads' => env('DOCUMENTS_TRACK_DOWNLOADS', true),

    /*
    |--------------------------------------------------------------------------
    | Visibility Options
    |--------------------------------------------------------------------------
    |
    | Default visibility level for uploaded documents.
    | Options: 'private', 'team', 'public'
    |
    */

    'default_visibility' => 'private',
    'visibility_options' => [
        'private' => 'Privé (propriétaire seulement)',
        'team' => 'Équipe (membres de l\'entité)',
        'public' => 'Public (tous les utilisateurs du workspace)',
    ],
    /*
    |--------------------------------------------------------------------------
    | Cloud Storage Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for cloud storage providers.
    |
    */

    's3' => [
        'enabled' => env('AWS_ENABLED', false),
        'bucket' => env('AWS_BUCKET'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
        'url' => env('AWS_URL'),
    ],

    'spaces' => [
        'enabled' => env('DO_SPACES_ENABLED', false),
        'key' => env('DO_SPACES_KEY'),
        'secret' => env('DO_SPACES_SECRET'),
        'endpoint' => env('DO_SPACES_ENDPOINT'),
        'region' => env('DO_SPACES_REGION', 'nyc3'),
        'bucket' => env('DO_SPACES_BUCKET'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Image Processing
    |--------------------------------------------------------------------------
    |
    | Configuration for image processing.
    |
    */

    'image_processing' => [
        'enabled' => env('IMAGE_PROCESSING_ENABLED', true),
        'driver' => env('IMAGE_DRIVER', 'gd'), // 'gd' or 'imagick'
        'quality' => env('IMAGE_QUALITY', 90),
        'max_width' => env('IMAGE_MAX_WIDTH', 2000),
        'max_height' => env('IMAGE_MAX_HEIGHT', 2000),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cleanup Settings
    |--------------------------------------------------------------------------
    |
    | Automatic cleanup of old or orphaned files.
    |
    */

    'cleanup' => [
        'enabled' => env('DOCUMENTS_CLEANUP_ENABLED', true),
        'soft_deleted_after_days' => env('DOCUMENTS_CLEANUP_DAYS', 30), // Delete soft-deleted files after X days
        'orphaned_files' => env('DOCUMENTS_CLEANUP_ORPHANED', true), // Remove files without database records
    ],

];
