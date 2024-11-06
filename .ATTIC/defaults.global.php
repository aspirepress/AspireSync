<?php

declare(strict_types=1);

function get_config_defaults(): array
{
    $app_env = $_ENV['APP_ENV'] ?? 'production';
    $app_dir = $_ENV['APP_DIR'] ?? realpath(__DIR__ . '/../aspiresync');

    $data_dir = $_ENV['APP_DATA_DIR'] ?? $app_dir . '/data';
    $db_file  = str_contains($app_env, 'test')
        ? $_ENV['DB__TEST_FILE'] ?? ':memory:'
        : $_ENV['DB_FILE'] ?? $data_dir . '/aspiresync.sqlite';
    $database = [
        'dsn'         => "sqlite:$db_file",
        'init_script' => __DIR__ . '/../schema.sql',
    ];

    return [
        'paths'            => [
            'basePath'     => $app_dir,
            'downloadPath' => "$data_dir/plugins",
        ],
        'urls'             => [
            'pluginUrl' => 'https://downloads.wordpress.org/plugin/%s.%s.zip',
            'themeUrl'  => '',
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
}

return get_config_defaults();
