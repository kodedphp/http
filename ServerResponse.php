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
use Koded\Http\Interfaces\Request;
use Koded\Http\Interfaces\Response;
use Koded\Stdlib\Mime;

/**
 * Class ServerResponse
 *
 */
class ServerResponse implements Response
{

    use HeaderTrait, MessageTrait;

    const E_INVALID_STATUS_CODE = 'Invalid status code %s, expected range between [100-599]';

    protected $statusCode   = HttpStatus::OK;
    protected $reasonPhrase = 'OK';

    protected $contentType = 'text/html';
    protected $charset     = 'UTF-8';

    /**
     * ServerResponse constructor.
     *
     * @param string $content     [optional]
     * @param int    $statusCode  [optional]
     * @param string $contentType [optional]
     * @param string $charset     [optional]
     */
    public function __construct(
        string $content = '',
        int $statusCode = HttpStatus::OK,
        string $contentType = '',
        string $charset = 'UTF-8'
    ) {
        $this->stream = create_stream($content);

//        $this->assertStatusCode($statusCode);
//        $this->statusCode = $statusCode;
//        $this->reasonPhrase = HttpStatus::CODE[$statusCode];

        $this->setStatus($this, $statusCode);

        $this->contentType = false === strpos($contentType, '/') ? Mime::type($contentType) : $contentType;
        $this->normalizeHeader('Content-Type', [$this->contentType], true);

//        $this->headers['Content-Type']    = [$this->contentType];
//        $this->headersMap['content-type'] = 'Content-Type';

        $this->charset = $charset;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): Response
    {
        return $this->setStatus(clone $this, $code, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        return $this->reasonPhrase;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }

    public function getCharset(): string
    {
        return $this->charset;
    }

    public function send(): string
    {
        $this->normalizeHeader('Content-Length', [$this->stream->getSize()], true);

        if (Request::HEAD === strtoupper($_SERVER['REQUEST_METHOD'] ?? '')) {
            $this->stream = create_stream(null);
        }

        if (in_array($this->getStatusCode(), [100, 101, 102, 204, 304])) {
            unset($this->headersMap['content-length'], $this->headers['Content-Length']);
            $this->stream = create_stream(null);
        }

        header(sprintf('HTTP/%s %d %s', $this->getProtocolVersion(), $this->getStatusCode(), $this->getReasonPhrase()),
            true, $this->getStatusCode()
        );

        foreach ($this->getHeaders() as $name => $values) {
            header($name . ':' . join(', ', (array)$values));
        }

        $this->stream->rewind();

        return $this->stream->getContents();
    }

    protected function setStatus(ServerResponse $instance, int $statusCode, string $reasonPhrase = ''): ServerResponse
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(
                sprintf(self::E_INVALID_STATUS_CODE, $statusCode), HttpStatus::UNPROCESSABLE_ENTITY
            );
        }

        $instance->statusCode   = (int)$statusCode;
        $instance->reasonPhrase = $reasonPhrase ? (string)$reasonPhrase : HttpStatus::CODE[$statusCode];

        return $instance;
    }
}
