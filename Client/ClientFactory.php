<?php

/*
 * This file is part of the Koded package.
 *
 * (c) Mihail Binev <mihail@kodeart.com>
 *
 * Please view the LICENSE distributed with this source code
 * for the full copyright and license information.
 *
 */

namespace Koded\Http\Client;

use InvalidArgumentException;
use Koded\Http\Interfaces\HttpRequestClient;
use Koded\Http\Interfaces\Request;

class ClientFactory
{

    const CURL = 0;
    const PHP  = 1;

    private $clientType = self::CURL;

    public function __construct(int $clientType = ClientFactory::PHP)
    {
        $this->clientType = $clientType;
    }

    public function open(string $method, $uri, $body = null, iterable $headers = []): HttpRequestClient
    {
        switch ($this->clientType) {
            case self::CURL:
                return (new CurlClient($method, $uri, $body, $headers))->open();

            case self::PHP:
                return (new PhpClient($method, $uri, $body, $headers))->open();

            default:
                throw new InvalidArgumentException("{$this->clientType} is not a valid HTTP client");
        }
    }

    public function get($uri, $headers = []): HttpRequestClient
    {
        return $this->open(Request::GET, $uri, null, $headers)->open();
    }

    public function post($uri, $body, $headers = []): HttpRequestClient
    {
        return $this->open(Request::POST, $uri, $body, $headers)->open();
    }

    public function put($uri, $body, $headers = []): HttpRequestClient
    {
        return $this->open(Request::PUT, $uri, $body, $headers)->open();
    }

    public function patch($uri, $body, $headers = []): HttpRequestClient
    {
        return $this->open(Request::PATCH, $uri, $body, $headers)->open();
    }

    public function delete($uri, $headers = []): HttpRequestClient
    {
        return $this->open(Request::DELETE, $uri, null, $headers)->open();
    }

    public function head($uri, $headers = []): HttpRequestClient
    {
        return $this->open(Request::HEAD, $uri, null, $headers)->open();
    }
}