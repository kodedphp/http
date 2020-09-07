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

use Exception;
use Koded\Http\Interfaces\{HttpRequestClient, HttpStatus};
use Psr\Http\Client\ClientExceptionInterface;

trait EncodingTrait
{
    private int $encoding = PHP_QUERY_RFC3986;

    public function withEncoding(int $type): HttpRequestClient
    {
        if (in_array($type, [0, PHP_QUERY_RFC1738, PHP_QUERY_RFC3986], true)) {
            $this->encoding = $type;
            return $this;
        }
        throw new class(
            'Invalid encoding type. Expects 0, PHP_QUERY_RFC1738 or PHP_QUERY_RFC3986',
            HttpStatus::BAD_REQUEST
        ) extends Exception implements ClientExceptionInterface {};
    }
}
