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

use Koded\Http\Interfaces\HttpStatus;
use Koded\Stdlib\Serializer\JsonSerializer;

/**
 * HTTP response object for JSON format.
 */
class JsonResponse extends ServerResponse
{
    public function __construct(
        mixed $content = null,
        int $statusCode = HttpStatus::OK,
        array $headers = [])
    {
        parent::__construct(
            $this->process($content),
            $statusCode,
            $headers);
    }

    /**
     * Converts the <, >, ', & and " to UTF-8 variants.
     * The content is safe for embedding it into HTML.
     *
     * @return JsonResponse
     */
    public function safe(): JsonResponse
    {
        $this->stream = create_stream(\json_encode(
            \json_decode($this->stream->getContents(), true),
            JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
        ));
        return $this;
    }

    public function getContentType(): string
    {
        return $this->getHeaderLine('Content-Type') ?: 'application/json';
    }

    private function process(mixed $content): mixed
    {
        if (\is_array($content)) {
            return \json_encode($content, JsonSerializer::OPTIONS);
        }
        if (\is_iterable($content)) {
            return \json_encode(\iterator_to_array($content), JsonSerializer::OPTIONS);
        }
        return $content;
    }
}
