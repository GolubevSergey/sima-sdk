<?php

declare(strict_types=1);

namespace SimaLandSdk;

use GuzzleHttp\Pool;
use GuzzleHttp\Psr7\Request as Psr7Request;
use GuzzleHttp\Psr7\Response;

class Request
{
    const CONNECTION_LIMIT_IN_SEC = 75;

    private static array $options = [
        'verify' => false,
        'debug' => false
    ];
    private static string $apiPath = '/api/v5/';

    /**
     * Sends a request to API. If method is POST and request data is provided, Content-Type set as "application/json". Data should be passed as array
     */
    public static function send(\GuzzleHttp\Client $httpClient, string $method, string $path, array $data = [], array $customOptions = [])
    {
        if ($customOptions) {
            self::$options = array_merge(self::$options, $customOptions);
        }
        
        if ($method == 'POST' and $data) {
            self::$options['json'] = $data;
        }

        return $httpClient->request($method, self::$apiPath . $path, self::$options)->getBody()->getContents();
    }

    public static function sendAsync(\GuzzleHttp\Client $httpClient, array $query = [], array $customOptions = []): array
    {
        if ($customOptions) {
            self::$options = array_merge(self::$options, $customOptions);
        }

        $count = count($query);
        if ($count > self::CONNECTION_LIMIT_IN_SEC) {
            self::$options['delay'] = ceil(1000 / self::CONNECTION_LIMIT_IN_SEC);
        }
        
        $requests = function () use ($query) {
            foreach ($query as $q) {
                yield new Psr7Request('GET', self::$apiPath . $q);
            }
        };

        $responses = [];
        $pool = new Pool($httpClient, $requests(), [
            'options' => self::$options,
            'fulfilled' => function(Response $response) use (&$responses) {
                $responses[] = $response->getBody()->getContents();
            }
        ]);

        $promise = $pool->promise();
        $promise->wait();

        return $responses;

    }
}