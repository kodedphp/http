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
use Koded\Http\Interfaces\{HttpStatus, Response};
use Psr\Http\Client\{NetworkExceptionInterface, RequestExceptionInterface};
use Psr\Http\Message\{RequestInterface, ResponseInterface};


trait Psr18ClientTrait
{
    /**
     * Sends a PSR-7 request and returns a PSR-7 response.
     *
     * @param RequestInterface $request
     *
     * @return ResponseInterface
     *
     * @throws \Psr\Http\Client\ClientExceptionInterface If an error happens while processing the request.
     */
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        /** @var Response $response */
        $response = $this
            ->withMethod($request->getMethod())
            ->withUri($request->getUri())
            ->withHeaders($request->getHeaders())
            ->withBody($request->getBody())
            ->read();

        if ($response->getStatusCode() >= HttpStatus::BAD_REQUEST) {
            throw new Psr18Exception($response->getBody()->getContents(), $response->getStatusCode(), $this);
        }

        return $response;
    }
}


class Psr18Exception extends Exception implements RequestExceptionInterface, NetworkExceptionInterface
{
    private $request;

    public function __construct(string $message, int $code, RequestInterface $request)
    {
        parent::__construct($message, $code);
        $this->request = $request;
    }

    public function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
