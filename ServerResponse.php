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

use Koded\Http\Interfaces\{HttpStatus, Response};
use InvalidArgumentException;
use JsonSerializable;
use function in_array;
use function join;
use function sprintf;
use function strtoupper;

/**
 * Class ServerResponse
 *
 */
class ServerResponse implements Response, JsonSerializable
{
    use HeaderTrait, MessageTrait, CookieTrait, JsonSerializeTrait;

    private const E_CLIENT_RESPONSE_SEND = 'Cannot send the client response.';
    private const E_INVALID_STATUS_CODE  = 'Invalid status code %s, expected range between [100-599]';

    protected int    $statusCode   = HttpStatus::OK;
    protected string $reasonPhrase = 'OK';

    /**
     * ServerResponse constructor.
     *
     * @param mixed $content    [optional]
     * @param int   $statusCode [optional]
     * @param array $headers    [optional]
     */
    public function __construct(
        mixed $content = '',
        int $statusCode = HttpStatus::OK,
        array $headers = [])
    {
        $this->setStatus($this, $statusCode);
        $this->setHeaders($headers);
        $this->stream = create_stream($content);
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): static
    {
        return $this->setStatus(clone $this, $code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getContentType(): string
    {
        return $this->getHeaderLine('Content-Type') ?: 'text/html';
    }

    public function sendHeaders(): void
    {
        $this->prepareResponse();
        if (false === headers_sent()) {
            foreach ($this->getHeaders() as $name => $values) {
                header($name . ':' . join(',', (array)$values), false, $this->statusCode);
            }
            // Status header
            header(sprintf('HTTP/%s %d %s',
                $this->getProtocolVersion(),
                $this->getStatusCode(),
                $this->getReasonPhrase()),
                true,
                $this->statusCode);
        }
    }

    public function sendBody(): string
    {
        try {
            return (string)$this->stream;
        } finally {
            $this->stream->close();
        }
    }

    public function send(): string
    {
        $this->sendHeaders();
        return $this->sendBody();
    }

    protected function setStatus(ServerResponse $instance, int $statusCode, string $reasonPhrase = ''): ServerResponse
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                sprintf(self::E_INVALID_STATUS_CODE, $statusCode), HttpStatus::UNPROCESSABLE_ENTITY
            );
        }
        $instance->statusCode   = $statusCode;
        $instance->reasonPhrase = $reasonPhrase ?: HttpStatus::CODE[$statusCode];
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
            $this->normalizeHeader('Content-Length', (string)$size, true);
        }
        $method = strtoupper($_SERVER['REQUEST_METHOD'] ?? '');
        if ('HEAD' === $method || 'OPTIONS' === $method) {
            $this->stream = create_stream(null);
        }
        if ($this->hasHeader('Transfer-Encoding') || !$size) {
            unset($this->headersMap['content-length'], $this->headers['Content-Length']);
        }
    }
}
