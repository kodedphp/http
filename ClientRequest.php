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

namespace Koded\Http;

use Koded\Http\Interfaces\{HttpMethod, HttpStatus, Request, Response};
use InvalidArgumentException;
use JsonSerializable;
use Psr\Http\Message\{RequestInterface, UriInterface};
use function error_get_last;
use function in_array;
use function is_iterable;
use function Koded\Stdlib\json_serialize;
use function preg_match;
use function str_replace;
use function strtoupper;

class ClientRequest implements RequestInterface, JsonSerializable
{
    use HeaderTrait, MessageTrait, JsonSerializeTrait;

    const E_INVALID_REQUEST_TARGET = 'The request target is invalid, it contains whitespaces';
    const E_SAFE_METHODS_WITH_BODY = 'failed to open stream: you should not set the message body with safe HTTP methods';

    protected UriInterface $uri;
    protected HttpMethod|string $method;
    protected string $requestTarget = '';

    /**
     * ClientRequest constructor.
     *
     * If body is provided, the content internally is encoded in JSON
     * and stored in body Stream object.
     *
     * @param HttpMethod          $method
     * @param UriInterface|string $uri
     * @param mixed               $body    [optional] \Psr\Http\Message\StreamInterface|iterable|resource|callable|string|null
     * @param array               $headers [optional]
     */
    public function __construct(
        HttpMethod $method,
        string|UriInterface $uri,
        string|iterable $body = null,
        array $headers = [])
    {
        $this->uri    = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->stream = create_stream($this->prepareBody($body));
        $this->method = $method;
        $this->setHost();
        $this->setHeaders($headers);
    }

    public function getMethod(): string
    {
        return $this->method?->value ?? $this->method;
    }

    public function withMethod(string $method): ClientRequest
    {
        $instance = clone $this;
        $instance->method = HttpMethod::tryFrom(strtoupper($method)) ?? $method;
        return $instance;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, bool $preserveHost = false): static
    {
        $instance = clone $this;
        $instance->uri = $uri;
        if (true === $preserveHost) {
            return $instance->withHeader('Host', $this->uri->getHost() ?: $uri->getHost());
        }
        //return $instance->withHeader('Host', $uri->getHost());
        return $instance->withHeader('Host', $uri->getHost() ?: $this->uri->getHost());
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        if (!$path = $this->uri->getPath()) {
            return '/';
        }
        if ($query = $this->uri->getQuery()) {
            $path .= '?' . $query;
        }
        return $path;
    }

    public function withRequestTarget(string $requestTarget): static
    {
        if (preg_match('/\s+/', $requestTarget)) {
            throw new InvalidArgumentException(
                self::E_INVALID_REQUEST_TARGET,
                HttpStatus::BAD_REQUEST);
        }
        $instance                = clone $this;
        $instance->requestTarget = $requestTarget;
        return $instance;
    }

    public function getPath(): string
    {
        return str_replace($_SERVER['SCRIPT_NAME'], '', $this->uri->getPath()) ?: '/';
    }

    public function getBaseUri(): string
    {
        if (false === empty($host = $this->getUri()->getHost())) {
            $port = $this->getUri()->getPort();
            $port && $port = ":{$port}";
            return $this->getUri()->getScheme() . "://{$host}{$port}";
        }
        return '';
    }

    public function isSecure(): bool
    {
        return 'https' === $this->uri->getScheme();
    }

    public function isSafeMethod(): bool
    {
        return in_array($this->method, Request::SAFE_METHODS);
    }

    protected function setHost(): void
    {
        $this->headersMap['host'] = 'Host';

        $this->headers = ['Host' => $this->uri->getHost() ?: $_SERVER['HTTP_HOST'] ?? ''] + $this->headers;
    }

    /**
     * Checks if body is non-empty if HTTP method is one of the *safe* methods.
     * The consuming code may disallow this and return the response object.
     *
     * @return Response|null
     */
    protected function assertSafeMethod(): ?Response
    {
        if ($this->isSafeMethod() && $this->getBody()->getSize() > 0) {
            return $this->getPhpError(HttpStatus::BAD_REQUEST, self::E_SAFE_METHODS_WITH_BODY);
        }
        return null;
    }

    /**
     * @param mixed $body
     *
     * @return mixed If $body is iterable returns JSON stringified body, or whatever it is
     */
    protected function prepareBody(mixed $body): mixed
    {
        if (is_iterable($body)) {
            return json_serialize($body);
        }
        return $body;
    }

    /**
     * @param int         $status
     * @param string|null $message
     *
     * @return Response JSON error message
     * @link https://tools.ietf.org/html/rfc7807
     */
    protected function getPhpError(int $status, ?string $message = null): Response
    {
        return new ServerResponse(json_serialize([
            'title'    => HttpStatus::CODE[$status],
            'detail'   => $message ?? error_get_last()['message'] ?? HttpStatus::CODE[$status],
            'instance' => (string)$this->getUri(),
            //'type'     => 'https://httpstatuses.com/' . $status,
            'status'   => $status,
        ]), $status, ['Content-Type' => 'application/problem+json']);
    }
}
