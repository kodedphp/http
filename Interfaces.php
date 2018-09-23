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

use Koded\Stdlib\Interfaces\Data;
use Psr\Http\Message\{RequestInterface, ResponseInterface, ServerRequestInterface};


interface Request extends ServerRequestInterface, ValidatableRequest, ExtendedMessageInterface
{

    /* RFC 7231, 5789 methods */
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
        self::CONNECT,
    ];

    const SAFE_METHODS = [
        self::GET,
        self::HEAD,
        self::OPTIONS,
        self::TRACE,
        self::CONNECT
    ];

    /* RFC 3253 methods */
    const CHECKIN         = 'CHECKIN';
    const CHECKOUT        = 'CHECKOUT';
    const REPORT          = 'REPORT';
    const UNCHECKIN       = 'UNCHECKIN';
    const UPDATE          = 'UPDATE';
    const VERSION_CONTROL = 'VERSION-CONTROL';

    const WEBDAV_METHODS = [
        self::CHECKIN,
        self::CHECKOUT,
        self::REPORT,
        self::UNCHECKIN,
        self::UPDATE,
        self::VERSION_CONTROL,
    ];

    /**
     * Returns the absolute path part of the URI.
     *
     * This method does not follow the UriInterface::getPath() idea
     * for returning an empty path, but trying to provide a useful absolute path.
     * There is not much use of an empty things.
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
    public function getBaseUri(): string;

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


interface Response extends ResponseInterface, ExtendedMessageInterface
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


interface HttpRequestClient extends RequestInterface
{

    const USER_AGENT = 'Koded/HttpClient (+https://github.com/kodedphp/http)';

    /**
     * Fetch the internet resource using the HTTP client.
     *
     * Error response codes:
     *
     *  - 400 on bad request when you execute body-less request with non empty body
     *  - 412 when client is not opened before reading
     *  - 422 when client dropped an error on the resource fetching
     *  - 500 on whatever code error
     *
     * @return ResponseInterface Response object with populated resource.
     *                           It can return an HTTP response with error status.
     */
    public function read(): ResponseInterface;

    /**
     * @param string $value
     *
     * @return HttpRequestClient
     */
    public function userAgent(string $value): HttpRequestClient;

    /**
     * Follow Location header redirects.
     *
     * @param bool $value Default is TRUE
     *
     * @return HttpRequestClient
     */
    public function followLocation(bool $value): HttpRequestClient;

    /**
     * The max number of redirects to follow. Value 1 or less means that no redirects are followed.
     *
     * @param int $value Default is 20
     *
     * @return HttpRequestClient
     */
    public function maxRedirects(int $value): HttpRequestClient;

    /**
     * Read timeout in seconds.
     *
     * This method should use the "default_socket_timeout" php.ini setting (usually 60).
     * or 10 if this value is not set in the ini.php
     *
     * @param float $value
     *
     * @return HttpRequestClient
     */
    public function timeout(float $value): HttpRequestClient;

    /**
     * Fetch the content even on failure status codes.
     *
     * @param bool $value Default is false
     *
     * @return HttpRequestClient
     */
    public function ignoreErrors(bool $value): HttpRequestClient;

    /**
     * @param bool $value
     *
     * @return HttpRequestClient
     */
    public function verifySslHost(bool $value): HttpRequestClient;

    /**
     * @param bool $value
     *
     * @return HttpRequestClient
     */
    public function verifySslPeer(bool $value): HttpRequestClient;
}


interface HttpInputValidator
{

    /**
     * Validates the provided data with custom rules specific to
     * some application logic and implementation.
     *
     * @param Data $input The data to be validated
     *
     * @return array Should return an empty array if validation has passed,
     *               or a nested data with explanation what failed.
     */
    public function validate(Data $input): array;
}


interface ExtendedMessageInterface
{
    /**
     * Bulk set the headers.
     *
     * @param array $headers name => value
     *
     * @return Request | Response
     */
    public function withHeaders(array $headers);
}


interface ValidatableRequest
{

    /**
     * Validates the request body using a concrete validation instance.
     *
     * @return Response|null Should return a NULL if validation has passed,
     * or a Response object with status code 400 and explanation what failed
     */
    public function validate(HttpInputValidator $validator): ?Response;
}