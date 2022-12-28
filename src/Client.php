<?php

declare(strict_types=1);

namespace SimaLandSdk;

use Exception;
use GuzzleHttp\Handler\CurlHandler;
use GuzzleHttp\HandlerStack;
use Psr\Http\Message\RequestInterface;

class Client
{

    const JWT_EXPIRATION_DAYS = 7;
    private \GuzzleHttp\Client $httpClient;
    private string $jwt;
    private string $currentUri;
    private string $tokenPath = __DIR__ . '/../cache';
    private string $tokenFileName = 'jwtToken';
    private string $returnFormatValue = 'json';
    private array $returnFormatsList = ['json', 'xml'];
    private array $requestOptions = [];

    public function __construct(array $config = [])
    {
        $stack = new HandlerStack();
        $stack->setHandler(new CurlHandler());
        $stack->push($this->handleHeaders());

        $this->httpClient = HttpClient::Init(['handler' => $stack]);
        $this->tokenPath = realpath($this->tokenPath) . DIRECTORY_SEPARATOR;
        if (!file_exists($this->tokenPath)) {
            mkdir($this->tokenPath);
        }

        if (!$this->isTokenExists()) {
            Validator::validateAuthorizationData($config);
            $this->getJWTToken($config);
        }
        
    }

    /**
     * Set return data format. JSON or XML can be used
     */
    public function setReturnFormat(string $format): void
    {
        if (!in_array($format, $this->returnFormatsList)) {
            throw new Exception("Unknown format \"$format\"");
        }

        $this->returnFormatValue = $format;
    }

    /**
     * Set list of GuzzleHttp options for a request. Full list of options available here https://docs.guzzlephp.org/en/stable/request-options.html
     */
    public function setRequestOptions(array $options): void
    {
        $this->requestOptions = $options;
    }

    public function getHttpClient(): \GuzzleHttp\Client
    {
        return $this->httpClient;
    }

    public function getAttribute(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('attribute', $id, $page);
        return $this;
    }

    public function getCategory(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('category', $id, $page);
        return $this;
    }

    public function getCountry(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('country', $id, $page);
        return $this;
    }

    public function getDataType(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('data-type', $id, $page);
        return $this;
    }

    public function getItem(int|bool $id = false, int $page = 1, bool $brief = false, bool $bySid = false): Client
    {
        $this->currentUri = $this->buildUri('item', $id, $page, $brief, $bySid);
        return $this;
    }

    public function getItemAttribute(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('item-attribute', $id, $page);
        return $this;
    }

    public function getItemCategory(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('item-category', $id, $page);
        return $this;
    }

    public function getItemModifier(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('item-modifier', $id, $page);
        return $this;
    }

    public function getModifier(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('modifier', $id, $page);
        return $this;
    }

    public function getOption(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('option', $id, $page);
        return $this;
    }

    public function getPhotoSize(): Client
    {
        $this->currentUri = $this->buildUri('photo-size');
        return $this;
    }

    public function getTrademark(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('trademark', $id, $page);
        return $this;
    }

    public function getUnit(int|bool $id = false, int $page = 1): Client
    {
        $this->currentUri = $this->buildUri('unit', $id, $page);
        return $this;
    }

    public function send()
    {
        return $this->sendRequestToEndpoint($this->currentUri);
    }

    public function prepare()
    {
        return $this->currentUri;
    }

    public function sendAsync(array $query)
    {
        $responses = Request::sendAsync($this->httpClient, $query, customOptions: $this->requestOptions);
        if ($this->returnFormatValue == 'json') {
            $responses = array_map('json_decode', $responses);
        }
        return $responses;
    }

    private function sendRequestToEndpoint(string $uri): array|string
    {
        $response = Request::send($this->httpClient, 'GET', $uri, customOptions: $this->requestOptions);
        if ($this->returnFormatValue == 'json') {
            $response = json_decode($response, true);
        }
        return $response;
    }

    private function buildUri(string $endpoint, int|bool $id = false, int $page = 1, bool $brief = false, bool $bySid = false): string
    {
        $uri = (is_int($id)) ? $endpoint . '/' . $id : $endpoint;

        $params = [];
        if ($page > 1) {
            $params[] = "p=$page";
        }
        if ($brief) {
            $params[] = "view=brief";
        }
        if ($bySid) {
            $params[] = "by_sid=true";
        }
        $params[] = "expand=has_balance";

        if (!empty($params)) {
            $uri .= "?" . implode("&", $params);
        }

        return $uri;
    }

    private function getJWTToken(array $data): void
    {
        if (!isset($data['regulation'])) {
            $data['regulation'] = true;
        }

        $response = Request::send($this->httpClient, 'POST', 'signin', $data);
        $jsonResponce = json_decode($response);
        $this->jwt = $jsonResponce->token;
        file_put_contents($this->tokenPath . $this->tokenFileName, $this->jwt);
    }

    /**
     * Checks if authorization token was received and is younger than JWT_EXPIRATION_DAYS amount of days
     */
    private function isTokenExists(): bool
    {
        clearstatcache();
        if (!file_exists($this->tokenPath . $this->tokenFileName) or filesize($this->tokenPath . $this->tokenFileName) === 0) {
            file_put_contents($this->tokenPath . $this->tokenFileName, '');
            return false;
        }

        $lastTokenModification = filemtime($this->tokenPath . $this->tokenFileName);
        if ($lastTokenModification < time() - 60 * 60 * 24 * self::JWT_EXPIRATION_DAYS) {
            return false;
        }
        
        $this->jwt = file_get_contents($this->tokenPath . $this->tokenFileName);

        return true;
    }

    /**
     * Middleware sets Authorization and Accept headers after creation of instance
     */
    private function handleHeaders(): callable
    {
        return function(callable $handler) {
            return function(RequestInterface $request, array $options) use ($handler) {
                if (isset($this->jwt) && $this->jwt) {
                    $request = $request->withHeader('Authorization', $this->jwt);
                }
                $request = $request->withHeader('Accept', 'application/' . $this->returnFormatValue);
                return $handler($request, $options);
            };
        };
    }

}