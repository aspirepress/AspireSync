<?php

declare(strict_types=1);

namespace App\Factories;

use GuzzleHttp\Client as GuzzleClient;
use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;


class GuzzleClientFactory
{
    public static function create(): GuzzleClient
    {
        // https://codewithkyrian.com/p/how-to-implement-retries-in-guzzlehttp
        $maxRetries = 10;
        $handler = HandlerStack::create();
        $retryMiddleware = Middleware::retry(
            function (int $retries, RequestInterface $request, ?ResponseInterface $response, ?\RuntimeException $e)
            use ($maxRetries) {
                // Limit the number of retries to maxRetries
                if ($retries >= $maxRetries) {
                    return false;
                }

                // Retry connection exceptions
                if ($e instanceof ConnectException) {
                    // echo "Error connecting to " . $request->getUri() . ". Retrying (" . ($retries + 1) . "/" . $maxRetries . ")...\n";
                    echo "ERROR [retrying]: {$e->getMessage()}\n";
                    return true;
                }

                if ($response && in_array($response->getStatusCode(), [249, 429, 500, 502, 503, 504], true)) {
                    // echo "Something went wrong on the server. Retrying (" . ($retries + 1) . "/" . $maxRetries . ")...\n";
                    echo "ERROR [retrying]: {$request->getUri()}: {$response->getStatusCode()}\n";
                    return true;
                }

                return false;
            },
            fn(int $retries) => 500 * $retries,
        );
        $handler->push($retryMiddleware);

        return new GuzzleClient(['handler' => $handler, 'timeout' => 60, 'connect_timeout' => 2]);
    }
}

