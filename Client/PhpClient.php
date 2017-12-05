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
use Throwable;

class PhpClient extends ClientRequest implements HttpRequestClient
{

    /**
     * @var array Stream context options
     * @link http://php.net/manual/en/context.http.php
     */
    private $options = [];

    public function open(): HttpRequestClient
    {
        $this->options = [
            'protocol_version' => (float)$this->getProtocolVersion(),
            'user_agent'       => HttpRequestClient::USER_AGENT,
            'method'           => $this->getMethod(),
            'timeout'          => (ini_get('default_socket_timeout') ?: 10.0) * 1.0,
            'max_redirects'    => 20,
            'follow_location'  => 1,
            'ignore_errors'    => false,
            'ssl'              => [
                'verify_peer'       => false,
                'allow_self_signed' => false,
            ]
        ];

        return $this;
    }

    /**
     * @return ResponseInterface
     */
    public function read(): ResponseInterface
    {
        $this->formatBody();

        if ($response = $this->assertSafeMethods()) {
            return $response;
        }

        $this->formatHeader();

        try {
            $context = stream_context_create(['http' => $this->options]);
            if (false === $response = $this->createResource($context)) {
                return new ServerResponse(error_get_last()['message'], HttpStatus::UNPROCESSABLE_ENTITY);
            }

            [$statusCode, $contentType] = $this->extractFromHeaders($http_response_header ?? []);

            return new ServerResponse(stream_get_contents($response), $statusCode, $contentType);
        } catch (Throwable $e) {
            return new ServerResponse($e->getMessage(), HttpStatus::INTERNAL_SERVER_ERROR);
        } finally {
            if (is_resource($response)) {
                fclose($response);
            }

            unset($context, $response);
        }
    }

    public function setUserAgent(string $value): HttpRequestClient
    {
        $this->options['user_agent'] = $value;

        return $this;
    }

    public function setFollowLocation(bool $value): HttpRequestClient
    {
        $this->options['follow_location'] = (int)$value;

        return $this;
    }

    public function setMaxRedirects(int $value): HttpRequestClient
    {
        $this->options['max_redirects'] = $value;

        return $this;
    }

    public function setTimeout(float $value): HttpRequestClient
    {
        $this->options['timeout'] = $value;

        return $this;
    }

    public function setIgnoreErrors(bool $value): HttpRequestClient
    {
        $this->options['ignore_errors'] = $value;

        return $this;
    }

    public function setVerifySslHost(bool $value): HttpRequestClient
    {
        $this->options['ssl']['allow_self_signed'] = $value;

        return $this;
    }

    public function setVerifySslPeer(bool $value): HttpRequestClient
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

    private function formatHeader(): void
    {
        if (!empty($this->headers)) {
            $this->options['header'] = join("\r\n", $this->getFlattenedHeaders()) . "\r\n";
        }
    }

    private function formatBody(): void
    {
        if ($content = json_decode($this->getBody()->getContents() ?: '[]', true)) {
            $this->options['content'] = http_build_query($content);
        }
    }

    /**
     * Extracts the status code and content type from response headers.
     *
     * @param array $headers Response headers
     *
     * @return array [statusCode, contentType]
     */
    private function extractFromHeaders(array $headers): array
    {
        $statusCode  = explode(' ', $headers[0] ?? ' 200 ')[1];
        $contentType = 'text/html';

        if ($found = array_filter($headers, function($header) {
            return false !== stripos($header, 'Content-Type');
        })) {
            $contentType = explode(':', current($found))[1];
        }

        return [(int)$statusCode, trim($contentType)];
    }
}
