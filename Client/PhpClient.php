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
use Throwable;
use function Koded\Http\create_stream;

/**
 * @link http://php.net/manual/en/context.http.php
 */
class PhpClient extends ClientRequest implements HttpRequestClient
{
    use EncodingTrait, Psr18ClientTrait;

    /** @var array Stream context options */
    private $options = [
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

    public function __construct(string $method, $uri, $body = null, array $headers = [])
    {
        parent::__construct($method, $uri, $body, $headers);
        $this->options['timeout'] = (ini_get('default_socket_timeout') ?: 10.0) * 1.0;
    }

    public function read(): Response
    {
        if ($resource = $this->assertSafeMethod()) {
            return $resource;
        }

        $this->prepareRequestBody();
        $this->prepareOptions();

        try {
            if (false === $resource = $this->createResource(stream_context_create(['http' => $this->options]))) {
                return new ServerResponse(error_get_last()['message'], HttpStatus::FAILED_DEPENDENCY);
            }

            $this->extractFromResponseHeaders($resource, $headers, $statusCode);

            return new ServerResponse(
                stream_get_contents($resource),
                $statusCode,
                $headers
            );
        } catch (Throwable $e) {
            return new ServerResponse($e->getMessage(), HttpStatus::INTERNAL_SERVER_ERROR);
        } finally {
            if (is_resource($resource)) {
                fclose($resource);
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
     * @param resource $context from stream_context_create()
     *
     * @return bool|resource
     */
    protected function createResource($context)
    {
        return @fopen((string)$this->getUri(), 'r', false, $context);
    }

    protected function prepareRequestBody(): void
    {
        if (!$this->stream->getSize()) {
            return;
        }

        $this->stream->rewind();

        if (0 === $this->encoding) {
            $this->options['content'] = $this->stream->getContents();
        } elseif ($content = json_decode($this->stream->getContents() ?: '[]', true)) {
            $this->normalizeHeader('Content-Type', self::X_WWW_FORM_URLENCODED, true);
            $this->options['content'] = http_build_query($content, null, '&', $this->encoding);
        }

        $this->stream = create_stream($this->options['content']);
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
     * @param resource $response   The resource from fopen()
     * @param array    $headers    Parsed response headers
     * @param int      $statusCode Response status code
     */
    protected function extractFromResponseHeaders($response, &$headers, &$statusCode): void
    {
        try {
            $_headers   = stream_get_meta_data($response)['wrapper_data'];
            $statusCode = array_filter($_headers, function(string $header) {
                return false !== stripos($header, 'HTTP/', 0);
            });
            $statusCode = array_pop($statusCode) ?: 'HTTP/1.1 200 OK';
            $statusCode = (int)(explode(' ', $statusCode)[1] ?? HttpStatus::OK);

            foreach ($_headers as $header) {
                [$k, $v] = explode(':', $header, 2) + [1 => null];
                if (null === $v) {
                    continue;
                }
                $headers[$k] = $v;
            }
        } finally {
            unset($_headers, $header, $k, $v);
        }
    }
}
