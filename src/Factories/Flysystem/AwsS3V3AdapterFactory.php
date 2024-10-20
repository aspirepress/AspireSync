<?php

declare(strict_types=1);

namespace AspirePress\AspireSync\Factories\Flysystem;

use Aws\S3\S3Client;
use Laminas\ServiceManager\ServiceManager;
use League\Flysystem\AwsS3V3\AwsS3V3Adapter;

class AwsS3V3AdapterFactory
{
    public function __invoke(ServiceManager $serviceManager): AwsS3V3Adapter
    {
        $config   = $serviceManager->get('config');
        $s3config = $config['amazon']['s3'];

        $passedConfig = [
            'region'  => $s3config['region'],
            'version' => 'latest',
        ];

        if (! empty($s3config['endpoint'])) {
            $passedConfig['bucket_endpoint'] = $s3config['endpoint'];
        }

        if (! empty($s3config['key']) && ! empty($s3config['secret'])) {
            $passedConfig['credentials'] = [
                'key'    => $s3config['key'],
                'secret' => $s3config['secret'],
            ];
        }
        $client = new S3Client($passedConfig);
        return new AwsS3V3Adapter($client, $s3config['bucket']);
    }
}
