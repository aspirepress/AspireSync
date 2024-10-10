<?php

declare(strict_types=1);

return [
    'paths'            => [
        'basePath'     => '/opt/assetgrabber',
        'downloadPath' => '/opt/assetgrabber/data/plugins',
    ],
    'urls'             => [
        'pluginUrl' => 'https://downloads.wordpress.org/plugin/%s.%s.zip',
        'themeUrl'  => '',
    ],
    'user-agents'      => [
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/537.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/536.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/535.36",
        "Mozilla/5.0 (Macintosh; Intel Mac OS X 10_15_7) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/129.0.0.0 Safari/534.36",
    ],
    'database'         => [
        'host'   => $_ENV['DB_HOST'],
        'name'   => $_ENV['DB_NAME'],
        'user'   => $_ENV['DB_USER'],
        'pass'   => $_ENV['DB_PASS'],
        'schema' => $_ENV['DB_SCHEMA'],
    ],
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
        'upload_dir' => $_ENV['UPLOAD_DIR'] ?? '/opt/assetgrabber/data/uploads',
    ],
];
