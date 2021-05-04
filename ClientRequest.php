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

use Koded\Http\Interfaces\{HttpStatus, Request, Response};
use Psr\Http\Message\{RequestInterface, UriInterface};
use function Koded\Stdlib\json_serialize;

class ClientRequest implements RequestInterface, \JsonSerializable
{
    use HeaderTrait, MessageTrait, JsonSerializeTrait;

    const E_INVALID_REQUEST_TARGET = 'The request target is invalid, it contains whitespaces';
    const E_SAFE_METHODS_WITH_BODY = 'failed to open stream: you should not set the message body with safe HTTP methods';

    protected UriInterface $uri;
    protected string $method        = Request::GET;
    protected string $requestTarget = '';

    /**
     * ClientRequest constructor.
     *
     * If body is provided, the content internally is encoded in JSON
     * and stored in body Stream object.
     *
     * @param string              $method
     * @param UriInterface|string $uri
     * @param mixed               $body    [optional] \Psr\Http\Message\StreamInterface|iterable|resource|callable|string|null
     * @param array               $headers [optional]
     */
    public function __construct(
        string $method,
        string|UriInterface $uri,
        string|iterable $body = null,
        array $headers = [])
    {
        $this->uri    = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->stream = create_stream($this->prepareBody($body));
        $this->setHost();
        $this->setMethod($method, $this);
        $this->setHeaders($headers);
    }

    public function getMethod(): string
    {
        return \strtoupper($this->method);
    }

    public function withMethod($method): ClientRequest
    {
        return $this->setMethod($method, clone $this);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): static
    {
        $instance = clone $this;
        $instance->uri = $uri;
        if (true === $preserveHost) {
            return $instance->withHeader('Host', $this->uri->getHost() ?: $uri->getHost());
        }
        return $instance->withHeader('Host', $uri->getHost());
    }

    public function getRequestTarget(): string
    {
        if ($this->requestTarget) {
            return $this->requestTarget;
        }
        $path = $this->uri->getPath();
        if (!$path && !$this->requestTarget) {
            return '/';
        }
        if ($query = $this->uri->getQuery()) {
            $path .= '?' . $query;
        }
        return $path;
    }

    public function withRequestTarget($requestTarget): static
    {
        if (\preg_match('/\s+/', $requestTarget)) {
            throw new \InvalidArgumentException(
                self::E_INVALID_REQUEST_TARGET,
                HttpStatus::BAD_REQUEST);
        }
        $instance                = clone $this;
        $instance->requestTarget = $requestTarget;
        return $instance;
    }

    public function getPath(): string
    {
        return \str_replace($_SERVER['SCRIPT_NAME'], '', $this->uri->getPath()) ?: '/';
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
        return \in_array($this->method, Request::SAFE_METHODS);
    }

    protected function setHost(): void
    {
        $this->headersMap['host'] = 'Host';

        $this->headers = ['Host' => $this->uri->getHost() ?: $_SERVER['HTTP_HOST'] ?? ''] + $this->headers;
    }

    /**
     * @param string           $method The HTTP method
     * @param RequestInterface $instance
     *
     * @return static
     */
    protected function setMethod(string $method, RequestInterface $instance): RequestInterface
    {
        $instance->method = \strtoupper($method);
        return $instance;
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
        if (\is_iterable($body)) {
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
            'title'    => StatusCode::CODE[$status],
            'detail'   => $message ?? \error_get_last()['message'] ?? StatusCode::CODE[$status],
            'instance' => (string)$this->getUri(),
            'type'     => 'https://httpstatuses.com/' . $status,
            'status'   => $status,
        ]), $status, ['Content-Type' => 'application/problem+json']);
    }
}
