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
use Koded\Http\Interfaces\{HttpMethod, HttpRequestClient, HttpStatus, Response};
use Psr\Http\Message\UriInterface;
use function Koded\Http\create_stream;

/**
 * @link http://php.net/manual/en/context.http.php
 */
class PhpClient extends ClientRequest implements HttpRequestClient
{
    use EncodingTrait, Psr18ClientTrait;

    /** @var array Stream context options */
    private array $options = [
        'protocol_version' => 1.1,
        'user_agent'       => self::USER_AGENT,
        'method'           => 'GET',
        'max_redirects'    => 20,
        'follow_location'  => 1,
        'ignore_errors'    => true,
        'request_fulluri'  => true,
        'ssl'              => [
            'verify_peer'       => true,
            'allow_self_signed' => false,
        ]
    ];

    public function __construct(
//        string $method,
        HttpMethod $method,
        string|UriInterface $uri,
        string|iterable $body = null,
        array $headers = [])
    {
        parent::__construct($method, $uri, $body, $headers);
        $this->options['timeout'] = (\ini_get('default_socket_timeout') ?: 10.0) * 1.0;
    }

    public function read(): Response
    {
        if ($resource = $this->assertSafeMethod()) {
            return $resource;
        }
        $this->prepareRequestBody();
        $this->prepareOptions();
        try {
            $resource = $this->createResource(\stream_context_create(['http' => $this->options]));
            if ($this->hasError($resource)) {
                return $this->getPhpError(HttpStatus::FAILED_DEPENDENCY,
                    'The HTTP client is not created therefore cannot read anything');
            }
            return new ServerResponse(
                \stream_get_contents($resource),
                ...$this->extractStatusAndHeaders($resource));
        } catch (\ValueError $e) {
            return $this->getPhpError(HttpStatus::FAILED_DEPENDENCY, $e->getMessage());
        } catch (\Throwable $e) {
            return $this->getPhpError(HttpStatus::INTERNAL_SERVER_ERROR, $e->getMessage());
        } finally {
            if (\is_resource($resource)) {
                \fclose($resource);
            }
        }
    }

    public function userAgent(string $value): HttpRequestClient
    {
        $this->options['user_agent'] = $value;
        return $this;
    }

    public function followLocation(bool $value): HttpRequestClient
    {
        $this->options['follow_location'] = (int)$value;
        return $this;
    }

    public function maxRedirects(int $value): HttpRequestClient
    {
        $this->options['max_redirects'] = $value;
        return $this;
    }

    public function timeout(float $value): HttpRequestClient
    {
        $this->options['timeout'] = $value * 1.0;
        return $this;
    }

    public function ignoreErrors(bool $value): HttpRequestClient
    {
        $this->options['ignore_errors'] = $value;
        return $this;
    }

    public function verifySslHost(bool $value): HttpRequestClient
    {
        $this->options['ssl']['allow_self_signed'] = $value;
        return $this;
    }

    public function verifySslPeer(bool $value): HttpRequestClient
    {
        $this->options['ssl']['verify_peer'] = $value;
        return $this;
    }

    /**
     * NOTE: if Content-Type is not provided
     * fopen() will assume application/x-www-form-urlencoded
     *
     * @param resource $context from stream_context_create()
     *
     * @return resource|bool
     */
    protected function createResource($context)
    {
        return @\fopen((string)$this->getUri(), 'rb', false, $context);
    }

    protected function prepareRequestBody(): void
    {
        if (!$this->stream->getSize()) {
            return;
        }
        $this->stream->rewind();
        if (0 === $this->encoding) {
            $this->options['content'] = $this->stream->getContents();
        } elseif ($content = \json_decode($this->stream->getContents() ?: '[]', true)) {
            $this->normalizeHeader('Content-Type', self::X_WWW_FORM_URLENCODED, true);
            $this->options['content'] = \http_build_query($content, '', '&', $this->encoding);
        }
        $this->stream = create_stream($this->options['content']);
    }

    protected function hasError($resource): bool
    {
        return false === \is_resource($resource);
    }

    protected function prepareOptions(): void
    {
        $this->options['method'] = $this->getMethod();
        $this->options['header'] = $this->getFlattenedHeaders();
        unset($this->options['header'][0]); // Host header is always present and first
    }

    /**
     * Extracts the headers and status code from the response.
     *
     * @param resource $resource The resource from fopen()
     * @return array Status code and headers
     */
    protected function extractStatusAndHeaders($resource): array
    {
        try {
            $headers = [];
            $meta = \stream_get_meta_data($resource)['wrapper_data'] ?? [];
            /* HTTP status may not always be the first header in the response headers,
             * for example, if the stream follows one or multiple redirects, the last
             * status line is what is expected here.
             */
            $status = \array_filter($meta, fn(string $header) => \str_starts_with($header, 'HTTP/'));
            $status = \array_pop($status) ?: 'HTTP/1.1 200 OK';
            $status = (int)(\explode(' ', $status)[1] ?? HttpStatus::OK);
            foreach ($meta as $header) {
                [$k, $v] = \explode(':', $header, 2) + [1 => null];
                if (null === $v) {
                    continue;
                }
                $headers[$k] = $v;
            }
            return [$status, $headers];
        } finally {
            unset($meta, $header, $k, $v);
        }
    }
}
