<?php

declare(strict_types=1);

$database = [
    'host'   => $_ENV['DB_HOST'],
    'name'   => $_ENV['DB_NAME'],
    'user'   => $_ENV['DB_USER'],
    'pass'   => $_ENV['DB_PASS'],
    'schema' => $_ENV['DB_SCHEMA'],
];

if (str_contains($_ENV['APP_ENV'] ?? 'dev', 'test')) {
    // The unit tests have had a disturbing tendency to clobber the production database.  Let's not do that.
    unset($_ENV['DB_HOST'], $_ENV['DB_NAME'], $_ENV['DB_USER'], $_ENV['DB_PASS'], $_ENV['DB_SCHEMA']);
    $database = [
        'host'   => $_ENV['TEST_DB_HOST'],
        'name'   => $_ENV['TEST_DB_NAME'],
        'user'   => $_ENV['TEST_DB_USER'],
        'pass'   => $_ENV['TEST_DB_PASS'],
        'schema' => $_ENV['TEST_DB_SCHEMA'],
    ];
}

return [
    'paths'            => [
        'basePath'     => '/opt/aspiresync',
        'downloadPath' => '/opt/aspiresync/data/plugins',
    ],
    'urls'             => [
        'pluginUrl' => 'https://downloads.wordpress.org/plugin/%s.%s.zip',
        'themeUrl'  => '',
    ],
    'user-agents'      => [
        'WordPress/6.6; https://example.org',
    ],
    'database'         => $database,
    'flysystem'        => [
        'util:upload' => $_ENV['UPLOAD_ASSETS_ADAPTER'] ?? 'upload_local_filesystem',
    ],
    'amazon'           => [
        's3' => [
            'bucket'   => $_ENV['AWS_BUCKET'] ?? null,
            'region'   => $_ENV['AWS_REGION'] ?? 'us-east-2',
            'endpoint' => $_ENV['AWS_S3_ENDPOINT'] ?? null,
            'key'      => $_ENV['AWS_S3_KEY'] ?? null,
            'secret'   => $_ENV['AWS_S3_SECRET'] ?? null,
        ],
    ],
    'local_filesystem' => [
        'upload_dir' => $_ENV['UPLOAD_DIR'] ?? '/opt/aspiresync/data/uploads',
    ],
];
