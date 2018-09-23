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
    const E_METHOD_NOT_ALLOWED     = 'HTTP method "%s" is not supported';

    /** @var string The HTTP method */
    protected $method = Request::GET;

    /** @var UriInterface */
    protected $uri;

    /** @var string */
    protected $requestTarget = '';

    /**
     * ClientRequest constructor.
     *
     * If body is provided, the content internally is encoded in JSON
     * and stored in body Stream object.
     *
     * @param string                                                                   $method
     * @param UriInterface|string                                                      $uri
     * @param \Psr\Http\Message\StreamInterface|iterable|resource|callable|string|null $body    [optional]
     * @param iterable                                                                 $headers [optional]
     */
    public function __construct(string $method, $uri, $body = null, iterable $headers = [])
    {
        $this->setHost();
        $this->setMethod($method, $this);
        $this->setHeaders($headers);

        $this->uri    = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->stream = create_stream($this->prepareBody($body));
    }

    public function getMethod(): string
    {
        return $this->method;
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
        $instance      = clone $this;
        $instance->uri = $uri;

        if (true === $preserveHost) {
            return $instance;
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

        //return '/';
        return '';
    }

    public function isSecure(): bool
    {
        return 'https' === $this->uri->getScheme();
    }

    public function isMethodSafe(): bool
    {
        return in_array($this->method, Request::SAFE_METHODS);
    }

    protected function setHost(): void
    {
        $this->headersMap['host'] = 'Host';

        $this->headers = ['Host' => $_SERVER['HTTP_HOST'] ?? ''] + $this->headers;
    }

    /**
     * @param string           $method The HTTP method
     * @param RequestInterface $instance
     *
     * @return self
     */
    protected function setMethod($method, RequestInterface $instance): self
    {
        $method = strtoupper($method);

        if (false === in_array($method, Request::HTTP_METHODS)) {
            throw new InvalidArgumentException(
                sprintf(self::E_METHOD_NOT_ALLOWED, $method), StatusCode::METHOD_NOT_ALLOWED
            );
        }

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
        if ($this->isMethodSafe() && $this->getBody()->getSize() > 0) {
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

        $options = JSON_UNESCAPED_SLASHES
            | JSON_PRESERVE_ZERO_FRACTION
            | JSON_NUMERIC_CHECK
            | JSON_UNESCAPED_UNICODE
            | JSON_FORCE_OBJECT;

        return json_encode($body, $options);
    }
}
