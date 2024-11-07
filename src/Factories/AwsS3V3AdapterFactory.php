<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories;

use Aws\S3\S3Client;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class AwsS3V3AdapterFactory
{
    public function __invoke(
        #[Autowire(param: 's3_bucket')] ?string $bucket,
        #[Autowire(param: 's3_region')] ?string $region,
        #[Autowire(param: 's3_key')] ?string $key,
        #[Autowire(param: 's3_secret')] ?string $secret,
        #[Autowire(param: 's3_endpoint')] ?string $endpoint,
    ): AwsS3V3Adapter {
        $config = [
            'region'  => $region,
            'version' => 'latest',
        ];

        if ($endpoint) {
            $config['bucket_endpoint'] = $endpoint;
        }

        if ($key && $secret) {
            $config['credentials'] = ['key' => $key, 'secret' => $secret];
        }

        $client = new S3Client($config);
        return new AwsS3V3Adapter($client, $bucket);
    }
}
