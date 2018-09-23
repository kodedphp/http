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

use Koded\Http\{ClientRequest, ServerResponse, StatusCode};
use Koded\Http\Interfaces\HttpRequestClient;
use Psr\Http\Message\ResponseInterface;
use Throwable;

/**
 *
 *
 * @link http://php.net/manual/en/context.curl.php
 */
class CurlClient extends ClientRequest implements HttpRequestClient
{

    /** @var resource|false */
    private $resource;

    /** @var array curl options */
    private $options = [
        CURLOPT_MAXREDIRS      => 20,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => HttpRequestClient::USER_AGENT,
        CURLOPT_FAILONERROR    => 0,
    ];

    public function __construct(string $method, $uri, $body = null, array $headers = [])
    {
        parent::__construct($method, $uri, $body, $headers);

        $this->options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion();
        $this->options[CURLOPT_TIMEOUT]      = (ini_get('default_socket_timeout') ?: 10.0) * 1.0;

        $this->resource = curl_init((string)$this->getUri());
    }

    public function read(): ResponseInterface
    {
        if (false === $this->resource) {
            return new ServerResponse(
                'The HTTP client is not created therefore cannot read anything',
                StatusCode::PRECONDITION_FAILED
            );
        }

        $this->prepareRequestBody();

        if ($response = $this->assertSafeMethods()) {
            return $response;
        }

        $this->prepareRequestHeaders();

        try {
            curl_setopt_array($this->resource, $this->options);
            $response = curl_exec($this->resource);

            if (true === $this->hasError()) {
                return new ServerResponse(curl_error($this->resource), StatusCode::UNPROCESSABLE_ENTITY);
            }

            return (new ServerResponse($response, curl_getinfo($this->resource, CURLINFO_RESPONSE_CODE)))
                ->withHeader('Content-Type', curl_getinfo($this->resource, CURLINFO_CONTENT_TYPE));

        } catch (Throwable $e) {
            return new ServerResponse($e->getMessage(), StatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            unset($response);

            if (is_resource($this->resource)) {
                curl_close($this->resource);
            }

            $this->resource = null;
        }
    }

//    public function withBody(StreamInterface $body): HttpRequestClient
//    {
//        $instance         = clone $this;
//        $instance->stream = $body;
//        $this->prepareRequestBody($body);
//
//        return $instance;
//    }

    public function userAgent(string $value): HttpRequestClient
    {
        $this->options[CURLOPT_USERAGENT] = $value;

        return $this;
    }

    public function followLocation(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_FOLLOWLOCATION] = $value;

        return $this;
    }

    public function maxRedirects(int $value): HttpRequestClient
    {
        $this->options[CURLOPT_MAXREDIRS] = $value;

        return $this;
    }

    public function timeout(float $value): HttpRequestClient
    {
        $this->options[CURLOPT_TIMEOUT] = $value;

        return $this;
    }

    public function ignoreErrors(bool $value): HttpRequestClient
    {
        // false = do not fail on error
        $this->options[CURLOPT_FAILONERROR] = (int)!$value;

        return $this;
    }

    public function verifySslHost(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_SSL_VERIFYHOST] = $value;

        return $this;
    }

    public function verifySslPeer(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_SSL_VERIFYPEER] = $value;

        return $this;
    }

    protected function hasError(): bool
    {
        return curl_errno($this->resource) > 0;
    }

    protected function prepareRequestHeaders(): void
    {
        $this->options[CURLOPT_HTTPHEADER] = $this->getFlattenedHeaders();
        unset($this->options[CURLOPT_HTTPHEADER][0]); // Host header is always present and first
    }

    protected function prepareRequestBody(): void
    {
        if ($content = json_decode($this->stream->getContents() ?: '[]', true)) {
            $this->options[CURLOPT_POSTFIELDS] = http_build_query($content);
        }
    }
}
