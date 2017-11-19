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

/**
 * Holds HTTP status codes with their text.
 *
 */
class HttpStatus {

    const CODE                = [

        // Informational 1xx
        100 => 'Continue',
        101 => 'Switching Protocols',
        102 => 'Processing',
        // 103-199 Unassigned

        // Success 2xx
        200 => 'OK',
        201 => 'Created',
        202 => 'Accepted',
        203 => 'Non-Authoritative Information',
        204 => 'No Content',
        205 => 'Reset Content',
        206 => 'Partial Content',
        207 => 'Multi-Status',
        208 => 'Already Reported',
        // 209-225 Unassigned
        226 => 'IM Used',
        // 227-299 Unassigned

        // Redirection 3xx
        300 => 'Multiple Choices',
        301 => 'Moved Permanently',
        302 => 'Found',
        303 => 'See Other',
        304 => 'Not Modified',
        305 => 'Use Proxy',
        // 306 is deprecated and reserved
        307 => 'Temporary Redirect',
        308 => 'Permanent Redirect',
        // 309-399 Unassigned

        // Client Error 4xx
        400 => 'Bad Request',
        401 => 'Unauthorized',     // Really means "Unauthenticated"
        402 => 'Payment Required',
        403 => 'Forbidden',        // Really means "Unauthorized"
        404 => 'Not Found',
        405 => 'Method Not Allowed',
        406 => 'Not Acceptable',
        407 => 'Proxy Authentication Required',
        408 => 'Request Timeout',
        409 => 'Conflict',
        410 => 'Gone',
        411 => 'Length Required',
        412 => 'Precondition Failed',
        413 => 'Payload Too Large',
        414 => 'URI Too Long',
        415 => 'Unsupported Media Type',
        416 => 'Range Not Satisfiable',
        417 => 'Expectation Failed',
        418 => "I'm teapot",
        // 418-420 Unassigned
        421 => 'Misdirected Request',
        422 => 'Unprocessable Entity',
        423 => 'Locked',
        424 => 'Failed Dependency',
        425 => 'Unordered Collection',
        426 => 'Upgrade Required',
        428 => 'Precondition Required',
        429 => 'Too Many Requests',
        // 430 Unassigned
        431 => 'Request Header Fields Too Large',
        // 432-499 Unassigned

        // Server Error 5xx
        500 => 'Internal Server Error',
        501 => 'Not Implemented',
        502 => 'Bad Gateway',
        503 => 'Service Unavailable',
        504 => 'Gateway Timeout',
        505 => 'HTTP Version Not Supported',
        506 => 'Variant Also Negotiates',
        507 => 'Insufficient Storage',
        508 => 'Loop Detected',
        509 => 'Bandwidth Limit Exceeded',
        510 => 'Not Extended',
        511 => 'Network Authentication Required',
        // 512-599 Unassigned
    ];

    // Informational 1xx
    const CONTINUE            = 100;
    const SWITCHING_PROTOCOLS = 101;
    const PROCESSING          = 102;
    // 103-199 Unassigned

    // Success 2xx
    const OK                            = 200;
    const CREATED                       = 201;
    const ACCEPTED                      = 202;
    const NON_AUTHORITATIVE_INFORMATION = 203;
    const NO_CONTENT                    = 204;
    const RESET_CONTENT                 = 205;
    const PARTIAL_CONTENT               = 206;
    const MULTI_STATUS                  = 207;
    const ALREADY_REPORTED              = 208;
    // 209-225 Unassigned
    const IM_USED = 226;
    // 227-299 Unassigned

    // Redirection 3xx
    const MULTIPLE_CHOICES  = 300;
    const MOVED_PERMANENTLY = 301;
    const FOUND             = 302;
    const SEE_OTHER         = 303;
    const NOT_MODIFIED      = 304;
    const USE_PROXY         = 305;
    // 306 is deprecated and reserved
    const TEMPORARY_REDIRECT = 307;
    const PERMANENT_REDIRECT = 308;
    // 309-399 Unassigned

    // Client Error 4xx
    const BAD_REQUEST                   = 400;
    const UNAUTHORIZED                  = 401;
    const PAYMENT_REQUIRED              = 402;
    const FORBIDDEN                     = 403;
    const NOT_FOUND                     = 404;
    const METHOD_NOT_ALLOWED            = 405;
    const NOT_ACCEPTABLE                = 406;
    const PROXY_AUTHENTICATION_REQUIRED = 407;
    const REQUEST_TIMEOUT               = 408;
    const CONFLICT                      = 409;
    const GONE                          = 410;
    const LENGTH_REQUIRED               = 411;
    const PRECONDITION_FAILED           = 412;
    const PAYLOAD_TOO_LARGE             = 413;
    const URI_TOO_LONG                  = 414;
    const UNSUPPORTED_MEDIA_TYPE        = 415;
    const RANGE_NOT_SATISFIABLE         = 416;
    const EXPECTATION_FAILED            = 417;
    const I_AM_TEAPOT                   = 418;
    // 418-420 Unassigned
    const MISDIRECTED_REQUEST   = 421;
    const UNPROCESSABLE_ENTITY  = 422;
    const LOCKED                = 423;
    const FAILED_DEPENDENCY     = 424;
    const UNORDERED_COLLECTION  = 425;
    const UPGRADE_REQUIRED      = 426;
    const PRECONDITION_REQUIRED = 428;
    const TOO_MANY_REQUESTS     = 429;
    // 430 Unassigned
    const REQUEST_HEADER_FIELDS_TOO_LARGE = 431;
    // 432-499 Unassigned

    // Server Error 5xx
    const INTERNAL_SERVER_ERROR                = 500;
    const NOT_IMPLEMENTED                      = 501;
    const BAD_GATEWAY                          = 502;
    const SERVICE_UNAVAILABLE                  = 503;
    const GATEWAY_TIMEOUT                      = 504;
    const VERSION_NOT_SUPPORTED                = 505;
    const VARIANT_ALSO_NEGOTIATES              = 506;
    const INSUFFICIENT_STORAGE                 = 507;
    const LOOP_DETECTED                        = 508;
    const BANDWIDTH_LIMIT_EXCEEDED             = 509;
    const NOT_EXTENDED                         = 510;
    const HTTP_NETWORK_AUTHENTICATION_REQUIRED = 511;
    // 512-599 Unassigned

    /**
     * @param string $code
     * @param mixed  $withCode [optional]
     *
     * @return string The status code
     */
    public static function __callStatic(string $code, $withCode = false) {
        $status = constant("self::$code");

        return ($withCode ? $status . ' ' : '') . self::CODE[$status];
    }
}
