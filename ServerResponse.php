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
use JsonSerializable;
use Koded\Http\Interfaces\{HttpStatus, Request, Response};

/**
 * Class ServerResponse
 *
 */
class ServerResponse implements Response, JsonSerializable
{
    use HeaderTrait, MessageTrait, CookieTrait, JsonSerializeTrait;

    private const E_CLIENT_RESPONSE_SEND = 'Cannot send the client response.';
    private const E_INVALID_STATUS_CODE  = 'Invalid status code %s, expected range between [100-599]';

    protected $statusCode   = HttpStatus::OK;
    protected $reasonPhrase = 'OK';

    /**
     * ServerResponse constructor.
     *
     * @param mixed $content    [optional]
     * @param int   $statusCode [optional]
     * @param array $headers    [optional]
     */
    public function __construct($content = '', int $statusCode = HttpStatus::OK, array $headers = [])
    {
        $this->setStatus($this, $statusCode);
        $this->setHeaders($headers);
        $this->stream = create_stream($content);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): Response
    {
        return $this->setStatus(clone $this, (int)$code, (string)$reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return (string)$this->reasonPhrase;
    }

    public function getContentType(): string
    {
        return $this->getHeaderLine('Content-Type') ?: 'text/html';
    }

    public function send(): string
    {
        $this->prepareResponse();

        if (headers_sent()) {
            return (string)$this->stream;
        }

        // Headers
        foreach ($this->getHeaders() as $name => $values) {
            header($name . ':' . join(',', (array)$values), false, $this->statusCode);
        }

        // Status header
        header(sprintf('HTTP/%s %d %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getReasonPhrase()),
            true, $this->statusCode
        );

        return (string)$this->stream;
    }

    protected function setStatus(ServerResponse $instance, int $statusCode, string $reasonPhrase = ''): ServerResponse
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                sprintf(self::E_INVALID_STATUS_CODE, $statusCode), HttpStatus::UNPROCESSABLE_ENTITY
            );
        }

        $instance->statusCode   = (int)$statusCode;
        $instance->reasonPhrase = $reasonPhrase ? (string)$reasonPhrase : StatusCode::CODE[$statusCode];

        return $instance;
    }

    protected function prepareResponse(): void
    {
        if (in_array($this->getStatusCode(), [100, 101, 102, 204, 304])) {
            $this->stream = create_stream(null);
            unset($this->headersMap['content-length'], $this->headers['Content-Length']);
            unset($this->headersMap['content-type'], $this->headers['Content-Type']);

            return;
        }

        if ($size = $this->stream->getSize()) {
            $this->normalizeHeader('Content-Length', $size, true);
        }

        if (Request::HEAD === strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
            $this->stream = create_stream(null);
        }

        if ($this->hasHeader('Transfer-Encoding') || !$size) {
            unset($this->headersMap['content-length'], $this->headers['Content-Length']);
        }
    }
}
