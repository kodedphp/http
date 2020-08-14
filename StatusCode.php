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

use Exception;
use Koded\Http\Interfaces\HttpStatus;

/**
 * Holds HTTP status codes with their text.
 *
 * @link https://httpstatuses.com
 */
class StatusCode implements HttpStatus
{
    /**
     * @param int $code
     *
     * @return string
     */
    public static function description(int $code): string
    {
        return [
                    // 4xx

                    self::BAD_REQUEST                     => 'The response means that server cannot understand the request due to invalid syntax',
                    self::UNAUTHORIZED                    => 'The request has not been applied because it lacks valid authentication credentials for the target resource',
                    self::FORBIDDEN                       => 'Client does not have access rights to the content, so the server refuses to authorize it',
                    self::NOT_FOUND                       => 'Server cannot find the requested resource',
                    self::METHOD_NOT_ALLOWED              => 'The request method is known by the server, but has been disabled and cannot be used',
                    self::NOT_ACCEPTABLE                  => 'The target resource does not have a current representation that would be acceptable to the user agent, according to the proactive negotiation header fields received in the request, and the server is unwilling to supply a default representation',
                    self::PROXY_AUTHENTICATION_REQUIRED   => 'Similar to 401 Unauthorized, but it indicates that the client needs to authenticate itself in order to use a proxy (Proxy-Authenticate request header)',
                    self::REQUEST_TIMEOUT                 => 'The server did not receive a complete request message within the time that it was prepared to wait',
                    self::CONFLICT                        => 'The request could not be completed due to a conflict with the current state of the target resource',
                    self::GONE                            => 'The target resource is no longer available at the origin server and that this condition is likely to be permanent',
                    self::LENGTH_REQUIRED                 => 'The server refuses to accept the request without a defined Content-Length',
                    self::PRECONDITION_FAILED             => 'The client sent preconditions in the request headers that failed to evaluate on the server',
                    self::PAYLOAD_TOO_LARGE               => 'The server is refusing to process a request because the request payload is larger than the server is willing or able to process',
                    self::REQUEST_URI_TOO_LONG            => 'The server is refusing to service the request because the request-target is longer than the server is willing to interpret',
                    self::UNSUPPORTED_MEDIA_TYPE          => 'The media format of the requested data is not supported by the server',
                    self::EXPECTATION_FAILED              => 'The expectation given in the Expect request header could not be met by at least one of the inbound servers',
                    self::I_AM_TEAPOT                     => 'Any attempt to brew coffee with a teapot should result in the error code "418 I\'m a teapot". The resulting entity body MAY be short and stout',
                    self::MISDIRECTED_REQUEST             => 'The request was directed at a server that is not able to produce a response. This can be sent by a server that is not configured to produce responses for the combination of scheme and authority that are included in the request URI',
                    self::UNPROCESSABLE_ENTITY            => 'The server understands the content type of the request entity, and the syntax of the request entity is correct, but was unable to process the contained instructions (i.e. has semantic errors)',
                    self::LOCKED                          => 'The source or destination resource of a method is locked',
                    self::FAILED_DEPENDENCY               => 'The method could not be performed on the resource because the requested action depended on another action and that action failed',
                    self::UPGRADE_REQUIRED                => 'The server refuses to perform the request using the current protocol but might be willing to do so after the client upgrades to a different protocol',
                    self::PRECONDITION_REQUIRED           => 'The origin server requires the request to be conditional',
                    self::TOO_MANY_REQUESTS               => 'The user has sent too many requests in a given amount of time ("rate limiting")',
                    self::REQUEST_HEADER_FIELDS_TOO_LARGE => 'The server is unwilling to process the request because its header fields are too large. The request MAY be resubmitted after reducing the size of the request header fields',
                    self::UNAVAILABLE_FOR_LEGAL_REASONS   => 'The server is denying access to the resource as a consequence of a legal demand',

                    // 5xx

                    self::INTERNAL_SERVER_ERROR    => 'The server encountered an unexpected condition that prevented it from fulfilling the request',
                    self::NOT_IMPLEMENTED          => 'The server does not support the functionality required to fulfill the request',
                    self::BAD_GATEWAY              => 'The server, while acting as a gateway or proxy, received an invalid response from an inbound server it accessed while attempting to fulfill the request',
                    self::SERVICE_UNAVAILABLE      => 'The server is currently unable to handle the request due to a temporary overload or scheduled maintenance, which will likely be alleviated after some delay',
                    self::GATEWAY_TIMEOUT          => 'The server, while acting as a gateway or proxy, did not receive a timely response from an upstream server it needed to access in order to complete the request',
                    self::VERSION_NOT_SUPPORTED    => 'The server does not support, or refuses to support, the major version of HTTP that was used in the request message',
                    self::VARIANT_ALSO_NEGOTIATES  => 'The server has an internal configuration error: the chosen variant resource is configured to engage in transparent content negotiation itself, and is therefore not a proper end point in the negotiation process',
                    self::INSUFFICIENT_STORAGE     => 'The method could not be performed on the resource because the server is unable to store the representation needed to successfully complete the request',
                    self::LOOP_DETECTED            => 'The server terminated an operation because it encountered an infinite loop while processing a request with "Depth: infinity". This status indicates that the entire operation failed',
                    self::BANDWIDTH_LIMIT_EXCEEDED => 'Not part of the HTTP standard. It is issued when the servers bandwidth limits have been exceeded',
                    self::NOT_EXTENDED             => 'The policy for accessing the resource has not been met in the request. The server should send back all the information necessary for the client to issue an extended request',

                    self::HTTP_NETWORK_AUTHENTICATION_REQUIRED => 'The client needs to authenticate to gain network access',

                    self::SERVICE_NOT_FOUND => 'The requested service is not found on the server. The service is determined by the part of the endpoint, which may indicate that the provided service name is invalid',

                ][$code] ?? '';
    }

    /**
     * @param string $code
     * @param bool   $withCode [optional]
     *
     * @return string|null The status text
     */
    public static function __callStatic(string $code, $withCode): ?string
    {
        try {
            $withCode += [false];
            $status = constant("self::$code");
            return ($withCode[0] ? $status . ' ' : '') . self::CODE[$status];
        } catch (Exception $e) {
            return null;
        }
    }
}
