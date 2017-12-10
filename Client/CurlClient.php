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

use Koded\Http\ClientRequest;
use Koded\Http\HttpStatus;
use Koded\Http\Interfaces\HttpRequestClient;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use Throwable;

/**
 *
 *
 * @link http://php.net/manual/en/context.curl.php
 */
class CurlClient extends ClientRequest implements HttpRequestClient
{

    /**
     * @var resource
     */
    private $resource;

    /**
     * @var array curl options
     */
    private $options = [
        CURLOPT_MAXREDIRS      => 20,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => false,
        CURLOPT_SSL_VERIFYHOST => false,
        CURLOPT_USERAGENT      => HttpRequestClient::USER_AGENT,
        CURLOPT_FAILONERROR    => 0,
    ];

    public function open(): HttpRequestClient
    {
        $this->options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion();
        $this->options[CURLOPT_TIMEOUT]      = (ini_get('default_socket_timeout') ?: 10.0) * 1.0;

        $this->resource = curl_init((string)$this->getUri());

        return $this;
    }

    public function read(): ResponseInterface
    {
        $this->formatBody($this->stream);

        if (false === $this->resource) {
            return new ClientResponse(
                'The HTTP client is not opened therefore cannot read anything', HttpStatus::PRECONDITION_FAILED);
        }

        if ($response = $this->assertSafeMethods()) {
            return $response;
        }

        $this->formatHeader();

        try {
            curl_setopt_array($this->resource, $this->options);
            $content = curl_exec($this->resource);
            $info    = curl_getinfo($this->resource);

            [$statusCode, $contentType] = $this->extractFromInfo($info);

            if (true === $this->hasError()) {
                $statusCode = HttpStatus::UNPROCESSABLE_ENTITY;

                return new ClientResponse(curl_error($this->resource), $statusCode, $contentType);
            }

            return new ClientResponse($content, $statusCode, $contentType);
        } catch (Throwable $e) {
            return new ClientResponse($e->getMessage(), HttpStatus::INTERNAL_SERVER_ERROR);
        } finally {
            unset($content, $info);

            if (is_resource($this->resource)) {
                curl_close($this->resource);
            }

            $this->resource = null;
        }
    }

    public function withBody(StreamInterface $body): self
    {
        $instance         = clone $this;
        $instance->stream = $body;
        $this->formatBody($body);

        return $instance;
    }

    public function setUserAgent(string $value): HttpRequestClient
    {
        $this->options[CURLOPT_USERAGENT] = $value;

        return $this;
    }

    public function setFollowLocation(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_FOLLOWLOCATION] = $value;

        return $this;
    }

    public function setMaxRedirects(int $value): HttpRequestClient
    {
        $this->options[CURLOPT_MAXREDIRS] = $value;

        return $this;
    }

    public function setTimeout(float $value): HttpRequestClient
    {
        $this->options[CURLOPT_TIMEOUT] = $value;

        return $this;
    }

    public function setIgnoreErrors(bool $value): HttpRequestClient
    {
        // false = do not fail on error
        $this->options[CURLOPT_FAILONERROR] = (int)!$value;

        return $this;
    }

    public function setVerifySslHost(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_SSL_VERIFYHOST] = $value;

        return $this;
    }

    public function setVerifySslPeer(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_SSL_VERIFYPEER] = $value;

        return $this;
    }

    protected function hasError(): bool
    {
        return curl_errno($this->resource) > 0;
    }

    private function formatHeader(): void
    {
        $this->options[CURLOPT_HTTPHEADER] = $this->getFlattenedHeaders();
    }

    private function formatBody(StreamInterface $body): void
    {
        if ($content = json_decode($body->getContents() ?: '[]', true)) {
            $this->options[CURLOPT_POSTFIELDS] = http_build_query($content);
        }
    }

    private function extractFromInfo(array $info): array
    {
        return [
            (int)$info['http_code'] ?: HttpStatus::INTERNAL_SERVER_ERROR,
            (string)$info['content_type'] ?: ($this->getHeader('Accept')[0] ?? null) ?? 'json'
        ];
    }
}
