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
            'region' => $region,
        ];

        if ($endpoint) {
            $config['endpoint'] = $endpoint;
        }

        if ($key && $secret) {
            $config['credentials'] = ['key' => $key, 'secret' => $secret];
        }

        $client = new S3Client($config);

        // Fail fast: if configuration is no good, we want to know. Flysystem swallows errors, the S3 SDK does not.
        $client->putObject([
            'Bucket' => 'aspiresync-dev',
            'Key'    => 'stamp',
            'Body'   => date('c'),
        ]);

        return new AwsS3V3Adapter($client, $bucket);
    }
}
