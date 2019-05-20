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

use InvalidArgumentException;
use JsonSerializable;
use Koded\Http\Interfaces\Request;
use Psr\Http\Message\{RequestInterface, UriInterface};


class ClientRequest implements RequestInterface, JsonSerializable
{

    use HeaderTrait, MessageTrait, JsonSerializeTrait;

    const E_INVALID_REQUEST_TARGET = 'The request target is invalid, it contains whitespaces';
    const E_SAFE_METHODS_WITH_BODY = 'failed to open stream: you should not set the message body with safe HTTP methods';

    protected $method        = Request::GET;
    protected $isSafeMethod  = true;
    protected $requestTarget = '';

    /** @var UriInterface */
    protected $uri;

    /**
     * ClientRequest constructor.
     *
     * If body is provided, the content internally is encoded in JSON
     * and stored in body Stream object.
     *
     * @param string                                                                   $method
     * @param UriInterface|string                                                      $uri
     * @param \Psr\Http\Message\StreamInterface|iterable|resource|callable|string|null $body    [optional]
     * @param array                                                                    $headers [optional]
     */
    public function __construct(string $method, $uri, $body = null, array $headers = [])
    {
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);

        $this->setHost();
        $this->setMethod($method, $this);
        $this->setHeaders($headers);

        $this->isSafeMethod = $this->isSafeMethod();
        $this->stream       = create_stream($this->prepareBody($body));
    }

    public function getMethod(): string
    {
        return strtoupper($this->method);
    }

    public function withMethod($method): ClientRequest
    {
        return $this->setMethod($method, clone $this);
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ClientRequest
    {
        $instance = clone $this;

        if (true === $preserveHost) {
            $uri      = $uri->withHost($this->uri->getHost());
            $instance = $instance->withUri($uri);
        } else {
            $instance->uri = $uri;
        }

        if (empty($instance->getHeader('host')) && $host = $uri->getHost()) {
            return $instance->withHeader('Host', $host);
        }

        return $instance->withHeader('Host', [$uri->getHost()]);
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

    public function withRequestTarget($requestTarget): ClientRequest
    {
        if (preg_match('/\s+/', $requestTarget)) {
            throw new InvalidArgumentException(self::E_INVALID_REQUEST_TARGET, StatusCode::BAD_REQUEST);
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
     * @param string           $method The HTTP method
     * @param RequestInterface $instance
     *
     * @return self
     */
    protected function setMethod(string $method, RequestInterface $instance): RequestInterface
    {
        $instance->method = $method;

        return $instance;
    }

    /**
     * Checks if body is non-empty if HTTP method is one of the "safe" methods.
     * The consuming code may disallow this and return the response object.
     *
     * @return ServerResponse|null
     */
    protected function assertSafeMethods(): ?ServerResponse
    {
        if ($this->isSafeMethod() && $this->getBody()->getSize() > 0) {
            return new ServerResponse(self::E_SAFE_METHODS_WITH_BODY, StatusCode::BAD_REQUEST);
        }

        return null;
    }

    /**
     * @param mixed $body
     *
     * @return mixed If $body is iterable returns JSON stringified body, or whatever it is
     */
    protected function prepareBody($body)
    {
        if (false === is_iterable($body)) {
            return $body;
        }

        return json_encode($body, JSON_UNESCAPED_SLASHES | JSON_PRESERVE_ZERO_FRACTION);
    }
}
