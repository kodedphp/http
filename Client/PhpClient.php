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
use Koded\Http\Interfaces\{HttpRequestClient, Response};
use Throwable;


class PhpClient extends ClientRequest implements HttpRequestClient
{
    /**
     * @var array Stream context options
     * @link http://php.net/manual/en/context.http.php
     */
    private $options = [];

    public function __construct(string $method, $uri, $body = null, array $headers = [])
    {
        parent::__construct($method, $uri, $body, $headers);

        $this->options = [
            'protocol_version' => (float)$this->getProtocolVersion(),
            'user_agent'       => HttpRequestClient::USER_AGENT,
            'method'           => $this->getMethod(),
            'timeout'          => (ini_get('default_socket_timeout') ?: 10.0) * 1.0,
            'max_redirects'    => 20,
            'follow_location'  => 1,
            'ignore_errors'    => true,
            'request_fulluri'  => true,
            'header'           => $this->getFlattenedHeaders(),
            'ssl'              => [
                'verify_peer'       => false,
                'allow_self_signed' => false,
            ]
        ];

        $this->prepareRequestBody();
    }


    public function read(): Response
    {
        if ($response = $this->assertSafeMethods()) {
            return $response;
        }

        try {
            if (false === $response = $this->createResource(stream_context_create(['http' => $this->options]))) {
                return new ServerResponse(error_get_last()['message'], StatusCode::FAILED_DEPENDENCY);
            }

            $this->extractFromResponseHeaders($response, $statusCode);

            return new ServerResponse(stream_get_contents($response), $statusCode, $this->getHeaders());
        } catch (Throwable $e) {
            return new ServerResponse($e->getMessage(), StatusCode::INTERNAL_SERVER_ERROR);
        } finally {
            if (is_resource($response)) {
                fclose($response);
            }

            unset($response);
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
        $this->stream->seek(0);

        if ($content = json_decode($this->stream->getContents() ?: '[]', true)) {
            $this->options['content'] = http_build_query($content);
        }
    }

    /**
     * Extracts the Content-Type and status code from the response
     * and sets the ServerResponse headers accordingly.
     *
     * @param resource $response The resource from fopen()
     * @param int      $statusCode
     */
    protected function extractFromResponseHeaders($response, &$statusCode): void
    {
        $headers    = stream_get_meta_data($response)['wrapper_data'];
        $statusCode = 200;

        $extracted = array_filter($headers, function($header) {
            return false !== stripos($header, 'Content-Type')
                || false !== stripos($header, 'HTTP/');
        });

        foreach ($extracted as $header) {
            try {
                [$k, $v] = explode(':', $header, 2);
                $this->headersMap[strtolower($k)] = $k;
                $this->headers[$k]                = trim($v);
            } catch (Throwable $e) {
                if (false !== stripos($header, 'HTTP/')) {
                    /*
                     * (Response) Headers can contain more than one status header;
                     * ex: before redirection, some more and finally the last after redirection
                     *
                     */
                    $statusCode = explode(' ', $header)[1] ?? 200;
                } else {
                    continue;
                }
            }
        }
    }
}
