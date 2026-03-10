<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Default Filesystem Disk
    |--------------------------------------------------------------------------
    */

    'default' => env('FILESYSTEM_DISK', 'local'),

    /*
    |--------------------------------------------------------------------------
    | Filesystem Disks
    |--------------------------------------------------------------------------
    |
    | The 'documents' disk is the one used throughout Kore ERP for all
    | project files (email attachments, uploads, generated PDFs).
    |
    | To switch from local to S3, set in .env:
    |   DOCUMENTS_DISK=s3
    |   AWS_ACCESS_KEY_ID=...
    |   AWS_SECRET_ACCESS_KEY=...
    |   AWS_DEFAULT_REGION=...
    |   AWS_BUCKET=...
    |
    | No code changes required — the disk driver is resolved at runtime.
    |
    */

    'disks' => [

        'local' => [
            'driver' => 'local',
            'root'   => storage_path('app'),
            'throw'  => false,
        ],

        'public' => [
            'driver'     => 'local',
            'root'       => storage_path('app/public'),
            'url'        => env('APP_URL') . '/storage',
            'visibility' => 'public',
            'throw'      => false,
        ],

        // ── Kore ERP: Project Documents Disk ─────────────────────────────────
        //
        // All project files are written to this disk.
        // Toggle between local and S3 via DOCUMENTS_DISK env var.
        //
        'documents' => env('DOCUMENTS_DISK', 'local') === 's3'
            ? [
                'driver'                  => 's3',
                'key'                     => env('AWS_ACCESS_KEY_ID'),
                'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
                'region'                  => env('AWS_DEFAULT_REGION', 'us-east-1'),
                'bucket'                  => env('AWS_BUCKET'),
                'url'                     => env('AWS_URL'),
                'endpoint'                => env('AWS_ENDPOINT'),
                'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
                'throw'                   => true,
                // Files are private by default — served via temporaryUrl()
                'visibility'              => 'private',
            ]
            : [
                'driver'     => 'local',
                'root'       => storage_path('app/documents'),
                'throw'      => true,
                'visibility' => 'private',
            ],

        's3' => [
            'driver'                  => 's3',
            'key'                     => env('AWS_ACCESS_KEY_ID'),
            'secret'                  => env('AWS_SECRET_ACCESS_KEY'),
            'region'                  => env('AWS_DEFAULT_REGION', 'us-east-1'),
            'bucket'                  => env('AWS_BUCKET'),
            'url'                     => env('AWS_URL'),
            'endpoint'                => env('AWS_ENDPOINT'),
            'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
            'throw'                   => false,
        ],

    ],

    /*
    |--------------------------------------------------------------------------
    | Symbolic Links
    |--------------------------------------------------------------------------
    */

    'links' => [
        public_path('storage') => storage_path('app/public'),
    ],

];
