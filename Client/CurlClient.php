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
use Koded\Http\ServerResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;
use RuntimeException;
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
        CURLOPT_FAILONERROR    => 1,
    ];

    public function open(): HttpRequestClient
    {
        $this->options[CURLOPT_HTTP_VERSION] = $this->getProtocolVersion();
        $this->options[CURLOPT_TIMEOUT]      = (float)ini_get('default_socket_timeout') ?: 10.0;

        $this->resource = curl_init((string)$this->getUri());
        $this->formatBody($this->stream);

        return $this;
    }

    public function read(): ResponseInterface
    {
        if (empty($this->resource)) {
            throw new RuntimeException('The HTTP client is not opened therefore cannot read anything',
                HttpStatus::PRECONDITION_FAILED);
        }

        if ($this->isMethodSafe() && $this->getBody()->getSize() > 0) {
            return new ServerResponse(
                'failed to open stream: you should not set the message body with safe HTTP methods',
                HttpStatus::BAD_REQUEST
            );
        }

        $this->formatHeader();

        try {
            $content = curl_exec($this->resource);
            $info    = curl_getinfo($this->resource);

            if (curl_errno($this->resource) > 0) {
                return new ServerResponse(
                    curl_error($this->resource), HttpStatus::UNPROCESSABLE_ENTITY, $info['content_type'] ?: 'json'
                );
            }

            return new ServerResponse($content, $info['http_code'], $info['content_type']);
        } catch (Throwable $e) {
            return new ServerResponse($e->getMessage(), HttpStatus::INTERNAL_SERVER_ERROR);
        } finally {
            unset($content, $info);

            if ($this->resource) {
                curl_close($this->resource);
                $this->resource = null;
            }
        }
    }

    public function withBody(StreamInterface $body): HttpRequestClient
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

    private function formatBody(StreamInterface $body): void
    {
        if ($body->getSize() > 0) {
            $content = json_decode($body->getContents() ?: '[]', true);

            $this->options[CURLOPT_POSTFIELDS] = http_build_query($content);
        }
    }

    private function formatHeader(): void
    {
        if ( ! empty($this->headers)) {
            $this->options[CURLOPT_HTTPHEADER] = $this->getFlattenedHeaders();
        }
    }
}