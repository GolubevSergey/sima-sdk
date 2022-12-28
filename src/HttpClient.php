<?php

declare(strict_types=1);

namespace SimaLandSdk;

use Exception;
use GuzzleHttp\Client;

class HttpClient
{

    const HOST = 'https://www.sima-land.ru/';
    private static ?Client $instance = null;
    private static array $config = [
        'base_uri' => self::HOST,
        'timeout' => 0,
        'allow_redirects' => false
    ];

    /**
     * Initializes GuzzleHttp\Client as singleton
     */
    public static function Init(array|null $customConfig = null): Client
    {
        if (self::$instance == null) {
            if ($customConfig !== null) {
                self::$config = array_merge(self::$config, $customConfig);
            }
            self::$instance = new Client(self::$config);
        }

        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
    
    public function __wakeup()
    {
        throw new Exception("Cannot unserialize this class");
    }

}