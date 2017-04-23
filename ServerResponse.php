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
use Koded\Http\Interfaces\Response;
use Koded\Stdlib\Mime;

/**
 * Class ServerResponse
 *
 */
class ServerResponse implements Response
{

    use HeaderTrait, MessageTrait;

    private $statusCode   = HttpStatus::OK;
    private $reasonPhrase = 'OK';

    private $contentType = 'text/html';
    private $charset     = 'UTF-8';

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
        $this->assertStatusCode($statusCode);
        $statusCode !== HttpStatus::OK && $this->statusCode = $statusCode;
        $this->reasonPhrase = HttpStatus::CODE[$this->statusCode];
        $this->charset      = $charset;
        $this->contentType  = Mime::type($contentType);

        if ('' !== $content) {
            $this->stream = create_stream($content);
        }

        $this->headers['Content-Type']    = $this->contentType;
        $this->headersMap['content-type'] = 'Content-Type';
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus($code, $reasonPhrase = ''): Response
    {
        $this->assertStatusCode($code);
        $instance = clone $this;

        $instance->statusCode   = (int)$code;
        $instance->reasonPhrase = $reasonPhrase ? (string)$reasonPhrase : HttpStatus::CODE[$code];

        return $instance;
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

    private function assertStatusCode(int $code)
    {
        if ($code < 100 || $code > 599) {
            throw new InvalidArgumentException('Invalid status code, expected range between [100-599]');
        }
    }
}
