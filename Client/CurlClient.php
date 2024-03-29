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

use Koded\Http\{ClientRequest, ServerResponse};
use Koded\Http\Interfaces\{HttpRequestClient, HttpStatus, Response};
use Psr\Http\Message\UriInterface;
use function Koded\Http\create_stream;
use function Koded\Stdlib\json_serialize;

/**
 * @link http://php.net/manual/en/context.curl.php
 */
class CurlClient extends ClientRequest implements HttpRequestClient
{
    use EncodingTrait, Psr18ClientTrait;

    private array $options = [
        CURLOPT_MAXREDIRS      => 20,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_SSL_VERIFYPEER => 1,
        CURLOPT_SSL_VERIFYHOST => 2,
        CURLOPT_USERAGENT      => self::USER_AGENT,
        CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
        CURLOPT_FAILONERROR    => 0,
    ];

    private array $responseHeaders = [];

    public function __construct(
        string $method,
        string|UriInterface $uri,
        string|iterable $body = null,
        array $headers = [])
    {
        parent::__construct($method, $uri, $body, $headers);
        $this->options[CURLOPT_TIMEOUT] = (\ini_get('default_socket_timeout') ?: 10.0) * 1.0;
    }

    public function read(): Response
    {
        if ($resource = $this->assertSafeMethod()) {
            return $resource;
        }
        $this->prepareRequestBody();
        $this->prepareOptions();
        try {
            if (false === $resource = $this->createResource()) {
                return $this->getPhpError(HttpStatus::FAILED_DEPENDENCY,
                    'The HTTP client is not created therefore cannot read anything');
            }
            \curl_setopt_array($resource, $this->options);
            $response = \curl_exec($resource);
            if ($this->hasError($resource)) {
                return $this->getCurlError(HttpStatus::FAILED_DEPENDENCY, $resource);
            }
            return new ServerResponse(
                $response,
                \curl_getinfo($resource, CURLINFO_RESPONSE_CODE),
                $this->responseHeaders);
        } catch (\TypeError $e) {
            return $this->getPhpError(HttpStatus::FAILED_DEPENDENCY, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->getPhpError(HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage());
        } finally {
            unset($response);
        }
    }

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
        $this->options[CURLOPT_SSL_VERIFYHOST] = $value ? 2 : 0;
        return $this;
    }

    public function verifySslPeer(bool $value): HttpRequestClient
    {
        $this->options[CURLOPT_SSL_VERIFYPEER] = $value ? 1 : 0;
        return $this;
    }

    public function withProtocolVersion($version): static
    {
        $instance = parent::withProtocolVersion($version);
        $instance->options[CURLOPT_HTTP_VERSION] =
            ['1.1' => CURL_HTTP_VERSION_1_1,
             '1.0' => CURL_HTTP_VERSION_1_0][$version];
        return $instance;
    }

    protected function createResource(): \CurlHandle|bool
    {
        return \curl_init((string)$this->getUri());
    }

    protected function hasError($resource): bool
    {
        return \curl_errno($resource) > 0;
    }

    protected function prepareOptions(): void
    {
        $this->options[CURLOPT_HEADERFUNCTION] = [$this, 'extractFromResponseHeaders'];
        $this->options[CURLOPT_CUSTOMREQUEST]  = $this->getMethod();
        $this->options[CURLOPT_HTTPHEADER]     = $this->getFlattenedHeaders();
        unset($this->options[CURLOPT_HTTPHEADER][0]); // Host header is always present and first
    }

    protected function prepareRequestBody(): void
    {
        if (!$this->stream->getSize()) {
            return;
        }
        $this->stream->rewind();
        if (0 === $this->encoding) {
            $this->options[CURLOPT_POSTFIELDS] = $this->stream->getContents();
        } elseif ($content = \json_decode($this->stream->getContents() ?: '[]', true)) {
            $this->normalizeHeader('Content-Type', self::X_WWW_FORM_URLENCODED, true);
            $this->options[CURLOPT_POSTFIELDS] = \http_build_query($content, null, '&', $this->encoding);
        }
        $this->stream = create_stream($this->options[CURLOPT_POSTFIELDS]);
    }

    protected function getCurlError(int $status, $resource): Response
    {
        //see https://tools.ietf.org/html/rfc7807
        return new ServerResponse(json_serialize([
            'title'    => \curl_error($resource),
            'detail'   => \curl_strerror(\curl_errno($resource)),
            'instance' => \curl_getinfo($resource, CURLINFO_EFFECTIVE_URL),
            'type'     => 'https://httpstatuses.com/' . $status,
            'status'   => $status,
        ]), $status, ['Content-Type' => 'application/problem+json']);
    }

    /**
     * Extracts the headers from curl response.
     *
     * @param resource $_      curl instance
     * @param string   $header Current header line
     *
     * @return int Header length
     */
    protected function extractFromResponseHeaders($_, string $header): int
    {
        try {
            [$k, $v] = \explode(':', $header, 2) + [1 => null];
            null === $v || $this->responseHeaders[$k] = $v;
        } catch (\Throwable) {
            /** NOOP **/
        } finally {
            return \mb_strlen($header);
        }
    }
}
