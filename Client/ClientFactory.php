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

use Koded\Http\Interfaces\{HttpRequestClient, Request};

class ClientFactory
{
    const CURL = 0;
    const PHP  = 1;

    private int $clientType = self::CURL;

    public function __construct(int $clientType = ClientFactory::CURL)
    {
        $this->clientType = $clientType;
    }

    public function get($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::GET, $uri, null, $headers);
    }

    public function post($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::POST, $uri, $body, $headers);
    }

    public function put($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::PUT, $uri, $body, $headers);
    }

    public function patch($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::PATCH, $uri, $body, $headers);
    }

    public function delete($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::DELETE, $uri, null, $headers);
    }

    public function head($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(Request::HEAD, $uri, null, $headers)->maxRedirects(0);
    }

    public function client(): HttpRequestClient
    {
        return $this->new('HEAD', '');
    }

    protected function new(string $method, $uri, $body = null, array $headers = []): HttpRequestClient
    {
        return match ($this->clientType) {
            self::CURL => new CurlClient($method, $uri, $body, $headers),
            self::PHP => new PhpClient($method, $uri, $body, $headers),
            default => throw new \InvalidArgumentException("{$this->clientType} is not a valid HTTP client"),
        };
    }
}
