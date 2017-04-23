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
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\UriInterface;

class ClientRequest implements RequestInterface
{

    use HeaderTrait, MessageTrait;

    const E_METHOD_NOT_SUPPORTED = 'HTTP method "%s" is not supported';

    /** @var string The HTTP method */
    protected $method = Request::GET;

    /** @var UriInterface */
    protected $uri;

    /** @var string */
    protected $requestTarget = '';

    public function __construct(string $method, $uri, $body = null, array $headers = [])
    {
        $this->assertMethod($method);
        $this->method = strtoupper($method);
        $this->uri    = $uri instanceof UriInterface ? $uri : new Uri($uri);
        $this->setHost();
    }

    public function getRequestTarget(): string
    {
        return $this->requestTarget;
    }

    public function withRequestTarget($requestTarget): ClientRequest
    {
        $instance = clone $this;

        $instance->requestTarget = $requestTarget;

        return $instance;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function withMethod($method): ClientRequest
    {
        $this->assertMethod($method);
        $instance = clone $this;

        $instance->method = strtoupper($method);

        return $instance;
    }

    public function getUri(): UriInterface
    {
        return $this->uri;
    }

    public function withUri(UriInterface $uri, $preserveHost = false): ClientRequest
    {
        $instance = clone $this;

        $instance->uri = $uri;

        if (true === $preserveHost) {
            return $instance;
        }

        if (empty($instance->getHeader('host')) and $host = $uri->getHost()) {
            return $instance->withHeader('Host', $host);
        }

        return $instance->withHeader('Host', [$uri->getHost()]);
    }

    /**
     * Move the Host header at the beginning.
     */
    protected function setHost(): void
    {
        $this->headersMap['host'] = 'Host';
        $this->headers            = ['Host' => $this->uri->getHost()] + $this->headers;
    }

    /**
     * @param string $method The HTTP method
     */
    private function assertMethod($method): void
    {
        if (false === in_array(strtoupper($method), Request::HTTP_METHODS)) {
            throw new InvalidArgumentException(
                sprintf(self::E_METHOD_NOT_SUPPORTED, $method),
                HttpStatus::METHOD_NOT_ALLOWED
            );
        }
    }
}
