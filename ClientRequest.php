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
use Koded\Http\Interfaces\Request;
use Psr\Http\Message\UriInterface;

class ClientRequest implements Request
{

    use HeaderTrait, MessageTrait;

    const E_METHOD_NOT_ALLOWED = 'HTTP method "%s" is not supported';

    /** @var string The HTTP method */
    protected $method = Request::GET;

    /** @var UriInterface */
    protected $uri;

    /** @var string */
    protected $requestTarget = '';

    /**
     * ClientRequest constructor.
     *
     * If body is provided, internally the content is encoded in JSON
     * and stored in body Stream object.
     *
     * @param string                                                                   $method
     * @param UriInterface|string                                                      $uri
     * @param \Psr\Http\Message\StreamInterface|iterable|resource|callable|string|null $body    [optional]
     * @param iterable                                                                 $headers [optional]
     */
    public function __construct(string $method, $uri, $body = null, iterable $headers = [])
    {
        $this->setMethod($method, $this);
        $this->uri = $uri instanceof UriInterface ? $uri : new Uri($uri);

        if (is_array($body)) {
            $body = json_encode($body);
        } elseif (is_iterable($body)) {
            $body = json_encode(iterator_to_array($body));
        }

        $this->stream = create_stream($body);

        $this->setHeaders($headers);
        $this->setHost();
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
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget): ClientRequest
    {
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
        if (!empty($host = $this->getUri()->getHost())) {
            $port = $this->getUri()->getPort();
            $port && $port = ":$port";

            return $this->getUri()->getScheme() . "://{$host}{$port}";
        }

        //return '/';
        return '';
    }

    /**
     * @return bool
     */
    public function isSecure(): bool
    {
        return 'https' === $this->uri->getScheme();
    }

    public function isMethodSafe(): bool
    {
        return in_array($this->method, self::SAFE_METHODS);
    }

    /**
     * Move the Host header at the beginning of the headers stack.
     */
    protected function setHost(): void
    {
        $this->headersMap['host'] = 'Host';

        $this->headers = ['Host' => $this->uri->getHost()] + $this->headers;
    }

    /**
     * @param string        $method The HTTP method
     * @param ClientRequest $instance
     *
     * @return ClientRequest
     */
    protected function setMethod($method, ClientRequest $instance): ClientRequest
    {
        $method = strtoupper($method);

        if (false === in_array($method, Request::HTTP_METHODS)) {
            throw new InvalidArgumentException(
                sprintf(self::E_METHOD_NOT_ALLOWED, $method), HttpStatus::METHOD_NOT_ALLOWED
            );
        }

        $instance->method = $method;

        return $instance;
    }

    /**
     * Checks if body is non-empty if HTTP method is one of the safe methods.
     * The consuming code should disallow this and return the response object.
     *
     * @return ServerResponse|null
     */
    protected function assertSafeMethods(): ?ServerResponse
    {
        if ($this->isMethodSafe() && $this->getBody()->getSize() > 0) {
            return new ServerResponse('failed to open stream: you should not set the message body with safe HTTP methods',
                HttpStatus::BAD_REQUEST
            );
        }

        return null;
    }
}
