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

namespace Koded\Http\Interfaces;

use Psr\Http\Message\{
    RequestInterface, ResponseInterface, ServerRequestInterface
};

interface Request extends RequestInterface, ServerRequestInterface
{

    const GET     = 'GET';
    const POST    = 'POST';
    const PUT     = 'PUT';
    const DELETE  = 'DELETE';
    const HEAD    = 'HEAD';
    const PATCH   = 'PATCH';
    const OPTIONS = 'OPTIONS';
    const CONNECT = 'CONNECT';
    const TRACE   = 'TRACE';

    const HTTP_METHODS = [
        self::GET,
        self::POST,
        self::PUT,
        self::PATCH,
        self::DELETE,
        self::HEAD,
        self::OPTIONS,
        self::TRACE,
        self::CONNECT
    ];

    /**
     * Returns the path part of the URI.
     *
     * This method should defeat the UriInterface::getPath() idea for returning
     * an empty path, by trying to provide a useful absolute "/" path.
     *
     * @return string URI path
     */
    public function getPath(): string;

    /**
     * Returns the schema and server name/address.
     * The port is omitted if it's a standard port.
     *
     * @return string If the schema is not set, returns an empty string.
     */
    public function getBaseuri(): string;

    /**
     * @param array $attributes Sets all attributes in the request object
     *
     * @return Request A new immutable response instance
     */
    public function withAttributes(array $attributes): Request;

    /**
     * Checks if the incoming request is HTTPS.
     *
     * @return bool
     */
    public function isSecure(): bool;

    /**
     * Checks if HTTP method is "safe" according to RFC 2414
     *
     * @return bool
     */
    public function isMethodSafe(): bool;

    /**
     * Checks if the request is AJAX.
     *
     * @return bool
     */
    public function isXHR(): bool;
}

interface Response extends ResponseInterface
{

    /**
     * Returns the mime type value for the response object.
     *
     * @return string The response mime type
     */
    public function getContentType(): string;

    /**
     * Returns the charset value for the response object.
     *
     * @return string
     */
    public function getCharset(): string;
}
