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

use Koded\Http\Interfaces\{ClientType, HttpMethod, HttpRequestClient};
use InvalidArgumentException;

class ClientFactory
{
    private ClientType $clientType;

    public function __construct(ClientType $type = ClientType::CURL)
    {
        $this->clientType = $type;
    }

    public function get($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::GET, $uri, null, $headers);
    }

    public function post($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::POST, $uri, $body, $headers);
    }

    public function put($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::PUT, $uri, $body, $headers);
    }

    public function patch($uri, $body, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::PATCH, $uri, $body, $headers);
    }

    public function delete($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::DELETE, $uri, null, $headers);
    }

    public function head($uri, array $headers = []): HttpRequestClient
    {
        return $this->new(HttpMethod::HEAD, $uri, null, $headers)->maxRedirects(0);
    }

    public function client(): HttpRequestClient
    {
        return $this->new(HttpMethod::HEAD, '');
    }

    protected function new(
        HttpMethod $method,
        $uri,
        $body = null,
        array $headers = []): HttpRequestClient
    {
        return match ($this->clientType) {
            ClientType::CURL => new CurlClient($method, $uri, $body, $headers),
            ClientType::PHP => new PhpClient($method, $uri, $body, $headers),
            default => throw new InvalidArgumentException("{$this->clientType} is not a valid HTTP client"),
        };
    }
}
